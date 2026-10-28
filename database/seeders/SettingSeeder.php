<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Settings::DEFAULTS as $k => $v) {
            Setting::firstOrCreate(['key' => $k], ['value' => $v]);
        }
        // Starting values only; the scheduler refreshes them every morning.
        foreach (['EGP' => 49.35, 'EUR' => 0.921, 'CNY' => 7.142, 'AED' => 3.6725, 'TRY' => 41.8, 'GBP' => 0.79, 'SAR' => 3.75] as $cur => $rate) {
            ExchangeRate::firstOrCreate(['currency' => $cur], ['rate_per_usd' => $rate, 'locked' => false, 'source' => 'seed']);
        }
    }
}
