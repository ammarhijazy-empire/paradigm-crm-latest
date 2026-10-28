<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Audited, HasFactory, HasPushSubscriptions, HasRoles, Notifiable;

    public const ROLES = ['admin', 'sales', 'pricing', 'pricing_review', 'ops', 'customs', 'accounts'];

    /** Only safe self-service fields. Targets, commission, roles and flags are set by UserAdminService. */
    protected $fillable = ['name', 'email', 'password', 'locale', 'phone'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'gets_leads' => 'boolean',
            'sales_target' => 'decimal:2',
            'commission_pct' => 'decimal:2',
            'last_lead_at' => 'datetime',
        ];
    }

    public function isManagement(): bool
    {
        return $this->hasRole('admin');
    }

    /** True when the user may act for a department (management always may). */
    public function actsAs(string $role): bool
    {
        return $this->isManagement() || $this->hasRole($role);
    }

    public function hasAnyCrmRole(): bool
    {
        return $this->roles->isNotEmpty();
    }

    public function displayInitials(): string
    {
        return $this->initials ?: mb_strtoupper(mb_substr($this->name, 0, 2));
    }
}
