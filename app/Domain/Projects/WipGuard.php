<?php

namespace App\Domain\Projects;

use App\Domain\Settings\SettingService;
use App\Models\Goal;
use App\Models\Project;
use App\Models\User;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\WipSlot;
use Illuminate\Support\Collection;

/**
 * Batas WIP: maksimal N project `active` per Goal (atau per legacy slot).
 * Sengaja keras: pagar utama melawan overkomitmen (Bagian 7.3).
 */
class WipGuard
{
    public function __construct(private readonly SettingService $settings) {}

    public function defaultLimit(User $user): int
    {
        return max(1, $this->settings->int($user, 'wip_limit_per_slot'));
    }

    public function limit(User $user, ?Goal $goal = null): int
    {
        if ($goal && $goal->wip_limit !== null && $goal->wip_limit > 0) {
            return (int) $goal->wip_limit;
        }

        return $this->defaultLimit($user);
    }

    /**
     * Pastikan $project boleh berstatus active.
     * Mengutamakan batas WIP berbasis Goal jika project terhubung ke Goal.
     *
     * @throws WipLimitExceededException
     */
    public function ensureCanActivate(Project $project, mixed $slot = null): void
    {
        $user = $project->user ?? User::findOrFail($project->user_id);

        // 1. Validasi Batas WIP Berbasis Goal (Opsi B & C: WIP Slot berbasis Goal dinamis)
        $goalId = $project->goal_id;
        if ($goalId) {
            $goal = $project->goal ?? Goal::find($goalId);
            if ($goal) {
                $limit = $this->limit($user, $goal);
                $occupants = Project::query()
                    ->where('user_id', $user->id)
                    ->where('goal_id', $goal->id)
                    ->where('status', ProjectStatus::Active)
                    ->when($project->exists, fn ($q) => $q->whereKeyNot($project->id))
                    ->orderBy('name')
                    ->get(['id', 'name']);

                if ($occupants->count() >= $limit) {
                    throw new WipLimitExceededException(sprintf(
                        'Slot Goal "%s" sudah penuh (%d/%d): %s. Jeda salah satu project, atau biarkan %s tetap sebagai ide.',
                        $goal->title,
                        $occupants->count(),
                        $limit,
                        $occupants->pluck('name')->implode(', '),
                        $project->name ?: 'project ini',
                    ));
                }

                return;
            }
        }

        // 2. Validasi Batas WIP Berbasis Slot Baseline / Legacy (jika tanpa Goal)
        $rawSlot = $slot ?? $project->wip_slot;
        if ($rawSlot !== null) {
            $slotEnum = $rawSlot instanceof WipSlot ? $rawSlot : WipSlot::tryFrom((string) $rawSlot);
            $slotLabel = $slotEnum ? $slotEnum->label() : (is_object($rawSlot) && method_exists($rawSlot, 'label') ? $rawSlot->label() : (string) $rawSlot);

            $occupants = $this->activeIn($user, $rawSlot, exceptId: $project->exists ? $project->id : null);
            $limit = $this->limit($user);

            if ($occupants->count() >= $limit) {
                throw new WipLimitExceededException(sprintf(
                    'Slot %s sudah penuh (%d/%d): %s. Jeda salah satu, atau biarkan %s tetap sebagai ide.',
                    $slotLabel,
                    $occupants->count(),
                    $limit,
                    $occupants->pluck('name')->implode(', '),
                    $project->name ?: 'project ini',
                ));
            }
        }
    }

    /** @return Collection<int, Project> */
    public function activeIn(User $user, mixed $slot, ?int $exceptId = null): Collection
    {
        $slotVal = $slot instanceof WipSlot ? $slot->value : (is_object($slot) && isset($slot->value) ? (string) $slot->value : (string) $slot);

        return Project::query()
            ->where('user_id', $user->id)
            ->where('status', ProjectStatus::Active)
            ->where('wip_slot', $slotVal)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Ringkasan pemakaian slot untuk dashboard.
     *
     * @return array<string, array{title: string, slot: mixed, count: int, limit: int, projects: Collection<int, Project>, goal?: Goal}>
     */
    public function usage(User $user): array
    {
        $defaultLimit = $this->defaultLimit($user);
        $usage = [];

        // Ambil Goals aktif milik user
        $goals = $user->goals()->with('lifeDomain')->where('status', 'active')->orderBy('title')->get();

        if ($goals->isNotEmpty()) {
            foreach ($goals as $goal) {
                $projects = Project::query()
                    ->where('user_id', $user->id)
                    ->where('goal_id', $goal->id)
                    ->where('status', ProjectStatus::Active)
                    ->orderBy('name')
                    ->get(['id', 'name']);

                $limit = $this->limit($user, $goal);

                $slotObj = new class($goal) {
                    public string $value;

                    public function __construct(private Goal $goal)
                    {
                        $this->value = (string) $this->goal->id;
                    }

                    public function label(): string
                    {
                        return $this->goal->title;
                    }

                    public function icon(): string
                    {
                        return '🎯';
                    }

                    public function __toString(): string
                    {
                        return $this->value;
                    }
                };

                $usage['goal_'.$goal->id] = [
                    'title' => 'Goal: ' . $goal->title,
                    'slot' => $slotObj,
                    'goal' => $goal,
                    'count' => $projects->count(),
                    'limit' => $limit,
                    'projects' => $projects,
                ];
            }

            // Project aktif yang tidak memiliki goal (jika ada)
            $noGoalProjects = Project::query()
                ->where('user_id', $user->id)
                ->whereNull('goal_id')
                ->where('status', ProjectStatus::Active)
                ->orderBy('name')
                ->get(['id', 'name']);

            if ($noGoalProjects->isNotEmpty()) {
                $slotObj = new class {
                    public string $value = 'no_goal';

                    public function label(): string
                    {
                        return 'Tanpa Goal (Mandiri)';
                    }

                    public function icon(): string
                    {
                        return '📁';
                    }

                    public function __toString(): string
                    {
                        return $this->value;
                    }
                };
                $usage['no_goal'] = [
                    'title' => 'Project Mandiri (Tanpa Goal)',
                    'slot' => $slotObj,
                    'count' => $noGoalProjects->count(),
                    'limit' => $defaultLimit,
                    'projects' => $noGoalProjects,
                ];
            }
        } else {
            // Baseline enum jika user belum memiliki goal sama sekali
            foreach (WipSlot::cases() as $slot) {
                $projects = $this->activeIn($user, $slot);
                $usage[$slot->value] = [
                    'title' => 'Slot WIP: ' . $slot->label(),
                    'slot' => $slot,
                    'count' => $projects->count(),
                    'limit' => $defaultLimit,
                    'projects' => $projects,
                ];
            }
        }

        return $usage;
    }
}
