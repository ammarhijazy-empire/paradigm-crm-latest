<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileUpdate extends Model
{
    use Audited;

    protected $table = 'file_updates';

    protected $fillable = ['file_id', 'user_id', 'kind', 'channel', 'body', 'email_outbox_id'];

    public function file(): BelongsTo
    {
        return $this->belongsTo(ShipmentFile::class, 'file_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
