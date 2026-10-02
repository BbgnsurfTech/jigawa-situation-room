<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Party extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'acronym', 'colour_hex', 'logo_path'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }
}
