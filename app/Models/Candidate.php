<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidate extends Model
{
    protected $fillable = [
        'name',
        'party_id',
        'post_type',
        'state_id',
        'lga_id',
        'ward_id',
        'senatorial_district_id',
        'federal_constituency_id',
        'state_constituency_id',
    ];

    // Post types
    public const POST_PRESIDENTIAL = 'presidential';
    public const POST_GOVERNORSHIP = 'governorship';
    public const POST_SENATE = 'senate';
    public const POST_HOUSE_OF_REPS = 'house_of_reps';
    public const POST_STATE_ASSEMBLY = 'state_assembly';

    public const POST_TYPES = [
        self::POST_PRESIDENTIAL => 'Presidential',
        self::POST_GOVERNORSHIP => 'Governorship',
        self::POST_SENATE => 'Senate',
        self::POST_HOUSE_OF_REPS => 'House of Representatives',
        self::POST_STATE_ASSEMBLY => 'State House of Assembly',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
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
    // Helpers
    // -------------------------------------------------------------------------

    public function getPostTypeLabel(): string
    {
        return self::POST_TYPES[$this->post_type] ?? ucwords(str_replace('_', ' ', $this->post_type));
    }
}
