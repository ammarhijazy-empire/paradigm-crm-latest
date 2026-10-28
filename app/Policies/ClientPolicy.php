<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['sales', 'accounts']);
    }

    public function view(User $u, Client $c): bool
    {
        return ($u->hasRole('sales') && $c->owner_id === $u->id) || $u->hasRole('accounts');
    }

    public function create(User $u): bool
    {
        return $u->hasRole('sales');
    }

    public function update(User $u, Client $c): bool
    {
        return $u->hasRole('sales') && $c->owner_id === $u->id;
    }

    public function updateCredit(User $u, Client $c): bool
    {
        return $u->hasRole('accounts');
    }

    public function delete(User $u, Client $c): bool
    {
        return false;
    }
}
