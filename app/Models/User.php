<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'full_name',
        'email',
        'password',
        'phone',
        'role',
        'state_id',
        'lga_id',
        'ward_id',
        'polling_station_id',
        'gender',
        'party_affiliation',
        'device_id',
        'last_login_at',
        'is_active',
        'mfa_enabled',
        'mfa_secret',
        'mfa_otp',
        'mfa_otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
        'mfa_otp',
        'mfa_otp_expires_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'mfa_enabled'       => 'boolean',
        'password'          => 'hashed',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function pollingStation(): BelongsTo
    {
        return $this->belongsTo(PollingStation::class);
    }

    public function wardReports(): HasMany
    {
        return $this->hasMany(WardReport::class, 'agent_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(JesrNotification::class, 'recipient_user_id');
    }

    // -------------------------------------------------------------------------
    // Role helpers
    // -------------------------------------------------------------------------

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isStateAdmin(): bool
    {
        return $this->role === 'state_admin';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isWardAgent(): bool
    {
        return $this->role === 'ward_agent';
    }

    public function isPollingUnitAgent(): bool
    {
        return $this->role === 'polling_unit_agent';
    }

    public function isFieldAgent(): bool
    {
        return in_array($this->role, ['ward_agent', 'polling_unit_agent']);
    }

    // -------------------------------------------------------------------------
    // Query scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByLga($query, int $lgaId)
    {
        return $query->where('lga_id', $lgaId);
    }

    public function scopeByState($query, ?int $stateId)
    {
        if ($stateId) {
            return $query->where('state_id', $stateId);
        }
        return $query;
    }
}
