<?php
namespace App\Livewire\Admin;

use App\Models\Lga;
use App\Models\PollingStation;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use App\Services\DeploymentService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterRole = '';
    public ?int $filterStateId = null;
    public ?int $filterLgaId = null;
    public bool $showModal = false;
    public ?int $editingUserId = null;

    // Form fields
    public string $full_name = '';
    public string $phone = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'ward_agent';
    public ?int $state_id = null;
    public ?int $lga_id = null;
    public ?int $ward_id = null;
    public ?int $polling_station_id = null;
    public string $gender = 'male';
    public bool $is_active = true;
    public bool $mfa_enabled = false;

    public bool $isNationalDeployment = false;

    public function mount(): void
    {
        $user = auth()->user();
        // SEC-06: Allow super_admin or state_admin
        abort_unless($user?->isSuperAdmin() || $user?->isStateAdmin(), 403);

        $deploymentService = app(DeploymentService::class);
        $this->isNationalDeployment = $deploymentService->isNational();

        // State admins are locked to their state
        if ($user->isStateAdmin()) {
            $this->filterStateId = $user->state_id;
            $this->state_id = $user->state_id;
        } elseif (!$this->isNationalDeployment) {
            // State-based deployment: pre-select configured state
            $this->state_id = $deploymentService->getCurrentStateId();
        }
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $userId)
    {
        $user = User::findOrFail($userId);
        $this->editingUserId = $userId;
        $this->full_name = $user->full_name;
        $this->phone = $user->phone;
        $this->email = $user->email ?? '';
        $this->role = $user->role;
        $this->state_id = $user->state_id;
        $this->lga_id = $user->lga_id;
        $this->ward_id = $user->ward_id;
        $this->polling_station_id = $user->polling_station_id;
        $this->gender = $user->gender ?? 'male';
        $this->is_active = $user->is_active;
        $this->mfa_enabled = $user->mfa_enabled;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:15|unique:users,phone' . ($this->editingUserId ? ",{$this->editingUserId}" : ''),
            'role' => 'required|in:super_admin,state_admin,supervisor,ward_agent,polling_unit_agent',
            'gender' => 'required|in:male,female',
            'state_id' => 'nullable|exists:states,id',
        ];
        if (!$this->editingUserId) {
            $rules['password'] = 'required|string|min:8';
        }
        $this->validate($rules);

        $data = [
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email ?: null,
            'role' => $this->role,
            'state_id' => $this->state_id,
            'lga_id' => $this->lga_id,
            'ward_id' => $this->ward_id,
            'polling_station_id' => $this->role === 'polling_unit_agent' ? $this->polling_station_id : null,
            'gender' => $this->gender,
            'is_active' => $this->is_active,
            'mfa_enabled' => $this->mfa_enabled,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        // Sync Spatie role
        $user->syncRoles([$this->role]);

        // Track PUA assignment on the polling station
        if ($this->role === 'polling_unit_agent' && $this->polling_station_id) {
            PollingStation::where('id', $this->polling_station_id)
                ->update(['assigned_pua_id' => $user->id]);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('notify', message: 'User saved successfully.');
    }

    public function toggleActive(int $userId)
    {
        $user = User::findOrFail($userId);
        $user->update(['is_active' => !$user->is_active]);
        $this->dispatch('notify', message: 'User status updated.');
    }

    private function resetForm()
    {
        $currentUser = auth()->user();
        $this->editingUserId = null;
        $this->full_name = $this->phone = $this->email = $this->password = '';
        $this->role = 'ward_agent';
        $this->lga_id = $this->ward_id = null;
        $this->polling_station_id = null;
        $this->gender = 'male';
        $this->is_active = true;
        $this->mfa_enabled = false;

        // Reset state_id based on role
        if ($currentUser->isStateAdmin()) {
            $this->state_id = $currentUser->state_id;
        } elseif (!$this->isNationalDeployment) {
            $this->state_id = app(DeploymentService::class)->getCurrentStateId();
        } else {
            $this->state_id = null;
        }
    }

    public function updatedFilterStateId()
    {
        $this->filterLgaId = null;
        $this->resetPage();
    }

    public function updatedStateId()
    {
        // Reset dependent fields when state changes
        $this->lga_id = null;
        $this->ward_id = null;
        $this->polling_station_id = null;
    }

    public function render()
    {
        $currentUser = auth()->user();
        $deploymentService = app(DeploymentService::class);

        $query = User::with('lga', 'ward', 'state');

        // Apply state filter
        if ($this->filterStateId) {
            $query->where('state_id', $this->filterStateId);
        }

        if ($this->search) {
            $query->where(fn($q) => $q->where('full_name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%"));
        }
        if ($this->filterRole) $query->where('role', $this->filterRole);
        if ($this->filterLgaId) $query->where('lga_id', $this->filterLgaId);

        $pollingStations = $this->ward_id
            ? PollingStation::where('ward_id', $this->ward_id)->active()->orderBy('name')->get()
            : collect();

        // Get LGAs filtered by state
        $lgaQuery = Lga::query()->forDeployment();
        if ($this->state_id) {
            $lgaQuery->where('state_id', $this->state_id);
        }
        if ($this->filterStateId) {
            $lgaQuery->where('state_id', $this->filterStateId);
        }

        // Get states for dropdown
        $states = $this->isNationalDeployment
            ? $deploymentService->getStatesForUser($currentUser)
            : collect();

        return view('livewire.admin.user-management', [
            'users'           => $query->paginate(25),
            'states'          => $states,
            'lgas'            => $lgaQuery->orderBy('name')->get(),
            'wards'           => $this->lga_id ? Ward::where('lga_id', $this->lga_id)->orderBy('name')->get() : collect(),
            'pollingStations' => $pollingStations,
        ])->layout('layouts.app', ['title' => 'User Management']);
    }
}
