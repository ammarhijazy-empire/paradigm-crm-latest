<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileNote extends Model
{
    use Audited;

    protected $table = 'file_notes';

    protected $fillable = ['file_id', 'user_id', 'body', 'mentions'];

    protected function casts(): array
    {
        return ['mentions' => 'array'];
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
