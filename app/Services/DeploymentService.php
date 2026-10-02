<?php

namespace App\Services;

use App\Models\State;
use App\Models\User;
use Illuminate\Support\Collection;

class DeploymentService
{
    /**
     * Check if this is a national deployment.
     */
    public function isNational(): bool
    {
        return config('jesr.deployment_scope') === 'national';
    }

    /**
     * Check if this is a single-state deployment.
     */
    public function isStateBased(): bool
    {
        return config('jesr.deployment_scope') === 'state';
    }

    /**
     * Get the configured state for single-state deployment.
     */
    public function getCurrentState(): ?State
    {
        if ($this->isNational()) {
            return null;
        }

        $stateCode = config('jesr.state_code');
        if (!$stateCode) {
            return null;
        }

        return State::where('code', $stateCode)->first();
    }

    /**
     * Get the current state ID for filtering.
     */
    public function getCurrentStateId(): ?int
    {
        return $this->getCurrentState()?->id;
    }

    /**
     * Get the application name based on deployment.
     */
    public function getAppName(): string
    {
        return config('jesr.app_name', 'Election Situation Room');
    }

    /**
     * Get the application short name.
     */
    public function getAppShortName(): string
    {
        return config('jesr.app_short_name', 'ESR');
    }

    /**
     * Get states accessible to a user based on their role and deployment scope.
     */
    public function getStatesForUser(User $user): Collection
    {
        if ($this->isStateBased()) {
            $state = $this->getCurrentState();
            return $state ? collect([$state]) : collect();
        }

        // National deployment
        if ($user->isSuperAdmin()) {
            return State::orderBy('name')->get();
        }

        if ($user->isStateAdmin()) {
            return State::where('id', $user->state_id)->get();
        }

        // Supervisors and agents see their assigned state
        if ($user->state_id) {
            return State::where('id', $user->state_id)->get();
        }

        return collect();
    }

    /**
     * Check if a user can access data for a specific state.
     */
    public function canAccessState(User $user, int $stateId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($this->isStateBased()) {
            return $this->getCurrentStateId() === $stateId;
        }

        return $user->state_id === $stateId;
    }
}
