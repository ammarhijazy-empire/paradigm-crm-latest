<?php

namespace App\Support;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;

class Money
{
    public static function rate(string $currency): float
    {
        if ($currency === 'USD') {
            return 1.0;
        }
        $rates = Cache::remember('crm.rates', 300, fn () => ExchangeRate::pluck('rate_per_usd', 'currency')->map(fn ($r) => (float) $r)->all());
        return $rates[$currency] ?? throw new \RuntimeException("No exchange rate for {$currency}");
    }

    public static function toUsd(float|string|null $amount, string $currency, ?float $rate = null): float
    {
        $rate ??= self::rate($currency);
        return round(((float) $amount) / ($rate ?: 1), 2);
    }

    public static function format(float|string|null $amount, string $currency = 'USD', int $dec = 2): string
    {
        $n = number_format((float) $amount, $dec);
        return in_array($currency, ['USD', 'EUR'], true) ? ($currency === 'USD' ? '$' : '€').$n : $n.' '.$currency;
    }

    public static function forgetRates(): void
    {
        Cache::forget('crm.rates');
    }
}
