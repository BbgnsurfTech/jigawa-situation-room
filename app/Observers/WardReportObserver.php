<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\WardReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class WardReportObserver
{
    /**
     * Handle the WardReport "created" event.
     */
    public function created(WardReport $report): void
    {
        $this->log('create', $report, null, $report->getAttributes());
    }

    /**
     * Handle the WardReport "updated" event.
     */
    public function updated(WardReport $report): void
    {
        $this->log('update', $report, $report->getOriginal(), $report->getChanges());
    }

    /**
     * Write an immutable audit log entry for the given action.
     */
    private function log(string $action, WardReport $report, ?array $previous, ?array $new): void
    {
        $user = Auth::user();

        AuditLog::create([
            'actor_user_id' => $user?->id,
            'actor_role'    => $user?->role ?? 'system',
            'action_type'   => $action,
            'entity_type'   => 'WardReport',
            'entity_id'     => $report->id,
            'previous_value' => $previous,
            'new_value'      => $new,
            'ip_address'    => Request::ip(),
            'device_info'   => Request::userAgent(),
        ]);
    }
}
