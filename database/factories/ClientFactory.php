<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Client> */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'company' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone' => '+20 10'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'sector' => 'chemicals',
            'status' => 'active',
            'owner_id' => User::factory()->role('sales'),
        ];
    }
}
