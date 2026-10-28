<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use Audited;

    protected $table = 'exchange_rates';

    protected $fillable = ['currency', 'rate_per_usd', 'locked', 'source', 'fetched_at'];

    protected function casts(): array
    {
        return ['rate_per_usd' => 'decimal:6', 'locked' => 'boolean', 'fetched_at' => 'datetime'];
    }
}
