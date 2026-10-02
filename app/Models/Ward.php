<?php

namespace App\Models;

use App\Traits\DeploymentScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ward extends Model
{
    use HasFactory, DeploymentScoped;

    protected $fillable = ['name', 'code', 'lga_id', 'senatorial_district_id', 'boundary_geojson'];

    /**
     * Exclude boundary_geojson from default serialization (toArray/toJson).
     * This prevents massive GeoJSON data from bloating Livewire payloads.
     * Use explicit select() or makeVisible() when boundary data is needed.
     */
    protected $hidden = ['boundary_geojson'];

    protected function getStateRelationPath(): string
    {
        return 'via_lga';
    }

    protected $casts = [
        'boundary_geojson' => 'array',
    ];

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function senatorialDistrict(): BelongsTo
    {
        return $this->belongsTo(SenatorialDistrict::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function wardReports(): HasMany
    {
        return $this->hasMany(WardReport::class);
    }

    public function pollingStations(): HasMany
    {
        return $this->hasMany(PollingStation::class);
    }

    public function stateConstituencies(): BelongsToMany
    {
        return $this->belongsToMany(StateConstituency::class, 'state_constituency_ward');
    }
}
