<?php

namespace App\Livewire\Admin;

use Database\Seeders\PermissionSeeder;
use Livewire\Component;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionManager extends Component
{
    /** [role_name => [permission_name => bool]] */
    public array $matrix = [];

    /** Ordered list of role names as they appear in the column header */
    public array $roleNames = [];

    /** Spatie Role models as plain arrays (for wire:key stability) */
    public array $roles = [];

    /** PermissionSeeder::GROUPS — permission categories with labels */
    public array $groups = [];

    /** Tracks the last toggle for the inline "saved" indicator */
    public ?string $lastSaved = null;

    public function mount(): void
    {
        if (!auth()->user()->isSuperAdmin()) {
            $this->redirect(route('dashboard'), navigate: true);
            return;
        }

        $this->groups    = PermissionSeeder::GROUPS;
        $this->roleNames = array_keys(PermissionSeeder::DEFAULT_MATRIX);

        $this->loadMatrix();
    }

    public function toggle(string $roleName, string $permissionName): void
    {
        if ($roleName === 'super_admin') {
            $this->dispatch('notify', [
                'message' => 'Super Admin always has all permissions and cannot be restricted.',
                'type'    => 'warning',
            ]);
            return;
        }

        // Ensure permission exists in DB — if not, guide admin to sync first
        if (!Permission::where('name', $permissionName)->where('guard_name', 'web')->exists()) {
            $this->dispatch('notify', [
                'message' => "Permission '{$permissionName}' not found in database. Use \"Sync Permissions\" to initialize.",
                'type'    => 'error',
            ]);
            return;
        }

        $role = Role::findByName($roleName, 'web');

        try {
            if ($role->hasPermissionTo($permissionName)) {
                $role->revokePermissionTo($permissionName);
                $this->matrix[$roleName][$permissionName] = false;
            } else {
                $role->givePermissionTo($permissionName);
                $this->matrix[$roleName][$permissionName] = true;
            }
        } catch (PermissionDoesNotExist) {
            $this->dispatch('notify', [
                'message' => "Permission '{$permissionName}' not found. Use \"Sync Permissions\" to initialize.",
                'type'    => 'error',
            ]);
            return;
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->lastSaved = now()->format('H:i:s');

        $this->dispatch('notify', [
            'message' => 'Permission updated successfully.',
            'type'    => 'success',
        ]);
    }

    public function syncPermissions(): void
    {
        (new PermissionSeeder())->run();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->loadMatrix();

        $this->dispatch('notify', [
            'message' => 'Permissions synchronized from system defaults.',
            'type'    => 'success',
        ]);
    }

    public function resetRole(string $roleName): void
    {
        if ($roleName === 'super_admin') return;

        $defaults = PermissionSeeder::DEFAULT_MATRIX[$roleName] ?? [];

        Role::findByName($roleName, 'web')->syncPermissions($defaults);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->loadMatrix();

        $label = ucwords(str_replace('_', ' ', $roleName));
        $this->dispatch('notify', [
            'message' => "{$label} permissions reset to system defaults.",
            'type'    => 'info',
        ]);
    }

    public function resetAll(): void
    {
        foreach (PermissionSeeder::DEFAULT_MATRIX as $roleName => $permissions) {
            if ($roleName === 'super_admin') continue;
            Role::findByName($roleName, 'web')->syncPermissions($permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->loadMatrix();

        $this->dispatch('notify', [
            'message' => 'All role permissions reset to system defaults.',
            'type'    => 'info',
        ]);
    }

    private function loadMatrix(): void
    {
        $this->matrix = [];

        $roles = Role::with('permissions')->whereIn('name', $this->roleNames)->get()->keyBy('name');

        foreach ($this->roleNames as $roleName) {
            $role = $roles->get($roleName);

            if (!$role) {
                // Role not seeded yet — show all as denied
                $this->matrix[$roleName] = [];
                continue;
            }

            $granted = $role->permissions->pluck('name')->flip()->map(fn () => true)->toArray();
            $this->matrix[$roleName] = $granted;
        }
    }

    public function render()
    {
        return view('livewire.admin.role-permission-manager')
            ->layout('layouts.app', ['title' => 'Role & Permission Management']);
    }
}
