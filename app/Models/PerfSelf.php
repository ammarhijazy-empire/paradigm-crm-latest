<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerfSelf extends Model
{
    use Audited;

    protected $table = 'perf_self';

    protected $fillable = ['user_id', 'period', 'answers'];

    protected function casts(): array
    {
        return ['answers' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
