<?php

namespace Database\Seeders;

use App\Models\Escalation;
use App\Models\Lga;
use App\Models\Party;
use App\Models\PollingStation;
use App\Models\PuPartyResult;
use App\Models\State;
use App\Models\User;
use App\Models\WardReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    private array $firstNames = [
        'male' => ['Musa', 'Ibrahim', 'Abubakar', 'Suleiman', 'Yusuf', 'Aliyu', 'Abdullahi', 'Umar', 'Aminu', 'Haruna', 'Garba', 'Bello', 'Isah', 'Sani', 'Adamu'],
        'female' => ['Fatima', 'Aisha', 'Zainab', 'Halima', 'Hadiza', 'Amina', 'Safiya', 'Khadija', 'Maryam', 'Hauwa', 'Bilkisu', 'Rabi', 'Salamatu', 'Asiya', 'Ruka'],
    ];

    private array $lastNames = ['Mohammed', 'Bala', 'Danladi', 'Garba', 'Idris', 'Jibril', 'Lawal', 'Nasiru', 'Shuaibu', 'Tanko', 'Usman', 'Yakubu', 'Yunusa', 'Zubairu', 'Ahmad'];

    private int $phoneCounter = 1000;
    private int $totalAgents = 0;
    private int $totalReports = 0;

    public function run(): void
    {
        $this->command->info('Seeding test data...');

        $deploymentScope = config('jesr.deployment_scope', 'state');
        $stateCode = config('jesr.state_code', 'JIG');

        $parties = Party::pluck('acronym')->toArray();
        if (empty($parties)) {
            $this->command->error('No parties found. Run PartySeeder first.');
            return;
        }

        if ($deploymentScope === 'state' && $stateCode) {
            // Single state mode - seed only that state
            $state = State::where('code', $stateCode)->first();
            if (!$state) {
                $this->command->error("State with code {$stateCode} not found.");
                return;
            }
            $this->command->info("Seeding test data for {$state->name} ({$stateCode}) only...");
            $this->seedStateData($state, $parties);
        } else {
            // National mode - seed all states
            $states = State::orderBy('name')->get();
            $this->command->info("National mode: Seeding test data for all {$states->count()} states...");

            // Create state admins for national mode
            $this->createStateAdmins($states);

            // Seed each state
            foreach ($states as $index => $state) {
                $this->command->info("[" . ($index + 1) . "/{$states->count()}] Processing {$state->name}...");
                $this->seedStateData($state, $parties);
            }
        }

        $this->command->info("Test data seeding completed!");
        $this->command->info("Total agents created: {$this->totalAgents}");
        $this->command->info("Total reports created: {$this->totalReports}");
    }

    /**
     * Create state admins for each state (national mode only).
     */
    private function createStateAdmins($states): void
    {
        $this->command->info('Creating state admins...');
        $created = 0;

        foreach ($states as $index => $state) {
            $phone = '0800' . str_pad($index + 1, 7, '0', STR_PAD_LEFT);

            // Skip if state admin already exists
            if (User::where('state_id', $state->id)->where('role', 'state_admin')->exists()) {
                continue;
            }

            $gender = fake()->randomElement(['male', 'female']);
            $firstName = fake()->randomElement($this->firstNames[$gender]);
            $lastName = fake()->randomElement($this->lastNames);

            User::create([
                'name' => $firstName,
                'full_name' => "$firstName $lastName",
                'email' => strtolower("stateadmin.{$state->code}@jesr.gov.ng"),
                'phone' => $phone,
                'password' => Hash::make('StateAdmin@JESR2024'),
                'role' => 'state_admin',
                'gender' => $gender,
                'state_id' => $state->id,
                'is_active' => true,
            ]);
            $created++;
        }

        $this->command->info("Created {$created} state admins.");
    }

    /**
     * Seed test data for a single state.
     */
    private function seedStateData(State $state, array $parties): void
    {
        $lgas = Lga::with(['wards.pollingStations'])
            ->where('state_id', $state->id)
            ->get();

        if ($lgas->isEmpty()) {
            $this->command->warn("  No LGAs found for {$state->name}. Skipping...");
            return;
        }

        // Create supervisors
        $this->createSupervisors($lgas, $state);

        // Create agents and collect them
        $agents = $this->createAgents($lgas, $state);

        // Create reports in batches
        if (!empty($agents)) {
            $this->createWardReports($agents, $parties, $state);
        }
    }

    /**
     * Create supervisors for each LGA in the state.
     */
    private function createSupervisors($lgas, State $state): void
    {
        $created = 0;
        $supervisorInserts = [];

        foreach ($lgas as $index => $lga) {
            // Skip if supervisor already exists for this LGA
            if (User::where('lga_id', $lga->id)->where('role', 'supervisor')->exists()) {
                continue;
            }

            $this->phoneCounter++;
            $phone = '0801' . str_pad($this->phoneCounter, 7, '0', STR_PAD_LEFT);

            // Ensure phone is unique
            if (User::where('phone', $phone)->exists()) {
                continue;
            }

            $gender = fake()->randomElement(['male', 'female']);
            $firstName = fake()->randomElement($this->firstNames[$gender]);
            $lastName = fake()->randomElement($this->lastNames);

            $supervisorInserts[] = [
                'name' => $firstName,
                'full_name' => "$firstName $lastName",
                'email' => strtolower("supervisor.{$lga->code}@jesr.gov.ng"),
                'phone' => $phone,
                'password' => Hash::make('Super@JESR2024'),
                'role' => 'supervisor',
                'gender' => $gender,
                'state_id' => $state->id,
                'lga_id' => $lga->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $created++;
        }

        // Batch insert supervisors
        if (!empty($supervisorInserts)) {
            foreach (array_chunk($supervisorInserts, 100) as $chunk) {
                User::insert($chunk);
            }
        }

        $this->command->info("  Created {$created} supervisors for {$state->name}");
    }

    /**
     * Create ward agents and polling unit agents.
     */
    private function createAgents($lgas, State $state): array
    {
        $agents = [];
        $wardAgentInserts = [];
        $puAgentInserts = [];

        foreach ($lgas as $lga) {
            foreach ($lga->wards as $ward) {
                // Create 1 ward agent per ward (reduced from 1-2 for speed)
                $this->phoneCounter++;
                $phone = '0802' . str_pad($this->phoneCounter, 7, '0', STR_PAD_LEFT);

                if (!User::where('phone', $phone)->exists()) {
                    $gender = fake()->randomElement(['male', 'female']);
                    $firstName = fake()->randomElement($this->firstNames[$gender]);
                    $lastName = fake()->randomElement($this->lastNames);

                    $wardAgentInserts[] = [
                        'name' => $firstName,
                        'full_name' => "$firstName $lastName",
                        'email' => strtolower("{$ward->code}.agent@jesr.gov.ng"),
                        'phone' => $phone,
                        'password' => Hash::make('Agent@JESR2024'),
                        'role' => 'ward_agent',
                        'gender' => $gender,
                        'state_id' => $state->id,
                        'lga_id' => $lga->id,
                        'ward_id' => $ward->id,
                        'party_affiliation' => fake()->randomElement(['APC', 'PDP', 'NNPP', 'LP']),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $agents[] = [
                        'phone' => $phone,
                        'lga_id' => $lga->id,
                        'ward_id' => $ward->id,
                        'type' => 'ward_agent',
                    ];
                }

                // Create 1 PU agent per ward (for first polling station only)
                $station = $ward->pollingStations->first();
                if ($station) {
                    $this->phoneCounter++;
                    $puPhone = '0803' . str_pad($this->phoneCounter, 7, '0', STR_PAD_LEFT);

                    if (!User::where('phone', $puPhone)->exists()) {
                        $gender = fake()->randomElement(['male', 'female']);
                        $firstName = fake()->randomElement($this->firstNames[$gender]);
                        $lastName = fake()->randomElement($this->lastNames);

                        $puAgentInserts[] = [
                            'name' => $firstName,
                            'full_name' => "$firstName $lastName",
                            'email' => strtolower("{$station->code}.puagent@jesr.gov.ng"),
                            'phone' => $puPhone,
                            'password' => Hash::make('PUAgent@JESR2024'),
                            'role' => 'polling_unit_agent',
                            'gender' => $gender,
                            'state_id' => $state->id,
                            'lga_id' => $lga->id,
                            'ward_id' => $ward->id,
                            'polling_station_id' => $station->id,
                            'party_affiliation' => fake()->randomElement(['APC', 'PDP', 'NNPP', 'LP']),
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $agents[] = [
                            'phone' => $puPhone,
                            'lga_id' => $lga->id,
                            'ward_id' => $ward->id,
                            'polling_station_id' => $station->id,
                            'type' => 'polling_unit_agent',
                        ];
                    }
                }
            }
        }

        // Batch insert ward agents
        if (!empty($wardAgentInserts)) {
            foreach (array_chunk($wardAgentInserts, 100) as $chunk) {
                User::insert($chunk);
            }
        }

        // Batch insert PU agents
        if (!empty($puAgentInserts)) {
            foreach (array_chunk($puAgentInserts, 100) as $chunk) {
                User::insert($chunk);
            }
        }

        $agentCount = count($wardAgentInserts) + count($puAgentInserts);
        $this->totalAgents += $agentCount;
        $this->command->info("  Created {$agentCount} agents for {$state->name}");

        return $agents;
    }

    /**
     * Create ward reports with batch insert.
     */
    private function createWardReports(array $agents, array $parties, State $state): void
    {
        $statuses = ['submitted', 'verified', 'flagged', 'escalated'];
        $channels = ['web', 'android', 'ios', 'sms'];
        $securityTypes = ['POLICE', 'ARMY', 'NSCDC', 'VIGILANTE', 'CIVIL_DEFENCE'];

        $reportInserts = [];
        $partyResultInserts = [];
        $escalationInserts = [];
        $reportCount = 0;

        // First, get all agent user IDs by phone
        $agentPhones = collect($agents)->pluck('phone')->toArray();
        $agentUsers = User::whereIn('phone', $agentPhones)->get()->keyBy('phone');

        foreach ($agents as $agentData) {
            $user = $agentUsers->get($agentData['phone']);
            if (!$user) continue;

            // Each agent creates 1-2 reports (reduced for speed)
            $numReports = fake()->numberBetween(1, 2);

            for ($i = 0; $i < $numReports; $i++) {
                $registeredVoters = fake()->numberBetween(500, 2000);
                $cast = fake()->numberBetween(100, min(800, $registeredVoters));
                $valid = fake()->numberBetween((int)($cast * 0.7), (int)($cast * 0.95));
                $invalid = $cast - $valid;

                $status = fake()->randomElement($statuses);
                $hasSecurity = fake()->boolean(30);
                $channel = fake()->randomElement($channels);
                $createdAt = fake()->dateTimeBetween('-14 days', 'now');

                $pollingStationId = $agentData['polling_station_id'] ?? null;

                $reportData = [
                    'agent_user_id' => $user->id,
                    'lga_id' => $agentData['lga_id'],
                    'ward_id' => $agentData['ward_id'],
                    'polling_station_id' => $pollingStationId,
                    'inec_agent_name' => fake()->randomElement($this->firstNames['male']) . ' ' . fake()->randomElement($this->lastNames),
                    'counted_voter_papers' => fake()->numberBetween($cast, $registeredVoters),
                    'security_present' => $hasSecurity,
                    'security_types' => $hasSecurity ? json_encode(fake()->randomElements($securityTypes, fake()->numberBetween(1, 3))) : null,
                    'voting_closed_time' => fake()->time('H:i:s'),
                    'number_cast_votes' => $cast,
                    'voting_papers_left' => fake()->numberBetween(0, 200),
                    'valid_ballot_papers' => $valid,
                    'invalid_ballot_papers' => $invalid,
                    'status' => $status,
                    'submitted_via' => $channel,
                    'device_id' => in_array($channel, ['android', 'ios']) ? 'device_' . fake()->uuid() : null,
                    'sync_status' => 'synced',
                    'registered_voter_count_snapshot' => $registeredVoters,
                    'turnout_rate' => round(($cast / $registeredVoters) * 100, 2),
                    'supervisor_note' => in_array($status, ['verified', 'flagged']) ? fake()->sentence() : null,
                    'flag_reason' => $status === 'flagged' ? fake()->randomElement([
                        'Discrepancy in vote count',
                        'Missing signatures',
                        'Incomplete data',
                        'Suspicious activity reported',
                        'Security concern',
                    ]) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                $reportInserts[] = $reportData;
                $reportCount++;
            }
        }

        // Batch insert reports
        if (!empty($reportInserts)) {
            foreach (array_chunk($reportInserts, 100) as $chunk) {
                WardReport::insert($chunk);
            }
        }

        // Get the inserted reports for party results
        $insertedReports = WardReport::whereIn('agent_user_id', $agentUsers->pluck('id'))
            ->orderBy('id', 'desc')
            ->limit($reportCount)
            ->get();

        // Create party results for reports
        foreach ($insertedReports as $report) {
            $partyCount = fake()->numberBetween(4, min(8, count($parties)));
            $selectedParties = fake()->randomElements($parties, $partyCount);
            $remainingVotes = $report->valid_ballot_papers;

            foreach ($selectedParties as $index => $partyAcronym) {
                if ($index === count($selectedParties) - 1) {
                    $votes = $remainingVotes;
                } else {
                    $maxVotes = (int)($remainingVotes * 0.6);
                    $votes = fake()->numberBetween(0, max(0, $maxVotes));
                    $remainingVotes -= $votes;
                }

                $partyResultInserts[] = [
                    'ward_report_id' => $report->id,
                    'party_name' => $partyAcronym,
                    'votes_scored' => $votes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Create escalation for escalated reports
            if ($report->status === 'escalated') {
                $supervisor = User::where('lga_id', $report->lga_id)
                    ->where('role', 'supervisor')
                    ->first();

                $escalationInserts[] = [
                    'report_id' => $report->id,
                    'level' => fake()->numberBetween(1, 3),
                    'escalated_by' => $supervisor?->id ?? $report->agent_user_id,
                    'reason' => fake()->randomElement([
                        'Security incident requires immediate attention',
                        'Significant vote count discrepancy',
                        'Electoral violence reported',
                        'Agent safety concern',
                        'Equipment malfunction affecting results',
                    ]),
                    'acknowledged_at' => fake()->boolean(50) ? fake()->dateTimeBetween($report->created_at, 'now') : null,
                    'acknowledged_by' => fake()->boolean(50) ? User::where('role', 'super_admin')->first()?->id : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Batch insert party results
        if (!empty($partyResultInserts)) {
            foreach (array_chunk($partyResultInserts, 500) as $chunk) {
                PuPartyResult::insert($chunk);
            }
        }

        // Batch insert escalations
        if (!empty($escalationInserts)) {
            foreach (array_chunk($escalationInserts, 100) as $chunk) {
                Escalation::insert($chunk);
            }
        }

        $this->totalReports += $reportCount;
        $this->command->info("  Created {$reportCount} reports for {$state->name}");
    }
}
