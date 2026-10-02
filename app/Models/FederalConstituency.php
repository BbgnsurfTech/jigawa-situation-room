<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FederalConstituency extends Model
{
    protected $fillable = ['name', 'code', 'state_id'];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function lgas(): BelongsToMany
    {
        return $this->belongsToMany(Lga::class, 'federal_constituency_lga');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function elections(): HasMany
    {
        return $this->hasMany(Election::class);
    }
}
