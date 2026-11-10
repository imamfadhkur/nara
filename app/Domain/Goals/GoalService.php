<?php

namespace App\Domain\Goals;

use App\Domain\Activity\ActivityLogger;
use App\Domain\DomainRuleException;
use App\Domain\Projects\ProgressCalculator;
use App\Models\Goal;
use App\Models\LifeDomain;
use App\Models\User;
use App\Support\Enums\ActivityEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Life Domain & Goal: CRUD sederhana lewat satu pintu (aturan struktur 3.4).
 */
class GoalService
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly ProgressCalculator $progress,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function createDomain(User $user, array $data): LifeDomain
    {
        $domain = new LifeDomain(Arr::except($data, ['user_id']));
        $domain->user()->associate($user);
        $domain->sort_order ??= (int) $user->lifeDomains()->max('sort_order') + 1;
        $domain->save();

        $this->logger->log($domain, ActivityEvent::Created, null, ['name' => $domain->name]);

        return $domain;
    }

    /** @param  array<string, mixed>  $data */
    public function updateDomain(LifeDomain $domain, array $data): LifeDomain
    {
        $domain->fill(Arr::except($data, ['user_id']))->save();
        $this->logger->logChanges($domain);

        return $domain;
    }

    /** Domain yang masih punya goal tidak boleh dihapus (mencegah cascade tak sengaja). */
    public function deleteDomain(LifeDomain $domain): void
    {
        if ($domain->goals()->exists()) {
            throw new DomainRuleException('Life Domain ini masih memiliki goal. Pindahkan atau hapus goal-nya dulu.');
        }

        $domain->delete();
        $this->logger->log($domain, ActivityEvent::Deleted, ['name' => $domain->name]);
    }

    /** @param  array<string, mixed>  $data */
    public function createGoal(LifeDomain $domain, array $data): Goal
    {
        $goal = new Goal(Arr::except($data, ['life_domain_id', 'progress_percent']));
        $goal->lifeDomain()->associate($domain);
        $goal->save();

        $this->logger->log($goal, ActivityEvent::Created, null, ['title' => $goal->title]);

        return $goal;
    }

    /** @param  array<string, mixed>  $data */
    public function updateGoal(Goal $goal, array $data): Goal
    {
        $goal->fill(Arr::except($data, ['progress_percent']));
        $event = $goal->isDirty('status') ? ActivityEvent::StatusChanged : ActivityEvent::Updated;
        $goal->save();
        $goal->unsetRelation('lifeDomain');

        $this->logger->logChanges($goal, $event);

        return $goal;
    }

    /** Project di bawahnya tetap ada, menjadi project tanpa goal (FK nullOnDelete). */
    public function deleteGoal(Goal $goal): void
    {
        DB::transaction(function () use ($goal) {
            $this->logger->log($goal, ActivityEvent::Deleted, ['title' => $goal->title]);
            $goal->delete();
        });
    }

    public function recalculate(Goal $goal): void
    {
        $this->progress->recalculateGoal($goal);
    }
}

