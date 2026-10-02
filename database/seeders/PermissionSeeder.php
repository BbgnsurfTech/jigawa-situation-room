<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Grouped permission definitions.
     * key   => machine name used in $user->can() / hasPermissionTo()
     * value => human-readable label shown in the UI matrix
     */
    public const GROUPS = [
        'Reports' => [
            'reports.view-any' => 'View reports list',
            'reports.create'   => 'Submit new reports',
            'reports.update'   => 'Edit / correct reports',
            'reports.verify'   => 'Verify reports',
            'reports.escalate' => 'Escalate reports',
            'reports.flag'     => 'Flag reports for review',
        ],
        'Analytics & Dashboard' => [
            'dashboard.access'       => 'Access analytics dashboard',
            'dashboard.agent-status' => 'View agent login status',
            'dashboard.export'       => 'Export report data',
        ],
        'Audit' => [
            'audit.view'   => 'View audit logs',
            'audit.export' => 'Export audit logs',
        ],
        'Administration' => [
            'agents.register' => 'Manage PU agent register',
            'users.manage'    => 'Manage user accounts',
            'sms.send'        => 'Send SMS notifications',
            'config.manage'   => 'System configuration',
            'roles.manage'    => 'Role & permission management',
        ],
    ];

    /**
     * Default permission matrix — mirrors the hardcoded policy behaviour.
     * The UI allows super_admin to diverge from these defaults at any time.
     */
    public const DEFAULT_MATRIX = [
        'super_admin' => [
            'reports.view-any', 'reports.create', 'reports.update', 'reports.verify',
            'reports.escalate', 'reports.flag',
            'dashboard.access', 'dashboard.agent-status', 'dashboard.export',
            'audit.view', 'audit.export',
            'agents.register', 'users.manage', 'sms.send', 'config.manage', 'roles.manage',
        ],
        'state_admin' => [
            'reports.view-any', 'reports.update', 'reports.verify', 'reports.escalate', 'reports.flag',
            'dashboard.access', 'dashboard.agent-status', 'dashboard.export',
            'audit.view', 'audit.export',
        ],
        'supervisor' => [
            'reports.view-any', 'reports.update', 'reports.verify', 'reports.escalate', 'reports.flag',
            'dashboard.access', 'dashboard.agent-status', 'dashboard.export',
            'audit.view', 'agents.register', 'sms.send',
        ],
        'ward_agent' => [
            'reports.view-any', 'reports.create', 'reports.update',
        ],
        'polling_unit_agent' => [
            'reports.view-any', 'reports.create', 'reports.update',
        ],
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create every permission
        $allPermissions = collect(self::GROUPS)->flatMap(fn ($perms) => array_keys($perms));
        foreach ($allPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // 2. Create roles (if missing) and assign their default permissions
        foreach (self::DEFAULT_MATRIX as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('JESR permissions seeded and assigned to all roles.');
    }
}
