<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollingUnitAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'surname',
        'polling_unit',
        'polling_unit_number',
        'ward_id',
        'lga_id',
        'state',
        'nin',
        'voters_card_number',
        'bvn',
        'account_number',
        'bank_name',
        'leadership_roles',
    ];

    protected $casts = [
        'leadership_roles' => 'array',
    ];

    // BVN always masked; raw value only via getRawBvn()
    public function getBvnAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return '***-***-'.substr($value, -4);
    }

    // Account number always masked; raw value only via getRawAccountNumber()
    public function getAccountNumberAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return str_repeat('*', strlen($value) - 4).substr($value, -4);
    }

    public function getRawBvn(): ?string
    {
        return $this->attributes['bvn'] ?? null;
    }

    public function getRawAccountNumber(): ?string
    {
        return $this->attributes['account_number'] ?? null;
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function clusterMembers(): HasMany
    {
        return $this->hasMany(PollingUnitClusterMember::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'entity_id')
            ->where('entity_type', 'PollingUnitAgent');
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }
        if ($user->isSupervisor()) {
            return $query->where('lga_id', $user->lga_id);
        }

        return $query->whereRaw('1=0');
    }

    public function scopeFiltered($query, array $filters)
    {
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$s}%")
                ->orWhere('surname', 'like', "%{$s}%")
                ->orWhere('nin', 'like', "%{$s}%")
                ->orWhere('polling_unit', 'like', "%{$s}%")
            );
        }
        if (! empty($filters['lga_id'])) {
            $query->where('lga_id', $filters['lga_id']);
        }
        if (! empty($filters['ward_id'])) {
            $query->where('ward_id', $filters['ward_id']);
        }

        return $query;
    }
}
