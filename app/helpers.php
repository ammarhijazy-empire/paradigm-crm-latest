<?php

use App\Models\Port;
use App\Support\Dates;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

if (! function_exists('money')) {
    function money(float|string|null $amount, string $currency = 'USD', int $dec = 2): string
    {
        return Money::format($amount, $currency, $dec);
    }
}

if (! function_exists('d')) {
    /** Date for display: day/month/year in Cairo time. */
    function d(?CarbonInterface $date, bool $time = false): string
    {
        return Dates::show($date, $time);
    }
}

if (! function_exists('port_name')) {
    function port_name(?string $code): string
    {
        if (! $code) {
            return '—';
        }
        $all = Cache::remember('crm.ports', 3600, fn () => Port::get(['code', 'name_en', 'name_ar'])->keyBy('code')->map(fn ($p) => ['en' => $p->name_en, 'ar' => $p->name_ar])->all());
        return $all[$code][app()->getLocale()] ?? $code;
    }
}

if (! function_exists('rtl')) {
    function rtl(): bool
    {
        return app()->getLocale() === 'ar';
    }
}

if (! function_exists('local_text')) {
    /** For JSON {"en":..,"ar":..} strings stored by the AI document check. */
    function local_text(?string $value): string
    {
        $j = json_decode((string) $value, true);
        return is_array($j) ? ($j[app()->getLocale()] ?? $j['en'] ?? '') : (string) $value;
    }
}
