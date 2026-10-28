<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use Audited;

    protected $table = 'invoice_lines';

    protected $fillable = ['invoice_id', 'description_en', 'description_ar', 'qty', 'unit_price', 'vat_applies', 'vat_amount', 'amount', 'sort'];

    protected function casts(): array
    {
        return ['qty' => 'decimal:2', 'unit_price' => 'decimal:2', 'vat_amount' => 'decimal:2', 'amount' => 'decimal:2', 'vat_applies' => 'boolean'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
