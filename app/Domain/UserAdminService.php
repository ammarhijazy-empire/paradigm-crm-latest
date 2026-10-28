<?php

namespace App\Domain;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Only management changes roles, targets, commission and account flags. */
class UserAdminService
{
    public function update(User $target, array $d, User $by): User
    {
        abort_unless($by->isManagement(), 403);
        return DB::transaction(function () use ($target, $d, $by) {
            $roles = array_values(array_intersect($d['roles'] ?? [], User::ROLES));
            // Never let management lock itself out of being management.
            if ($target->id === $by->id && ! in_array('admin', $roles, true)) {
                $roles[] = 'admin';
            }
            $before = $target->getRoleNames()->sort()->values()->all();
            $target->syncRoles($roles);
            $target->forceFill([
                'name' => $d['name'] ?? $target->name,
                'initials' => isset($d['initials']) ? mb_strtoupper($d['initials']) : $target->initials,
                'sales_target' => $d['sales_target'] ?? $target->sales_target,
                'commission_pct' => $d['commission_pct'] ?? $target->commission_pct,
                'active' => $target->id === $by->id ? true : (bool) ($d['active'] ?? $target->active),
                'gets_leads' => (bool) ($d['gets_leads'] ?? false),
            ])->save();
            $after = collect($roles)->sort()->values()->all();
            if ($before !== $after) {
                activity('User')->performedOn($target)->causedBy($by)->withProperties(['old' => ['roles' => $before], 'attributes' => ['roles' => $after]])->log('roles_changed');
            }
            return $target;
        });
    }
}
