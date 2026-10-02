<?php

namespace App\Livewire\Admin;

use App\Models\Party;
use App\Models\PuPartyResult;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class PartyManagement extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $name = '';
    public string $acronym = '';
    public string $colour_hex = '#16a34a';
    public $logo;
    public ?string $existingLogoUrl = null;

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
        $party = Party::findOrFail($id);
        $this->editingId = $id;
        $this->name = $party->name;
        $this->acronym = $party->acronym;
        $this->colour_hex = $party->colour_hex ?? '#16a34a';
        $this->existingLogoUrl = $party->logo_url;
        $this->logo = null;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'acronym' => 'required|string|max:10|unique:parties,acronym' . ($this->editingId ? ",{$this->editingId}" : ''),
            'colour_hex' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $this->name,
            'acronym' => strtoupper($this->acronym),
            'colour_hex' => $this->colour_hex,
        ];

        if ($this->logo) {
            $data['logo_path'] = $this->logo->store('party-logos', 'public');
        }

        if ($this->editingId) {
            Party::findOrFail($this->editingId)->update($data);
        } else {
            Party::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('notify', message: 'Party saved successfully.');
    }

    public function confirmDelete(int $id)
    {
        $party = Party::withCount('candidates')->findOrFail($id);

        if ($party->candidates_count > 0) {
            $this->dispatch('notify', message: "Cannot delete {$party->acronym} — it has {$party->candidates_count} candidate(s).", type: 'error');
            return;
        }

        $voteCount = PuPartyResult::where('party_name', $party->acronym)->count();
        if ($voteCount > 0) {
            $this->dispatch('notify', message: "Cannot delete {$party->acronym} — it has {$voteCount} vote record(s).", type: 'error');
            return;
        }

        if ($party->logo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($party->logo_path);
        }

        $party->delete();
        $this->dispatch('notify', message: 'Party deleted successfully.');
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->acronym = '';
        $this->colour_hex = '#16a34a';
        $this->logo = null;
        $this->existingLogoUrl = null;
    }

    public function render()
    {
        $query = Party::withCount('candidates');

        if ($this->search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('acronym', 'like', "%{$this->search}%"));
        }

        return view('livewire.admin.party-management', [
            'parties' => $query->orderBy('acronym')->paginate(25),
        ])->layout('layouts.app', ['title' => 'Party Management']);
    }
}
