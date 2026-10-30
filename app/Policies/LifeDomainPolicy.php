<?php

namespace App\Policies;

use App\Models\LifeDomain;
use App\Models\User;

class LifeDomainPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LifeDomain $domain): bool
    {
        return $domain->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, LifeDomain $domain): bool
    {
        return $domain->user_id === $user->id;
    }

    public function delete(User $user, LifeDomain $domain): bool
    {
        return $domain->user_id === $user->id;
    }
}

