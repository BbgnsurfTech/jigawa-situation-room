<?php

namespace App\Models;

use App\Traits\DeploymentScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollingStation extends Model
{
    use HasFactory, DeploymentScoped;

    protected $fillable = [
        'name', 'code', 'ward_id', 'lga_id',
        'registered_voter_count', 'gps_latitude', 'gps_longitude',
        'is_active', 'assigned_pua_id',
    ];

    protected function getStateRelationPath(): string
    {
        return 'via_lga';
    }

    protected $casts = [
        'is_active'              => 'boolean',
        'registered_voter_count' => 'integer',
    ];

    public function ward(): BelongsTo  { return $this->belongsTo(Ward::class); }
    public function lga(): BelongsTo   { return $this->belongsTo(Lga::class); }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_pua_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(User::class, 'polling_station_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(WardReport::class, 'polling_station_id');
    }

    // Scopes
    public function scopeActive($query)     { return $query->where('is_active', true); }
    public function scopeUnassigned($query)  { return $query->whereNull('assigned_pua_id'); }
}
