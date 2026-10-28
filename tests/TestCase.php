<?php

namespace Tests;

use App\Models\Client;
use App\Models\ShipmentFile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected bool $seedReference = true;

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->seedReference && in_array(\Illuminate\Foundation\Testing\RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            $this->seed(DatabaseSeeder::class);
        }
        $this->withoutVite();
    }

    protected function user(string ...$roles): User
    {
        return User::factory()->role(...$roles)->create();
    }

    /** A file owned by $sales (a new salesperson if none given), placed at $stage. */
    protected function fileFor(?User $sales = null, int $stage = 1, array $checks = [], array $attrs = []): ShipmentFile
    {
        $sales ??= $this->user('sales');
        $client = Client::factory()->create(['owner_id' => $sales->id]);
        return ShipmentFile::factory()->atStage($stage, $checks)->create(['client_id' => $client->id, 'sales_id' => $sales->id] + $attrs);
    }
}
