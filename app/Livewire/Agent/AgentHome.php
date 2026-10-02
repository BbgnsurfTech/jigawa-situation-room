<?php

namespace App\Livewire\Agent;

use App\Models\WardReport;
use Livewire\Component;

class AgentHome extends Component
{
    public array $summary = [];
    public array $recentReports = [];

    public function mount(): void
    {
        $user = auth()->user();

        if (!$user->isFieldAgent()) {
            $this->redirect(route('dashboard'), navigate: true);
            return;
        }

        $this->loadData();
    }

    public function loadData(): void
    {
        $user = auth()->user();
        $base = WardReport::query()->where('agent_user_id', $user->id);

        $this->summary = [
            'total'     => (clone $base)->count(),
            'submitted' => (clone $base)->where('status', 'submitted')->count(),
            'verified'  => (clone $base)->where('status', 'verified')->count(),
            'escalated' => (clone $base)->where('status', 'escalated')->count(),
            'flagged'   => (clone $base)->where('status', 'flagged')->count(),
        ];

        $this->recentReports = (clone $base)
            ->with([
                'ward:id,name',
                'lga:id,name',
                'pollingStation:id,name',
            ])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id'              => $r->id,
                'ward'            => $r->ward?->name ?? '—',
                'lga'             => $r->lga?->name ?? '—',
                'polling_station' => $r->pollingStation?->name ?? '—',
                'status'          => $r->status,
                'submitted_via'   => $r->submitted_via,
                'created_at'      => $r->created_at->format('d M Y H:i'),
                'valid_votes'     => $r->valid_ballot_papers,
                'invalid_votes'   => $r->invalid_ballot_papers,
                'has_security'    => $r->security_present,
                'can_edit'        => $r->isWithinCorrectionWindow(),
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.agent.agent-home')
            ->layout('layouts.app', ['title' => 'My Reports']);
    }
}
