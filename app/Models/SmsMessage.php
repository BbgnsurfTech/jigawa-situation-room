<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    protected $fillable = [
        'direction',
        'from_phone',
        'to_phone',
        'body',
        'parsed_report_id',
        'delivery_status',
        'retry_count',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function parsedReport(): BelongsTo
    {
        return $this->belongsTo(WardReport::class, 'parsed_report_id');
    }
}
