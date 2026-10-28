<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasRole('sales');
    }

    public function update(User $u, Lead $l): bool
    {
        return $u->hasRole('sales') && $l->assigned_to === $u->id;
    }
}
