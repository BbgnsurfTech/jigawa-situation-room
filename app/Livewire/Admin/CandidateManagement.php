<?php

namespace App\Livewire\Admin;

use App\Models\Candidate;
use App\Models\Lga;
use App\Models\Party;
use App\Models\Ward;
use Livewire\Component;
use Livewire\WithPagination;

class CandidateManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $filterPartyId = null;
    public string $filterPostType = '';
    public ?int $filterLgaId = null;
    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $name = '';
    public ?int $party_id = null;
    public string $post_type = '';
    public ?int $lga_id = null;
    public ?int $ward_id = null;

    protected array $postTypeLabels = [
        'governorship' => 'Governorship',
        'senate' => 'Senate',
        'house_of_reps' => 'House of Reps',
        'state_assembly' => 'State Assembly',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function updatedPostType(): void
    {
        if (in_array($this->post_type, ['governorship', 'senate'])) {
            $this->lga_id = null;
            $this->ward_id = null;
        }
    }

    public function updatedLgaId(): void
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
        $candidate = Candidate::findOrFail($id);
        $this->editingId = $id;
        $this->name = $candidate->name;
        $this->party_id = $candidate->party_id;
        $this->post_type = $candidate->post_type;
        $this->lga_id = $candidate->lga_id;
        $this->ward_id = $candidate->ward_id;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'party_id' => 'required|exists:parties,id',
            'post_type' => 'required|in:governorship,senate,house_of_reps,state_assembly',
        ];

        if (in_array($this->post_type, ['house_of_reps', 'state_assembly'])) {
            $rules['lga_id'] = 'required|exists:lgas,id';
        } else {
            $rules['lga_id'] = 'nullable';
        }

        $rules['ward_id'] = 'nullable|exists:wards,id';

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'party_id' => $this->party_id,
            'post_type' => $this->post_type,
            'lga_id' => in_array($this->post_type, ['governorship', 'senate']) ? null : $this->lga_id,
            'ward_id' => in_array($this->post_type, ['governorship', 'senate']) ? null : $this->ward_id,
        ];

        if ($this->editingId) {
            Candidate::findOrFail($this->editingId)->update($data);
        } else {
            Candidate::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('notify', message: 'Candidate saved successfully.');
    }

    public function confirmDelete(int $id)
    {
        Candidate::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Candidate deleted successfully.');
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->party_id = null;
        $this->post_type = '';
        $this->lga_id = null;
        $this->ward_id = null;
    }

    public function render()
    {
        $query = Candidate::with('party', 'lga', 'ward');

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }
        if ($this->filterPartyId) {
            $query->where('party_id', $this->filterPartyId);
        }
        if ($this->filterPostType) {
            $query->where('post_type', $this->filterPostType);
        }
        if ($this->filterLgaId) {
            $query->where('lga_id', $this->filterLgaId);
        }

        return view('livewire.admin.candidate-management', [
            'candidates' => $query->orderBy('name')->paginate(25),
            'allParties' => Party::orderBy('acronym')->get(),
            'allLgas' => Lga::orderBy('name')->get(),
            'formWards' => $this->lga_id ? Ward::where('lga_id', $this->lga_id)->orderBy('name')->get() : collect(),
            'postTypeLabels' => $this->postTypeLabels,
        ])->layout('layouts.app', ['title' => 'Candidate Management']);
    }
}
