<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\OwnedBySales;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use Audited, HasFactory, OwnedBySales, SoftDeletes;

    public const STATUSES = ['lead', 'contacted', 'active', 'repeat', 'lost'];

    /** Fields hidden from pricing everywhere (views, JSON, PDFs, AI prompts). */
    public const CONTACT_FIELDS = ['contact_person', 'phone', 'phone_normalized', 'email', 'address'];

    protected $salesOwnerColumn = 'owner_id';

    protected $fillable = ['company', 'contact_person', 'phone', 'email', 'sector', 'sector_other', 'products', 'current_forwarder', 'source', 'status', 'owner_id', 'next_follow_up', 'next_order_date', 'credit_limit', 'credit_days', 'tax_id', 'address'];

    protected function casts(): array
    {
        return ['next_follow_up' => 'date', 'next_order_date' => 'date', 'credit_limit' => 'decimal:2', 'credit_days' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Client $c) {
            $c->phone_normalized = Phone::normalize($c->phone);
            $c->email = mb_strtolower(trim((string) $c->email));
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ShipmentFile::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest('happened_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
