<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteRound extends Model
{
    use Audited;

    protected $table = 'quote_rounds';

    protected $fillable = ['file_id', 'round', 'our_price_usd', 'counter_offer_usd', 'outcome', 'notes', 'user_id'];

    protected function casts(): array
    {
        return ['our_price_usd' => 'decimal:2', 'counter_offer_usd' => 'decimal:2'];
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
