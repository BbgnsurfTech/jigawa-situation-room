<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\PollingUnitAgent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class PollingUnitAgentObserver
{
    public function created(PollingUnitAgent $agent): void
    {
        $attrs = $agent->getAttributes();
        // Strip sensitive fields from the audit trail
        unset($attrs['bvn'], $attrs['account_number']);
        $this->log('create', $agent, null, $attrs);
    }

    public function updated(PollingUnitAgent $agent): void
    {
        $changes = $agent->getChanges();
        unset($changes['bvn'], $changes['account_number']);
        $this->log('update', $agent, $agent->getOriginal(), $changes);
    }

    private function log(string $action, PollingUnitAgent $agent, ?array $previous, ?array $new): void
    {
        $user = Auth::user();

        AuditLog::create([
            'actor_user_id' => $user?->id,
            'actor_role' => $user?->role ?? 'system',
            'action_type' => $action,
            'entity_type' => 'PollingUnitAgent',
            'entity_id' => $agent->id,
            'previous_value' => $previous,
            'new_value' => $new,
            'ip_address' => Request::ip(),
            'device_info' => Request::userAgent(),
        ]);
    }
}
