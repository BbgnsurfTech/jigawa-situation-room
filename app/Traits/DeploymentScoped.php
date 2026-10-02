<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait DeploymentScoped
{
    /**
     * Scope query to current deployment context.
     * For state deployment: filters to single state
     * For national deployment: no filtering (returns all)
     */
    public function scopeForDeployment(Builder $query): Builder
    {
        $scope = config('jesr.deployment_scope', 'state');

        if ($scope === 'national') {
            return $query;
        }

        $stateCode = config('jesr.state_code');
        if (!$stateCode) {
            return $query;
        }

        $statePath = $this->getStateRelationPath();

        return match ($statePath) {
            'direct' => $query->whereHas('state', fn ($q) => $q->where('code', $stateCode)),
            'via_lga' => $query->whereHas('lga.state', fn ($q) => $q->where('code', $stateCode)),
            'via_ward' => $query->whereHas('ward.lga.state', fn ($q) => $q->where('code', $stateCode)),
            default => $query,
        };
    }

    /**
     * Override in models that need different relationship paths to State.
     * Options: 'direct', 'via_lga', 'via_ward', 'none'
     */
    protected function getStateRelationPath(): string
    {
        return 'direct';
    }
}
