<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => fake()->unique()->numerify('080########'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'ward_agent',
            'gender' => fake()->randomElement(['male', 'female']),
            'party_affiliation' => fake()->randomElement(['APC', 'PDP', 'NNPP']),
            'is_active' => true,
            'mfa_enabled' => false,
            'remember_token' => Str::random(10),
        ];
    }
}
