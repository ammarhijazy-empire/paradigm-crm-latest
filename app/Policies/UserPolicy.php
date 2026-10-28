<?php

namespace App\Policies;

use App\Models\User;

/** Users, roles, targets and commission: management only (granted in Gate::before). */
class UserPolicy
{
    public function viewAny(User $u): bool
    {
        return false;
    }

    public function update(User $u, User $target): bool
    {
        return false;
    }
}
