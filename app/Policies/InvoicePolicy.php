<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasRole('accounts');
    }

    public function view(User $u, Invoice $i): bool
    {
        return $u->hasRole('accounts') || ($u->hasRole('sales') && $i->file?->sales_id === $u->id);
    }

    public function create(User $u): bool
    {
        return $u->hasRole('accounts');
    }

    public function update(User $u, Invoice $i): bool
    {
        return $u->hasRole('accounts');
    }
}
