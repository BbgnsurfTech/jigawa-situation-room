<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollingUnitClusterMember extends Model
{
    protected $fillable = [
        'polling_unit_agent_id',
        'name',
        'phone_number',
        'nin',
        'voters_card_number',
        'arty_membership_reg_number',
    ];

    public function pollingUnitAgent(): BelongsTo
    {
        return $this->belongsTo(PollingUnitAgent::class);
    }
}
