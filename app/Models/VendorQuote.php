<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorQuote extends Model
{
    use Audited;

    protected $table = 'vendor_quotes';

    protected $fillable = ['file_id', 'vendor_id', 'ref', 'carrier', 'routing', 'currency', 'freight', 'surcharges', 'origin_charges', 'destination_charges', 'total', 'transit_days', 'free_days', 'valid_until', 'etd', 'conditions', 'lane', 'status', 'source', 'ai_confidence', 'inbound_email_id', 'is_chosen', 'created_by'];

    protected function casts(): array
    {
        return ['surcharges' => 'array', 'lane' => 'array', 'ai_confidence' => 'array', 'valid_until' => 'date', 'etd' => 'date', 'is_chosen' => 'boolean', 'freight' => 'decimal:2', 'origin_charges' => 'decimal:2', 'destination_charges' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ShipmentFile::class, 'file_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function inboundEmail(): BelongsTo
    {
        return $this->belongsTo(InboundEmail::class);
    }
}
