<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileBooking extends Model
{
    use Audited;

    protected $table = 'file_booking';

    protected $fillable = ['file_id', 'vendor_id', 'booking_no', 'vessel_voyage', 'cutoff_at', 'cfs_warehouse', 'notes'];

    protected function casts(): array
    {
        return ['cutoff_at' => 'datetime'];
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
