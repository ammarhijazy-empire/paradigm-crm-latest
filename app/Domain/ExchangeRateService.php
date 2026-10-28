<?php

namespace App\Domain;

use App\Models\ExchangeRate;
use App\Support\Money;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    public const CURRENCIES = ['EGP', 'EUR', 'CNY', 'AED', 'TRY', 'GBP', 'SAR'];

    /** Fetches rates per USD and updates every currency that is not locked. Returns the number updated. */
    public function refresh(): int
    {
        $res = Http::timeout(20)->retry(2, 2000)->get(Settings::get('rates_source_url'))->throw()->json();
        $rates = $res['rates'] ?? $res['conversion_rates'] ?? [];
        $n = 0;
        foreach (self::CURRENCIES as $cur) {
            if (! isset($rates[$cur])) {
                continue;
            }
            $row = ExchangeRate::firstOrNew(['currency' => $cur]);
            if ($row->exists && $row->locked) {
                continue;
            }
            $row->fill(['rate_per_usd' => $rates[$cur], 'source' => parse_url(Settings::get('rates_source_url'), PHP_URL_HOST), 'fetched_at' => now()])->save();
            $n++;
        }
        Money::forgetRates();
        return $n;
    }

    public function setManual(string $cur, float $rate, bool $locked): void
    {
        ExchangeRate::updateOrCreate(['currency' => $cur], ['rate_per_usd' => $rate, 'locked' => $locked, 'source' => 'manual']);
        Money::forgetRates();
    }
}
