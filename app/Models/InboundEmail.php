<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InboundEmail extends Model
{
    protected $table = 'inbound_emails';

    protected $fillable = ['message_id', 'from_email', 'from_name', 'subject', 'body_text', 'attachments', 'received_at', 'vendor_id', 'file_id', 'match_method', 'status'];

    protected function casts(): array
    {
        return ['attachments' => 'array', 'received_at' => 'datetime'];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ShipmentFile::class, 'file_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(VendorQuote::class, 'inbound_email_id');
    }
}
