<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * "See only my own rows": a salesperson (who is not management) only ever
 * gets their own records from any query. The model sets $salesOwnerColumn.
 */
trait OwnedBySales
{
    public static function bootOwnedBySales(): void
    {
        static::addGlobalScope('sales_owner', function (Builder $query) {
            $user = Auth::user();
            if (! $user || $user->isManagement()) {
                return;
            }
            // Roles that legitimately work across every salesperson's records.
            if ($user->hasAnyRole(['pricing', 'pricing_review', 'ops', 'customs', 'accounts'])) {
                return;
            }
            if ($user->hasRole('sales')) {
                $query->where($query->getModel()->getTable().'.'.static::salesOwnerColumn(), $user->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        });
    }

    public static function salesOwnerColumn(): string
    {
        return property_exists(static::class, 'salesOwnerColumn') ? (new static)->salesOwnerColumn : 'owner_id';
    }
}
