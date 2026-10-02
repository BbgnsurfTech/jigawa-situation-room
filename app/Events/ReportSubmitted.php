<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\WardReport;

class ReportSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public WardReport $report) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin'),
        ];

        // Also broadcast to the LGA supervisor channel
        if ($this->report->lga_id) {
            $channels[] = new PrivateChannel("lga.{$this->report->lga_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'report.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'report_id' => $this->report->id,
            'agent_name' => $this->report->agent?->full_name,
            'lga_id' => $this->report->lga_id,
            'ward_id' => $this->report->ward_id,
            'status' => $this->report->status,
            'security_present' => $this->report->security_present,
            'submitted_at' => $this->report->created_at?->toISOString(),
        ];
    }
}
