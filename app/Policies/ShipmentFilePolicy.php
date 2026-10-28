<?php

namespace App\Policies;

use App\Models\ShipmentFile;
use App\Models\User;

class ShipmentFilePolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasAnyRole(['sales', 'pricing', 'pricing_review', 'ops', 'customs', 'accounts']);
    }

    public function view(User $u, ShipmentFile $f): bool
    {
        if ($u->hasAnyRole(['pricing', 'pricing_review', 'ops', 'customs', 'accounts'])) {
            return true;
        }
        return $u->hasRole('sales') && $f->sales_id === $u->id;
    }

    public function create(User $u): bool
    {
        return $u->hasRole('sales');
    }

    /** Edit the quote request (only before pricing has it). */
    public function updateRfq(User $u, ShipmentFile $f): bool
    {
        return $u->hasRole('sales') && $f->sales_id === $u->id && $f->status === 'open' && in_array($f->pricing_status, ['draft', 'pending'], true) && $f->stage <= 2;
    }

    public function price(User $u, ShipmentFile $f): bool
    {
        return $u->hasRole('pricing') && $f->status === 'open';
    }

    public function review(User $u, ShipmentFile $f): bool
    {
        return $u->hasRole('pricing_review');
    }

    public function quote(User $u, ShipmentFile $f): bool
    {
        return $u->hasRole('sales') && $f->sales_id === $u->id && $f->status === 'open';
    }

    public function operate(User $u, ShipmentFile $f): bool
    {
        return $u->hasRole('ops') && $f->status === 'open';
    }

    public function clear(User $u, ShipmentFile $f): bool
    {
        return $u->hasRole('customs') && $f->status === 'open';
    }

    public function bill(User $u, ShipmentFile $f): bool
    {
        return $u->hasRole('accounts');
    }

    /** Actual costs, paid amounts, actual profit. */
    public function viewMoney(User $u, ShipmentFile $f): bool
    {
        return $u->hasRole('accounts');
    }

    /** Invoices list on the file (sales read-only). */
    public function viewInvoices(User $u, ShipmentFile $f): bool
    {
        return $u->hasRole('accounts') || ($u->hasRole('sales') && $f->sales_id === $u->id);
    }

    /** Client email, phone and contact person: never for pricing. */
    public function viewClientContact(User $u, ShipmentFile $f): bool
    {
        return $u->hasAnyRole(['sales', 'ops', 'customs', 'accounts']);
    }

    public function uploadDocuments(User $u, ShipmentFile $f): bool
    {
        return $u->hasAnyRole(['ops', 'customs']) || ($u->hasRole('sales') && $f->sales_id === $u->id);
    }

    public function resolveDocChecks(User $u, ShipmentFile $f): bool
    {
        return $u->hasAnyRole(['ops', 'customs']);
    }

    public function sendClientUpdate(User $u, ShipmentFile $f): bool
    {
        return $u->hasAnyRole(['ops', 'customs']) || ($u->hasRole('sales') && $f->sales_id === $u->id);
    }

    public function note(User $u, ShipmentFile $f): bool
    {
        return $this->view($u, $f);
    }

    public function markLost(User $u, ShipmentFile $f): bool
    {
        return $u->hasRole('sales') && $f->sales_id === $u->id && $f->status === 'open';
    }

    public function approveMargin(User $u, ShipmentFile $f): bool
    {
        return false; // management only (Gate::before)
    }

    public function moveBack(User $u, ShipmentFile $f): bool
    {
        return false;
    }
}
