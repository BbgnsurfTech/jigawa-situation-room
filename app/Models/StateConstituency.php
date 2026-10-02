<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StateConstituency extends Model
{
    protected $fillable = ['name', 'code', 'state_id', 'lga_id'];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function wards(): BelongsToMany
    {
        return $this->belongsToMany(Ward::class, 'state_constituency_ward');
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
