<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->userName().'@paradigmcargo.com',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'active' => true,
            'locale' => 'ar',
        ];
    }

    /** Give the user one or more CRM roles after creation. */
    public function role(string ...$roles): static
    {
        return $this->afterCreating(function (\App\Models\User $u) use ($roles) {
            foreach ($roles as $r) {
                \Spatie\Permission\Models\Role::findOrCreate($r, 'web');
            }
            $u->syncRoles($roles);
            if (in_array('sales', $roles, true) && ! $u->initials) {
                $u->forceFill(['initials' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $u->name).'XX', 0, 2)).$u->id])->save();
            }
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
