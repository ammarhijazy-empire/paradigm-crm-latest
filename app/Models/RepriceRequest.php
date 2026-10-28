<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepriceRequest extends Model
{
    use Audited;

    protected $table = 'reprice_requests';

    protected $fillable = ['file_id', 'requested_by', 'reason', 'target_usd', 'status', 'answered_by', 'answer'];

    protected function casts(): array
    {
        return ['target_usd' => 'decimal:2'];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ShipmentFile::class, 'file_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
