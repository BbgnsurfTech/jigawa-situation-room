<?php

namespace App\Livewire\Admin;

use App\Models\Lga;
use App\Models\State;
use App\Services\DeploymentService;
use Livewire\Component;
use Livewire\WithPagination;

class LgaManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $filterStateId = null;
    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $name = '';
    public string $code = '';
    public ?int $state_id = null;
    public string $boundary_geojson_raw = '';

    public bool $isNationalDeployment = false;

    public function mount(): void
    {
        $user = auth()->user();
        // Allow super_admin or state_admin
        abort_unless($user?->isSuperAdmin() || $user?->isStateAdmin(), 403);

        $deploymentService = app(DeploymentService::class);
        $this->isNationalDeployment = $deploymentService->isNational();

        // State admins are locked to their state
        if ($user->isStateAdmin()) {
            $this->filterStateId = $user->state_id;
            $this->state_id = $user->state_id;
        } elseif (!$this->isNationalDeployment) {
            // State-based deployment: lock to configured state
            $this->state_id = $deploymentService->getCurrentStateId();
        }
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id)
    {
        $lga = Lga::findOrFail($id);
        $this->editingId = $id;
        $this->name = $lga->name;
        $this->code = $lga->code;
        $this->state_id = $lga->state_id;
        $this->boundary_geojson_raw = $lga->boundary_geojson ? json_encode($lga->boundary_geojson) : '';
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|alpha|size:3|unique:lgas,code' . ($this->editingId ? ",{$this->editingId}" : ''),
            'state_id' => 'nullable|exists:states,id',
            'boundary_geojson_raw' => 'nullable|json',
        ]);

        $data = [
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'state_id' => $this->state_id,
            'boundary_geojson' => $this->boundary_geojson_raw ? json_decode($this->boundary_geojson_raw, true) : null,
        ];

        if ($this->editingId) {
            Lga::findOrFail($this->editingId)->update($data);
        } else {
            Lga::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('notify', message: 'LGA saved successfully.');
    }

    public function confirmDelete(int $id)
    {
        $lga = Lga::withCount(['wards', 'users', 'wardReports'])->findOrFail($id);

        if ($lga->wards_count > 0) {
            $this->dispatch('notify', message: "Cannot delete {$lga->name} — it has {$lga->wards_count} ward(s).", type: 'error');
            return;
        }
        if ($lga->users_count > 0) {
            $this->dispatch('notify', message: "Cannot delete {$lga->name} — it has {$lga->users_count} user(s) assigned.", type: 'error');
            return;
        }
        if ($lga->ward_reports_count > 0) {
            $this->dispatch('notify', message: "Cannot delete {$lga->name} — it has {$lga->ward_reports_count} report(s).", type: 'error');
            return;
        }

        $lga->delete();
        $this->dispatch('notify', message: 'LGA deleted successfully.');
    }

    private function resetForm()
    {
        $user = auth()->user();
        $this->editingId = null;
        $this->name = '';
        $this->code = '';
        $this->boundary_geojson_raw = '';

        // Reset state_id based on role
        if ($user->isStateAdmin()) {
            $this->state_id = $user->state_id;
        } elseif (!$this->isNationalDeployment) {
            $this->state_id = app(DeploymentService::class)->getCurrentStateId();
        } else {
            $this->state_id = null;
        }
    }

    public function updatedFilterStateId()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $deploymentService = app(DeploymentService::class);

        $query = Lga::withCount('wards')->with('state')->forDeployment();

        // Apply state filter
        if ($this->filterStateId) {
            $query->where('state_id', $this->filterStateId);
        }

        if ($this->search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"));
        }

        // Get available states for filter
        $states = $this->isNationalDeployment
            ? $deploymentService->getStatesForUser($user)
            : collect();

        return view('livewire.admin.lga-management', [
            'lgas' => $query->orderBy('name')->paginate(25),
            'states' => $states,
        ])->layout('layouts.app', ['title' => 'LGA Management']);
    }
}
