<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WardReport extends Model
{
    use HasFactory;
    protected $fillable = [
        'agent_user_id',
        'lga_id',
        'ward_id',
        'polling_station_id',
        'party_agent_id',
        'inec_agent_name',
        'counted_voter_papers',
        'security_present',
        'security_types',
        'voting_closed_time',
        'number_cast_votes',
        'voting_papers_left',
        'valid_ballot_papers',
        'invalid_ballot_papers',
        'remarks',
        'flag_reason',
        'supervisor_note',
        'clarification_request',
        'clarification_sent_at',
        'status',
        'submitted_via',
        'device_id',
        'sync_status',
        'submitted_at_device',
        'ec8a_serial_number',
        'turnout_rate',
        'registered_voter_count_snapshot',
    ];

    protected $casts = [
        'security_present' => 'boolean',
        'security_types' => 'array',
        'submitted_at_device' => 'datetime',
        'counted_voter_papers' => 'integer',
        'number_cast_votes' => 'integer',
        'voting_papers_left' => 'integer',
        'valid_ballot_papers' => 'integer',
        'invalid_ballot_papers' => 'integer',
        'clarification_sent_at' => 'datetime',
        'turnout_rate' => 'float',
        'registered_voter_count_snapshot' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_user_id');
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

    public function partyAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'party_agent_id');
    }

    public function partyResults(): HasMany
    {
        return $this->hasMany(PuPartyResult::class, 'ward_report_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ReportMedia::class, 'report_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'entity_id')
            ->where('entity_type', 'WardReport');
    }

    public function escalation(): HasOne
    {
        return $this->hasOne(Escalation::class, 'report_id');
    }

    // -------------------------------------------------------------------------
    // Business logic
    // -------------------------------------------------------------------------

    public function isWithinCorrectionWindow(): bool
    {
        $windowMinutes = config('jesr.correction_window_minutes', 10);

        return $this->created_at->diffInMinutes(now()) <= $windowMinutes;
    }

    // -------------------------------------------------------------------------
    // Query scopes
    // -------------------------------------------------------------------------

    public function scopeForUser($query, User $user)
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // State admin sees all reports in their assigned state
        if ($user->isStateAdmin()) {
            return $query->whereHas('lga', fn ($q) => $q->where('state_id', $user->state_id));
        }

        if ($user->isSupervisor()) {
            return $query->where('ward_reports.lga_id', $user->lga_id);
        }

        // Both ward_agent and polling_unit_agent see only their own reports
        return $query->where('ward_reports.agent_user_id', $user->id);
    }

    /**
     * Scope reports by deployment context.
     * For state deployment: filters to state-specific reports
     * For national deployment: returns all reports
     */
    public function scopeForDeployment($query)
    {
        $deploymentScope = config('jesr.deployment_scope', 'state');

        if ($deploymentScope === 'national') {
            return $query;
        }

        $stateCode = config('jesr.state_code');
        if (!$stateCode) {
            return $query;
        }

        return $query->whereHas('lga.state', fn ($q) => $q->where('code', $stateCode));
    }

    public function scopeFiltered($query, array $filters)
    {
        if (!empty($filters['lga_id'])) {
            $query->where('ward_reports.lga_id', $filters['lga_id']);
        }

        if (!empty($filters['ward_id'])) {
            $query->where('ward_reports.ward_id', $filters['ward_id']);
        }

        if (!empty($filters['polling_station_id'])) {
            $query->where('ward_reports.polling_station_id', $filters['polling_station_id']);
        }

        if (!empty($filters['status'])) {
            $query->whereIn('ward_reports.status', (array) $filters['status']);
        }

        if (!empty($filters['submitted_via'])) {
            $query->whereIn('ward_reports.submitted_via', (array) $filters['submitted_via']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('ward_reports.created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('ward_reports.created_at', '<=', $filters['to']);
        }

        return $query;
    }
}
