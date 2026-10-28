<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileHistory extends Model
{
    protected $table = 'file_history';

    public const UPDATED_AT = null;

    protected $fillable = ['file_id', 'user_id', 'action', 'from_stage', 'to_stage', 'note'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ShipmentFile::class, 'file_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
