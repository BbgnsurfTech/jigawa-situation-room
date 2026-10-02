<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Escalation extends Model
{
    protected $fillable = [
        'report_id',
        'level',
        'escalated_by',
        'reason',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'level'           => 'integer',
        'acknowledged_at' => 'datetime',
        'resolved_at'     => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function report(): BelongsTo
    {
        return $this->belongsTo(WardReport::class, 'report_id');
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // -------------------------------------------------------------------------
    // Business logic
    // -------------------------------------------------------------------------

    public function isOverdue(): bool
    {
        // Response time limits in minutes per escalation level
        $limits = [
            1 => 10,
            2 => 30,
            3 => 5,
        ];

        return is_null($this->acknowledged_at)
            && $this->created_at->diffInMinutes(now()) > ($limits[$this->level] ?? 30);
    }
}
