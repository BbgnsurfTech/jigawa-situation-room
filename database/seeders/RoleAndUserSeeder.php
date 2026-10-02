<?php

namespace Database\Seeders;

use App\Models\Lga;
use App\Models\PollingStation;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'supervisor',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'ward_agent',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'polling_unit_agent', 'guard_name' => 'web']);

        // Super Admin
        $admin = User::firstOrCreate(
            ['phone' => '08000000001'],
            [
                'name'        => 'Super Admin',
                'full_name'   => 'JESR Super Administrator',
                'email'       => 'admin@jesr.gov.ng',
                'password'    => Hash::make('Admin@JESR2024'),
                'role'        => 'super_admin',
                'is_active'   => true,
                'mfa_enabled' => true,
            ]
        );
        $admin->assignRole('super_admin');

        // Demo Supervisor (Dutse LGA)
        $dutseLga = Lga::where('code', 'DUT')->first();
        if ($dutseLga) {
            $supervisor = User::firstOrCreate(
                ['phone' => '08000000002'],
                [
                    'name'      => 'Supervisor Demo',
                    'full_name' => 'Aminu Supervisor — Dutse LGA',
                    'email'     => 'supervisor@jesr.gov.ng',
                    'password'  => Hash::make('Super@JESR2024'),
                    'role'      => 'supervisor',
                    'lga_id'    => $dutseLga->id,
                    'is_active' => true,
                ]
            );
            $supervisor->assignRole('supervisor');
        }

        // Demo Ward Agent (Dutse LGA, Ward 1)
        $dutseWard = Ward::where('lga_id', $dutseLga?->id)->first();
        if ($dutseWard) {
            $agent = User::firstOrCreate(
                ['phone' => '08000000003'],
                [
                    'name'      => 'Ward Agent Demo',
                    'full_name' => 'Musa Ward Agent — ' . $dutseWard->name,
                    'email'     => 'agent@jesr.gov.ng',
                    'password'  => Hash::make('Agent@JESR2024'),
                    'role'      => 'ward_agent',
                    'lga_id'    => $dutseLga->id,
                    'ward_id'   => $dutseWard->id,
                    'is_active' => true,
                ]
            );
            $agent->assignRole('ward_agent');
        }

        // Demo Polling Unit Agent (first polling station in Dutse ward)
        $firstStation = $dutseWard
            ? PollingStation::where('ward_id', $dutseWard->id)->first()
            : null;

        if ($firstStation) {
            $puAgent = User::firstOrCreate(
                ['phone' => '08000000004'],
                [
                    'name'               => 'PU Agent Demo',
                    'full_name'          => 'Ibrahim PU Agent — ' . $firstStation->name,
                    'email'              => 'puagent@jesr.gov.ng',
                    'password'           => Hash::make('PUAgent@JESR2024'),
                    'role'               => 'polling_unit_agent',
                    'lga_id'             => $dutseLga->id,
                    'ward_id'            => $dutseWard->id,
                    'polling_station_id' => $firstStation->id,
                    'is_active'          => true,
                ]
            );
            $puAgent->assignRole('polling_unit_agent');

            // Track assignment on the polling station side
            $firstStation->update(['assigned_pua_id' => $puAgent->id]);
        }
    }
}
