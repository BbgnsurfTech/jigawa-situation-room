<?php

namespace App\Policies;

use App\Models\PollingUnitAgent;
use App\Models\User;

class PollingUnitAgentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isSupervisor();
    }

    public function view(User $user, PollingUnitAgent $agent): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isSupervisor() && $agent->lga_id === $user->lga_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isSupervisor();
    }

    public function update(User $user, PollingUnitAgent $agent): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isSupervisor() && $agent->lga_id === $user->lga_id;
    }

    public function delete(User $user, PollingUnitAgent $agent): bool
    {
        return $user->isSuperAdmin();
    }

    public function export(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isSupervisor();
    }

    public function viewSensitive(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
