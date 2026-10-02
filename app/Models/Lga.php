<?php

namespace App\Models;

use App\Traits\DeploymentScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lga extends Model
{
    use HasFactory, DeploymentScoped;

    protected $fillable = ['name', 'code', 'state_id', 'boundary_geojson'];

    /**
     * Exclude boundary_geojson from default serialization (toArray/toJson).
     * This prevents massive GeoJSON data from bloating Livewire payloads.
     * Use explicit select() or makeVisible() when boundary data is needed.
     */
    protected $hidden = ['boundary_geojson'];

    protected $casts = [
        'boundary_geojson' => 'array',
    ];

    protected function getStateRelationPath(): string
    {
        return 'direct';
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function wards(): HasMany
    {
        return $this->hasMany(Ward::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function pollingStations(): HasMany
    {
        return $this->hasMany(PollingStation::class);
    }

    public function wardReports(): HasMany
    {
        return $this->hasMany(WardReport::class);
    }

    public function federalConstituencies(): BelongsToMany
    {
        return $this->belongsToMany(FederalConstituency::class, 'federal_constituency_lga');
    }

    public function stateConstituencies(): HasMany
    {
        return $this->hasMany(StateConstituency::class);
    }
}
