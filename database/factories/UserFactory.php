<?php

namespace Database\Factories;

use App\Enums\UserRole;
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
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Agency,
            'agency_id' => null,
            'phone' => null,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Administrator,
            'agency_id' => null,
        ]);
    }

    public function agency(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Agency,
        ]);
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
