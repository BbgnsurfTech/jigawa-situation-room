<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JesrNotification extends Model
{
    /**
     * JesrNotification does not use updated_at.
     */
    public $timestamps = false;

    protected $fillable = [
        'recipient_user_id',
        'type',
        'message',
        'related_entity_type',
        'related_entity_id',
        'is_read',
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'created_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    // -------------------------------------------------------------------------
    // Query scopes
    // -------------------------------------------------------------------------

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
