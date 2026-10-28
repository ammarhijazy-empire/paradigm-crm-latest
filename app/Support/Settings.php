<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    public const DEFAULTS = [
        'min_margin_usd' => 200,
        'vat_rate' => 14,
        'lead_sla_hours' => 4,
        'stage_wait_days' => 3,
        'ai' => ['whatsapp_rfq' => true, 'vendor_email' => true, 'doc_check' => true, 'monthly_limit_usd' => 60],
        'company' => ['name' => 'Paradigm Cargo', 'tax_reg_no' => '', 'commercial_reg_no' => '', 'address' => 'Cairo, Egypt', 'phone' => '', 'email' => ''],
        'banks' => [
            'USD' => ['bank' => '', 'account' => '', 'iban' => '', 'swift' => ''],
            'EUR' => ['bank' => '', 'account' => '', 'iban' => '', 'swift' => ''],
            'EGP' => ['bank' => '', 'account' => '', 'iban' => '', 'swift' => ''],
        ],
        'rates_source_url' => 'https://open.er-api.com/v6/latest/USD',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('crm.settings', fn () => Setting::pluck('value', 'key')->all());
        return $all[$key] ?? self::DEFAULTS[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('crm.settings');
    }
}
