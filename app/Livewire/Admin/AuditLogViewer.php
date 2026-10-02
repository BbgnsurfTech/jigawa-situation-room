<?php
namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogViewer extends Component
{
    use WithPagination;

    public string $filterAction = '';
    public string $filterRole = '';
    public ?int $filterUserId = null;
    public string $filterFrom = '';
    public string $filterTo = '';
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterAction() { $this->resetPage(); }
    public function updatingFilterRole() { $this->resetPage(); }

    public function exportCsv()
    {
        $logs = $this->buildQuery()->get();

        $csv = "ID,Actor,Role,Action,Entity,Entity ID,Reason,IP,Date\n";
        foreach ($logs as $log) {
            $csv .= implode(',', [
                $log->id,
                '"' . ($log->actor?->full_name ?? 'System') . '"',
                $log->actor_role,
                $log->action_type,
                $log->entity_type,
                $log->entity_id,
                '"' . str_replace('"', '""', $log->reason ?? '') . '"',
                $log->ip_address,
                $log->created_at->toISOString(),
            ]) . "\n";
        }

        return response()->streamDownload(
            fn() => print($csv),
            'jesr-audit-log-' . now()->format('Y-m-d') . '.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    private function buildQuery()
    {
        $query = AuditLog::with('actor')->orderByDesc('created_at');

        if ($this->filterAction) $query->where('action_type', $this->filterAction);
        if ($this->filterRole) $query->where('actor_role', $this->filterRole);
        if ($this->filterUserId) $query->where('actor_user_id', $this->filterUserId);
        if ($this->filterFrom) $query->whereDate('created_at', '>=', $this->filterFrom);
        if ($this->filterTo) $query->whereDate('created_at', '<=', $this->filterTo);
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('entity_type', 'like', "%{$this->search}%")
                  ->orWhere('reason', 'like', "%{$this->search}%")
                  ->orWhere('ip_address', 'like', "%{$this->search}%");
            });
        }

        return $query;
    }

    public function clearFilters(): void
    {
        $this->reset('filterAction', 'filterRole', 'filterUserId', 'filterFrom', 'filterTo', 'search');
        $this->resetPage();
    }

    public function render()
    {
        // P0: build export URL with current filter state so CSV reflects what the user sees
        $exportParams = array_filter([
            'action_type' => $this->filterAction ?: null,
            'user_id'     => $this->filterUserId  ?: null,
            'from'        => $this->filterFrom     ?: null,
            'to'          => $this->filterTo       ?: null,
        ]);

        return view('livewire.admin.audit-log-viewer', [
            'logs'      => $this->buildQuery()->paginate(30),
            'actions'   => ['create', 'update', 'delete', 'verify', 'flag', 'escalate', 'login', 'logout'],
            'roles'     => ['super_admin', 'supervisor', 'ward_agent'],
            'users'     => \App\Models\User::select('id', 'full_name')->orderBy('full_name')->get(),
            'exportUrl' => route('audit.export', $exportParams),
        ])->layout('layouts.app', ['title' => 'Audit Log']);
    }
}
