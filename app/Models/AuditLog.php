<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /**
     * AuditLog only has created_at — no updated_at column.
     */
    public $timestamps = false;

    protected $fillable = [
        'actor_user_id',
        'actor_role',
        'action_type',
        'entity_type',
        'entity_id',
        'previous_value',
        'new_value',
        'reason',
        'ip_address',
        'device_info',
    ];

    protected $casts = [
        'previous_value' => 'array',
        'new_value'      => 'array',
        'created_at'     => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Immutability guards — audit logs are append-only
    // -------------------------------------------------------------------------

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \RuntimeException('Audit logs are immutable and cannot be updated.');
    }

    public function delete(): bool|null
    {
        throw new \RuntimeException('Audit logs are immutable and cannot be deleted.');
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    // -------------------------------------------------------------------------
    // Query scopes
    // -------------------------------------------------------------------------

    public function scopeForEntity($query, string $type, int $id)
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }
}
