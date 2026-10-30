<?php

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;

class GoalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Goal $goal): bool
    {
        return $goal->lifeDomain->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Goal $goal): bool
    {
        return $goal->lifeDomain->user_id === $user->id;
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $goal->lifeDomain->user_id === $user->id;
    }
}

