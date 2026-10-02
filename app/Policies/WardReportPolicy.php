<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WardReport;

class WardReportPolicy
{
    /**
     * All authenticated roles may request a report listing.
     * Data isolation is enforced by WardReport::scopeForUser() at the query level.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WardReport $report): bool
    {
        if ($user->isSuperAdmin()) return true;

        if ($user->isStateAdmin()) {
            return $report->lga?->state_id === $user->state_id;
        }

        if ($user->isSupervisor()) {
            return $report->lga_id === $user->lga_id;
        }

        // ward_agent / polling_unit_agent — own reports only
        return $report->agent_user_id === $user->id;
    }

    /**
     * Only field agents submit reports; management roles review them.
     */
    public function create(User $user): bool
    {
        return $user->isFieldAgent();
    }

    public function update(User $user, WardReport $report): bool
    {
        if ($user->isSuperAdmin()) return true;

        if ($user->isStateAdmin()) {
            // State admins can correct data for reports within their state
            return $report->lga?->state_id === $user->state_id;
        }

        if ($user->isSupervisor()) {
            // Supervisors can update reports in their own LGA
            return $report->lga_id === $user->lga_id;
        }

        // Field agents: own reports, within the 10-minute correction window
        return $report->agent_user_id === $user->id
            && $report->isWithinCorrectionWindow();
    }

    /**
     * Elevate a report to the next escalation level.
     * State admins can escalate across their state; supervisors within their LGA.
     */
    public function escalate(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isStateAdmin() || $user->isSupervisor();
    }

    /**
     * Mark a report as verified (counted ballots confirmed correct).
     */
    public function verify(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isStateAdmin() || $user->isSupervisor();
    }

    /**
     * Flag a report for review without escalating it.
     */
    public function flag(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isStateAdmin() || $user->isSupervisor();
    }
}
