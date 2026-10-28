<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLine extends Model
{
    use Audited;

    protected $table = 'quote_lines';

    protected $fillable = ['file_id', 'section', 'description_en', 'description_ar', 'currency', 'amount', 'sort'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ShipmentFile::class, 'file_id');
    }
}
