<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LgaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city() . ' LGA',
            'code' => fake()->unique()->bothify('LGA-##'),
        ];
    }
}
