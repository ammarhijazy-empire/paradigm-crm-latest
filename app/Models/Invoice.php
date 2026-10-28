<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use Audited;

    protected $fillable = ['number', 'file_id', 'client_id', 'currency', 'issue_date', 'due_date', 'e_invoice_no', 'subtotal', 'vat_total', 'total', 'paid', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'due_date' => 'date', 'subtotal' => 'decimal:2', 'vat_total' => 'decimal:2', 'total' => 'decimal:2', 'paid' => 'decimal:2'];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ShipmentFile::class, 'file_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('sort');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function balance(): float
    {
        return round((float) $this->total - (float) $this->paid, 2);
    }

    public function isOverdue(): bool
    {
        return $this->balance() > 0 && $this->due_date->isPast();
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }
}
