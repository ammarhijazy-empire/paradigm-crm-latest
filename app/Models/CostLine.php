<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostLine extends Model
{
    use Audited;

    protected $table = 'cost_lines';

    protected $fillable = ['file_id', 'type', 'description', 'vendor_id', 'currency', 'exchange_rate', 'estimated', 'actual', 'extra_fee', 'vendor_due_date', 'vendor_paid', 'vendor_paid_at'];

    protected function casts(): array
    {
        return ['exchange_rate' => 'decimal:6', 'estimated' => 'decimal:2', 'actual' => 'decimal:2', 'vendor_paid' => 'decimal:2', 'extra_fee' => 'boolean', 'vendor_due_date' => 'date', 'vendor_paid_at' => 'datetime'];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ShipmentFile::class, 'file_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
