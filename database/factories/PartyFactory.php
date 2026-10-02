<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PartyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company() . ' Party',
            'acronym' => fake()->unique()->lexify('???'),
            'colour_hex' => fake()->hexColor(),
        ];
    }
}
