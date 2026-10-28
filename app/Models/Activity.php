<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use Audited;

    protected $table = 'activities';

    protected $fillable = ['client_id', 'file_id', 'user_id', 'type', 'body', 'rating', 'happened_at'];

    protected function casts(): array
    {
        return ['happened_at' => 'datetime', 'rating' => 'integer'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ShipmentFile::class, 'file_id');
    }
}
