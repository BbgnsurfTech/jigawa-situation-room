<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Election extends Model
{
    protected $fillable = [
        'name',
        'type',
        'scope',
        'election_date',
        'is_active',
        'state_id',
        'senatorial_district_id',
        'federal_constituency_id',
        'state_constituency_id',
    ];

    protected $casts = [
        'election_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Election types
    public const TYPE_PRESIDENTIAL = 'presidential';
    public const TYPE_GOVERNORSHIP = 'governorship';
    public const TYPE_SENATE = 'senate';
    public const TYPE_HOUSE_OF_REPS = 'house_of_reps';
    public const TYPE_STATE_ASSEMBLY = 'state_assembly';

    public const TYPES = [
        self::TYPE_PRESIDENTIAL => 'Presidential',
        self::TYPE_GOVERNORSHIP => 'Governorship',
        self::TYPE_SENATE => 'Senate',
        self::TYPE_HOUSE_OF_REPS => 'House of Representatives',
        self::TYPE_STATE_ASSEMBLY => 'State House of Assembly',
    ];

    // Election scopes
    public const SCOPE_NATIONAL = 'national';
    public const SCOPE_STATE = 'state';
    public const SCOPE_SENATORIAL = 'senatorial_district';
    public const SCOPE_FEDERAL_CONST = 'federal_constituency';
    public const SCOPE_STATE_CONST = 'state_constituency';

    public const SCOPES = [
        self::SCOPE_NATIONAL => 'National',
        self::SCOPE_STATE => 'State-wide',
        self::SCOPE_SENATORIAL => 'Senatorial District',
        self::SCOPE_FEDERAL_CONST => 'Federal Constituency',
        self::SCOPE_STATE_CONST => 'State Constituency',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function senatorialDistrict(): BelongsTo
    {
        return $this->belongsTo(SenatorialDistrict::class);
    }

    public function federalConstituency(): BelongsTo
    {
        return $this->belongsTo(FederalConstituency::class);
    }

    public function stateConstituency(): BelongsTo
    {
        return $this->belongsTo(StateConstituency::class);
    }

    // -------------------------------------------------------------------------
    // Query Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOfScope(Builder $query, string $scope): Builder
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope elections by deployment context.
     * For state deployment: filters to state-specific or national elections
     * For national deployment: returns all elections
     */
    public function scopeForDeployment(Builder $query): Builder
    {
        $deploymentScope = config('jesr.deployment_scope', 'state');

        if ($deploymentScope === 'national') {
            return $query;
        }

        $stateCode = config('jesr.state_code');
        if (!$stateCode) {
            return $query;
        }

        return $query->where(function ($q) use ($stateCode) {
            $q->whereHas('state', fn ($s) => $s->where('code', $stateCode))
              ->orWhere('scope', self::SCOPE_NATIONAL);
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucwords(str_replace('_', ' ', $this->type));
    }

    public function getScopeLabel(): string
    {
        return self::SCOPES[$this->scope] ?? ucwords(str_replace('_', ' ', $this->scope));
    }
}
