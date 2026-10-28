<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FilePayTerm extends Model
{
    use Audited;

    protected $table = 'file_pay_terms';

    protected $fillable = ['file_id', 'method', 'credit_days', 'status', 'decided_by', 'decided_at', 'note'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ShipmentFile::class, 'file_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
