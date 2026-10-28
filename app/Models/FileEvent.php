<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileEvent extends Model
{
    protected $table = 'file_events';

    protected $fillable = ['file_id', 'event', 'location', 'happened_at', 'user_id'];

    protected function casts(): array
    {
        return ['happened_at' => 'datetime'];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ShipmentFile::class, 'file_id');
    }
}
