<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Lga;
use App\Models\PollingUnitAgent;
use App\Models\Ward;
use App\Services\PollingUnitAgentExportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class PollingUnitAgentIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public ?int $filterLgaId = null;

    public ?int $filterWardId = null;

    private PollingUnitAgentExportService $exportService;

    public function boot(PollingUnitAgentExportService $exportService): void
    {
        $this->exportService = $exportService;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin() || auth()->user()?->isSupervisor(), 403);

        if (auth()->user()->isSupervisor()) {
            $this->filterLgaId = auth()->user()->lga_id;
        }
    }

    public function updatedFilterLgaId(): void
    {
        $this->filterWardId = null;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $agentId): void
    {
        $agent = PollingUnitAgent::findOrFail($agentId);
        $this->authorize('delete', $agent);

        $attrs = $agent->getAttributes();
        unset($attrs['bvn'], $attrs['account_number']);

        $agent->delete();

        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'actor_role' => auth()->user()->role,
            'action_type' => 'delete',
            'entity_type' => 'PollingUnitAgent',
            'entity_id' => $agentId,
            'previous_value' => $attrs,
            'new_value' => null,
            'ip_address' => request()->ip(),
            'device_info' => request()->userAgent(),
        ]);

        $this->dispatch('notify', message: 'Agent record deleted.');
    }

    public function exportCsv(): mixed
    {
        $this->authorize('export', PollingUnitAgent::class);

        return $this->exportService->exportCsv($this->buildFilters(), auth()->user());
    }

    public function exportPdf(): mixed
    {
        $this->authorize('export', PollingUnitAgent::class);

        return $this->exportService->exportPdf($this->buildFilters(), auth()->user());
    }

    private function buildFilters(): array
    {
        return [
            'search' => $this->search,
            'lga_id' => $this->filterLgaId,
            'ward_id' => $this->filterWardId,
        ];
    }

    public function render()
    {
        $user = auth()->user();

        $agents = PollingUnitAgent::with(['lga', 'ward'])
            ->forUser($user)
            ->filtered($this->buildFilters())
            ->orderBy('surname')
            ->paginate(25);

        $lgaQuery = Lga::orderBy('name');
        if ($user->isSupervisor()) {
            $lgaQuery->where('id', $user->lga_id);
        }

        return view('livewire.admin.polling-unit-agent-index', [
            'agents' => $agents,
            'lgas' => $lgaQuery->get(),
            'wards' => $this->filterLgaId
                ? Ward::where('lga_id', $this->filterLgaId)->orderBy('name')->get()
                : collect(),
            'isSuperAdmin' => $user->isSuperAdmin(),
            'isSupervisor' => $user->isSupervisor(),
        ])->layout('layouts.app', ['title' => 'PU Agent Register']);
    }
}
