<?php

namespace App\Livewire\Admin;

use App\Models\Lga;
use App\Models\PollingStation;
use App\Models\Ward;
use Livewire\Component;
use Livewire\WithPagination;

class PollingStationManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $filterLgaId = null;
    public ?int $filterWardId = null;
    public string $filterActive = ''; // '' = all, '1' = active, '0' = inactive
    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $name = '';
    public string $code = '';
    public ?int $lga_id = null;
    public ?int $ward_id = null;
    public ?int $registered_voter_count = null;
    public ?string $gps_latitude = null;
    public ?string $gps_longitude = null;
    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    // Filter cascade: LGA change resets ward filter
    public function updatingFilterLgaId()
    {
        $this->filterWardId = null;
    }

    // Form cascade: LGA change resets ward in form
    public function updatedLgaId()
    {
        $this->ward_id = null;
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id)
    {
        $ps = PollingStation::findOrFail($id);
        $this->editingId = $id;
        $this->name = $ps->name;
        $this->code = $ps->code;
        $this->lga_id = $ps->lga_id;
        $this->ward_id = $ps->ward_id;
        $this->registered_voter_count = $ps->registered_voter_count;
        $this->gps_latitude = $ps->gps_latitude;
        $this->gps_longitude = $ps->gps_longitude;
        $this->is_active = $ps->is_active;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:polling_stations,code' . ($this->editingId ? ",{$this->editingId}" : ''),
            'lga_id' => 'required|exists:lgas,id',
            'ward_id' => 'required|exists:wards,id',
            'registered_voter_count' => 'nullable|integer|min:0',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $data = [
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'lga_id' => $this->lga_id,
            'ward_id' => $this->ward_id,
            'registered_voter_count' => $this->registered_voter_count,
            'gps_latitude' => $this->gps_latitude,
            'gps_longitude' => $this->gps_longitude,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            PollingStation::findOrFail($this->editingId)->update($data);
        } else {
            PollingStation::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('notify', message: 'Polling station saved successfully.');
    }

    public function toggleActive(int $id)
    {
        $ps = PollingStation::findOrFail($id);
        $ps->update(['is_active' => !$ps->is_active]);
        $this->dispatch('notify', message: 'Polling station status updated.');
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = '';
        $this->lga_id = null;
        $this->ward_id = null;
        $this->registered_voter_count = null;
        $this->gps_latitude = null;
        $this->gps_longitude = null;
        $this->is_active = true;
    }

    public function render()
    {
        $query = PollingStation::with('ward', 'lga', 'assignedAgent');

        if ($this->search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"));
        }
        if ($this->filterLgaId) $query->where('lga_id', $this->filterLgaId);
        if ($this->filterWardId) $query->where('ward_id', $this->filterWardId);
        if ($this->filterActive !== '') $query->where('is_active', (bool) $this->filterActive);

        // Filter cascade: wards scoped to selected filter LGA
        $filterWards = $this->filterLgaId
            ? Ward::where('lga_id', $this->filterLgaId)->orderBy('name')->get()
            : collect();

        // Form cascade: wards scoped to selected form LGA
        $formWards = $this->lga_id
            ? Ward::where('lga_id', $this->lga_id)->orderBy('name')->get()
            : collect();

        return view('livewire.admin.polling-station-management', [
            'stations' => $query->orderBy('name')->paginate(25),
            'lgas' => Lga::orderBy('name')->get(),
            'filterWards' => $filterWards,
            'formWards' => $formWards,
        ])->layout('layouts.app', ['title' => 'Polling Stations']);
    }
}
