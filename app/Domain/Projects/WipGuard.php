<?php

namespace App\Domain\Projects;

use App\Domain\Settings\SettingService;
use App\Models\Project;
use App\Models\User;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\WipSlot;
use Illuminate\Support\Collection;

/**
 * Batas WIP: maksimal N project `active` per slot (Bagian 7.3, default 3).
 * Sengaja keras: pagar utama melawan overkomitmen.
 */
class WipGuard
{
    public function __construct(private readonly SettingService $settings) {}

    public function limit(User $user): int
    {
        return max(1, $this->settings->int($user, 'wip_limit_per_slot'));
    }

    /**
     * Pastikan $project boleh berstatus active di $slot.
     *
     * @throws WipLimitExceededException
     */
    public function ensureCanActivate(Project $project, WipSlot $slot): void
    {
        $user = $project->user ?? User::findOrFail($project->user_id);
        $occupants = $this->activeIn($user, $slot, exceptId: $project->exists ? $project->id : null);
        $limit = $this->limit($user);

        if ($occupants->count() >= $limit) {
            throw new WipLimitExceededException(sprintf(
                'Slot %s sudah penuh (%d/%d): %s. Jeda salah satu, atau biarkan %s tetap sebagai ide.',
                $slot->label(),
                $occupants->count(),
                $limit,
                $occupants->pluck('name')->implode(', '),
                $project->name ?: 'project ini',
            ));
        }
    }

    /** @return Collection<int, Project> */
    public function activeIn(User $user, WipSlot $slot, ?int $exceptId = null): Collection
    {
        return Project::query()
            ->where('user_id', $user->id)
            ->where('status', ProjectStatus::Active)
            ->where('wip_slot', $slot)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Ringkasan pemakaian slot untuk dashboard.
     *
     * @return array<string, array{slot: WipSlot, count: int, limit: int, projects: Collection<int, Project>}>
     */
    public function usage(User $user): array
    {
        $limit = $this->limit($user);
        $usage = [];

        foreach (WipSlot::cases() as $slot) {
            $projects = $this->activeIn($user, $slot);
            $usage[$slot->value] = [
                'slot' => $slot,
                'count' => $projects->count(),
                'limit' => $limit,
                'projects' => $projects,
            ];
        }

        return $usage;
    }
}

