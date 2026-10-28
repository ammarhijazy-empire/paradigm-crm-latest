<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRun extends Model
{
    protected $table = 'ai_runs';

    protected $fillable = ['feature', 'user_id', 'file_id', 'subject_type', 'subject_id', 'model', 'prompt_version', 'input_ref', 'output', 'status', 'tokens_in', 'tokens_out', 'cost_usd', 'error', 'attempts', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return ['input_ref' => 'array', 'output' => 'array', 'cost_usd' => 'decimal:4', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
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
