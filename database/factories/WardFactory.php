<?php

namespace Database\Factories;

use App\Models\Lga;
use Illuminate\Database\Eloquent\Factories\Factory;

class WardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->streetName() . ' Ward',
            'code' => fake()->unique()->bothify('WRD###'),
            'lga_id' => Lga::factory(),
            'senatorial_district_id' => null,
        ];
    }
}
