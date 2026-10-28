<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['pricing', 'pricing_review', 'ops', 'customs', 'accounts']);
    }

    public function view(User $u, Vendor $v): bool
    {
        return $this->viewAny($u);
    }

    public function create(User $u): bool
    {
        return $u->hasAnyRole(['pricing', 'ops']);
    }

    public function update(User $u, Vendor $v): bool
    {
        return $this->create($u);
    }

    public function delete(User $u, Vendor $v): bool
    {
        return false;
    }
}
