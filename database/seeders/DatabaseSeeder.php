<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Reference data only. Safe to run on production. Demo data: php artisan db:seed --class=DemoSeeder (staging only). */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RoleSeeder::class, PortSeeder::class, SettingSeeder::class]);
    }
}
