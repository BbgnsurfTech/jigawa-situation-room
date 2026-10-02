<?php

namespace App\Livewire\Admin;

use App\Models\Lga;
use App\Models\SenatorialDistrict;
use App\Models\Ward;
use Livewire\Component;
use Livewire\WithPagination;

class WardManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $filterLgaId = null;
    public ?int $filterDistrictId = null;
    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $name = '';
    public string $code = '';
    public ?int $lga_id = null;
    public ?int $senatorial_district_id = null;
    public string $boundary_geojson_raw = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id)
    {
        $ward = Ward::findOrFail($id);
        $this->editingId = $id;
        $this->name = $ward->name;
        $this->code = $ward->code;
        $this->lga_id = $ward->lga_id;
        $this->senatorial_district_id = $ward->senatorial_district_id;
        $this->boundary_geojson_raw = $ward->boundary_geojson ? json_encode($ward->boundary_geojson) : '';
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:wards,code' . ($this->editingId ? ",{$this->editingId}" : ''),
            'lga_id' => 'required|exists:lgas,id',
            'senatorial_district_id' => 'nullable|exists:senatorial_districts,id',
            'boundary_geojson_raw' => 'nullable|json',
        ]);

        $data = [
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'lga_id' => $this->lga_id,
            'senatorial_district_id' => $this->senatorial_district_id,
            'boundary_geojson' => $this->boundary_geojson_raw ? json_decode($this->boundary_geojson_raw, true) : null,
        ];

        if ($this->editingId) {
            Ward::findOrFail($this->editingId)->update($data);
        } else {
            Ward::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('notify', message: 'Ward saved successfully.');
    }

    public function confirmDelete(int $id)
    {
        $ward = Ward::withCount(['users', 'wardReports', 'pollingStations'])->findOrFail($id);

        if ($ward->users_count > 0) {
            $this->dispatch('notify', message: "Cannot delete {$ward->name} — it has {$ward->users_count} user(s) assigned.", type: 'error');
            return;
        }
        if ($ward->ward_reports_count > 0) {
            $this->dispatch('notify', message: "Cannot delete {$ward->name} — it has {$ward->ward_reports_count} report(s).", type: 'error');
            return;
        }
        if ($ward->polling_stations_count > 0) {
            $this->dispatch('notify', message: "Cannot delete {$ward->name} — it has {$ward->polling_stations_count} polling station(s).", type: 'error');
            return;
        }

        $ward->delete();
        $this->dispatch('notify', message: 'Ward deleted successfully.');
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = '';
        $this->lga_id = null;
        $this->senatorial_district_id = null;
        $this->boundary_geojson_raw = '';
    }

    public function render()
    {
        $query = Ward::with('lga', 'senatorialDistrict')->withCount('pollingStations');

        if ($this->search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"));
        }
        if ($this->filterLgaId) $query->where('lga_id', $this->filterLgaId);
        if ($this->filterDistrictId) $query->where('senatorial_district_id', $this->filterDistrictId);

        return view('livewire.admin.ward-management', [
            'wards' => $query->orderBy('name')->paginate(25),
            'lgas' => Lga::orderBy('name')->get(),
            'districts' => SenatorialDistrict::orderBy('name')->get(),
        ])->layout('layouts.app', ['title' => 'Ward Management']);
    }
}
