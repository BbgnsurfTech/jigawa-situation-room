<?php

namespace Database\Factories;

use App\Models\Lga;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

class WardReportFactory extends Factory
{
    public function definition(): array
    {
        $cast = fake()->numberBetween(100, 800);
        $valid = fake()->numberBetween(50, (int)($cast * 0.85));
        $invalid = fake()->numberBetween(0, $cast - $valid);

        return [
            'agent_user_id' => User::factory(['role' => 'ward_agent']),
            'lga_id' => Lga::factory(),
            'ward_id' => Ward::factory(),
            'counted_voter_papers' => fake()->numberBetween($cast, 1000),
            'security_present' => false,
            'security_types' => null,
            'number_cast_votes' => $cast,
            'voting_papers_left' => fake()->numberBetween(0, 200),
            'valid_ballot_papers' => $valid,
            'invalid_ballot_papers' => $invalid,
            'status' => 'submitted',
            'submitted_via' => 'web',
            'sync_status' => 'synced',
        ];
    }
}
