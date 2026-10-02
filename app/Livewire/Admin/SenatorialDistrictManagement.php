<?php

namespace App\Livewire\Admin;

use App\Models\SenatorialDistrict;
use Livewire\Component;
use Livewire\WithPagination;

class SenatorialDistrictManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $name = '';

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
        $district = SenatorialDistrict::findOrFail($id);
        $this->editingId = $id;
        $this->name = $district->name;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:senatorial_districts,name' . ($this->editingId ? ",{$this->editingId}" : ''),
        ]);

        $data = ['name' => $this->name];

        if ($this->editingId) {
            SenatorialDistrict::findOrFail($this->editingId)->update($data);
        } else {
            SenatorialDistrict::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('notify', message: 'Senatorial district saved successfully.');
    }

    public function confirmDelete(int $id)
    {
        $district = SenatorialDistrict::withCount('wards')->findOrFail($id);

        if ($district->wards_count > 0) {
            $this->dispatch('notify', message: "Cannot delete {$district->name} — it has {$district->wards_count} ward(s).", type: 'error');
            return;
        }

        $district->delete();
        $this->dispatch('notify', message: 'Senatorial district deleted successfully.');
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
    }

    public function render()
    {
        $query = SenatorialDistrict::withCount('wards');

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        return view('livewire.admin.senatorial-district-management', [
            'districts' => $query->orderBy('name')->paginate(25),
        ])->layout('layouts.app', ['title' => 'Senatorial Districts']);
    }
}
