<?php

use Illuminate\Support\Facades\Broadcast;

// Super Admin channel — only super admins can subscribe
Broadcast::channel('admin', function ($user) {
    return $user->isSuperAdmin();
});

// LGA-scoped channel — supervisors for that LGA + super admins
Broadcast::channel('lga.{lgaId}', function ($user, int $lgaId) {
    if ($user->isSuperAdmin()) return true;
    if ($user->isSupervisor() && $user->lga_id === $lgaId) return true;
    return false;
});

// Agent's own channel — only the agent themselves
Broadcast::channel('agent.{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId;
});
