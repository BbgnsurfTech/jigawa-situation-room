<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuPartyResult extends Model
{
    public $timestamps = false;

    protected $fillable = ['ward_report_id', 'party_name', 'election_type', 'votes_scored'];

    protected $casts = [
        'votes_scored' => 'integer',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(WardReport::class, 'ward_report_id');
    }
}
