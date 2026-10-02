<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class AgentLoggedIn implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $agent) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
            new PrivateChannel("lga.{$this->agent->lga_id}"),
        ];
    }

    public function broadcastAs(): string { return 'agent.logged_in'; }

    public function broadcastWith(): array
    {
        return [
            'agent_id' => $this->agent->id,
            'agent_name' => $this->agent->full_name,
            'lga_id' => $this->agent->lga_id,
            'ward_id' => $this->agent->ward_id,
            'logged_in_at' => $this->agent->last_login_at?->toISOString(),
        ];
    }
}
