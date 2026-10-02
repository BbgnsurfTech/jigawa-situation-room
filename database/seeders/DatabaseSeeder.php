<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with national data.
     *
     * Order is critical due to foreign key dependencies:
     * 1. Zones (foundation)
     * 2. States (depends on zones)
     * 3. Senatorial Districts (depends on states)
     * 4. LGAs (depends on states)
     * 5. Federal Constituencies (depends on states, links to LGAs)
     * 6. Wards (depends on LGAs, senatorial districts)
     * 7. Polling Stations (depends on wards, LGAs)
     * 8. Parties, Elections, Candidates, Users
     */
    public function run(): void
    {
        $deploymentScope = config('jesr.deployment_scope', 'state');

        $this->call([
            // Geographic hierarchy (order matters!)
            ZoneSeeder::class,                  // 6 geopolitical zones
            StateSeeder::class,                 // 36 states + FCT
            SenatorialDistrictSeeder::class,    // 109 senatorial districts
            LgaSeeder::class,                   // 774 LGAs
            FederalConstituencySeeder::class,   // 360 federal constituencies
            WardSeeder::class,                  // Wards per LGA
        ]);

        // Polling stations: use official INEC data for Jigawa, generated for national
        if ($deploymentScope === 'national') {
            $this->call(NationalPollingStationSeeder::class);  // All states (generated)
        } else {
            $this->call(PollingStationSeeder::class);          // Jigawa only (INEC official)
        }

        $this->call([
            // Election entities
            PartySeeder::class,                 // Political parties
            ElectionSeeder::class,              // Elections
            CandidateSeeder::class,             // Candidates

            // Users and permissions
            RoleAndUserSeeder::class,           // Roles and demo users
            PermissionSeeder::class,            // Spatie permissions + role-permission matrix

            // Additional data
            GeolocationSeeder::class,           // GPS coordinates
            SmsTemplateSeeder::class,           // SMS templates
        ]);

        // Include test data in non-production environments
        if (app()->environment(['local', 'staging', 'testing'])) {
            $this->call(TestDataSeeder::class);
        }
    }
}
