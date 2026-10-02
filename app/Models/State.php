<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $fillable = ['name', 'code', 'zone_id', 'capital'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function lgas(): HasMany
    {
        return $this->hasMany(Lga::class);
    }

    public function senatorialDistricts(): HasMany
    {
        return $this->hasMany(SenatorialDistrict::class);
    }

    public function federalConstituencies(): HasMany
    {
        return $this->hasMany(FederalConstituency::class);
    }

    public function stateConstituencies(): HasMany
    {
        return $this->hasMany(StateConstituency::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function elections(): HasMany
    {
        return $this->hasMany(Election::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }
}
