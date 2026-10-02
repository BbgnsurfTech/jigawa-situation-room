<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Escalation;

class ReportEscalated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Escalation $escalation) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
            new PrivateChannel("lga.{$this->escalation->report->lga_id}"),
        ];
    }

    public function broadcastAs(): string { return 'report.escalated'; }

    public function broadcastWith(): array
    {
        return [
            'escalation_id' => $this->escalation->id,
            'report_id' => $this->escalation->report_id,
            'level' => $this->escalation->level,
            'reason' => $this->escalation->reason,
            'escalated_at' => $this->escalation->created_at?->toISOString(),
        ];
    }
}
