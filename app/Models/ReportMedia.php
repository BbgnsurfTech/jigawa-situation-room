<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReportMedia extends Model
{
    protected $fillable = [
        'report_id',
        'media_type',
        'storage_path',
        'thumbnail_path',
        'is_sensitive',
        'upload_status',
        'chain_of_custody_notes',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function report(): BelongsTo
    {
        return $this->belongsTo(WardReport::class, 'report_id');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getUrlAttribute(): string
    {
        return Storage::url($this->storage_path);
    }
}
