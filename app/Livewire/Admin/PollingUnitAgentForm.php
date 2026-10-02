<?php

namespace App\Livewire\Admin;

use App\Models\Lga;
use App\Models\PollingUnitAgent;
use App\Models\Ward;
use App\Services\PollingUnitAgentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class PollingUnitAgentForm extends Component
{
    use AuthorizesRequests;

    // Section 1: Agent info
    public string $first_name = '';

    public string $surname = '';

    public string $polling_unit = '';

    public string $polling_unit_number = '';

    public ?int $lga_id = null;

    public ?int $ward_id = null;

    public string $state = '';

    public string $nin = '';

    public string $voters_card_number = '';

    public string $bvn = '';

    public string $account_number = '';

    public string $bank_name = '';

    // Leadership roles (multi-select)
    public array $leadership_roles = [];

    // Section 2: Cluster members
    public array $clusterMembers = [];

    public ?int $agentId = null;

    public bool $isSupervisorLocked = false;

    private PollingUnitAgentService $agentService;

    public function boot(PollingUnitAgentService $agentService): void
    {
        $this->agentService = $agentService;
    }

    public function mount(?int $agentId = null): void
    {
        $user = auth()->user();
        abort_unless($user?->isSuperAdmin() || $user?->isSupervisor(), 403);

        if ($agentId) {
            $agent = PollingUnitAgent::with('clusterMembers')->findOrFail($agentId);
            $this->authorize('update', $agent);

            $this->agentId = $agentId;
            $this->first_name = $agent->first_name;
            $this->surname = $agent->surname;
            $this->polling_unit = $agent->polling_unit;
            $this->polling_unit_number = $agent->polling_unit_number;
            $this->lga_id = $agent->lga_id;
            $this->ward_id = $agent->ward_id;
            $this->state = $agent->state;
            $this->nin = $agent->nin;
            $this->voters_card_number = $agent->voters_card_number;
            $this->bvn = $agent->getRawBvn() ?? '';
            $this->account_number = $agent->getRawAccountNumber() ?? '';
            $this->bank_name = $agent->bank_name ?? '';
            $this->leadership_roles = $agent->leadership_roles ?? [];

            $this->clusterMembers = $agent->clusterMembers->map(fn ($m) => [
                'name' => $m->name,
                'phone_number' => $m->phone_number ?? '',
                'nin' => $m->nin ?? '',
                'voters_card_number' => $m->voters_card_number ?? '',
                'arty_membership_reg_number' => $m->arty_membership_reg_number ?? '',
            ])->toArray();
        }

        if ($user->isSupervisor()) {
            $this->isSupervisorLocked = true;
            if (! $this->lga_id) {
                $this->lga_id = $user->lga_id;
            }
        }
    }

    public function updatedLgaId(): void
    {
        $this->ward_id = null;
    }

    public function toggleRole(string $role): void
    {
        if (in_array($role, $this->leadership_roles, true)) {
            $this->leadership_roles = array_values(
                array_filter($this->leadership_roles, fn ($r) => $r !== $role)
            );
        } else {
            $this->leadership_roles[] = $role;
        }
    }

    public function addClusterRow(): void
    {
        if (count($this->clusterMembers) >= PollingUnitAgentService::MAX_CLUSTER_MEMBERS) {
            $this->dispatch('notify', message: 'Maximum of 20 cluster members allowed.', type: 'warning');

            return;
        }
        $this->clusterMembers[] = [
            'name' => '',
            'phone_number' => '',
            'nin' => '',
            'voters_card_number' => '',
            'arty_membership_reg_number' => '',
        ];
    }

    public function removeClusterRow(int $index): void
    {
        unset($this->clusterMembers[$index]);
        $this->clusterMembers = array_values($this->clusterMembers);
    }

    public function save(): void
    {
        $ignoreId = $this->agentId ? ",{$this->agentId}" : '';

        $this->validate([
            'first_name' => 'required|string|max:100',
            'surname' => 'required|string|max:100',
            'polling_unit' => 'required|string|max:255',
            'polling_unit_number' => 'required|string|max:50',
            'lga_id' => 'required|exists:lgas,id',
            'ward_id' => 'required|exists:wards,id',
            'state' => 'required|string|in:'.implode(',', PollingUnitAgentService::NIGERIAN_STATES),
            'nin' => "required|digits:11|unique:polling_unit_agents,nin{$ignoreId}",
            'voters_card_number' => "required|string|max:20|unique:polling_unit_agents,voters_card_number{$ignoreId}",
            'bvn' => 'nullable|digits:11',
            'account_number' => 'nullable|digits:10',
            'bank_name' => 'nullable|string|max:100',
            'leadership_roles' => 'nullable|array',
            'leadership_roles.*' => 'string|in:'.implode(',', array_keys(PollingUnitAgentService::LEADERSHIP_ROLES)),
            'clusterMembers' => 'nullable|array|max:20',
            'clusterMembers.*.name' => 'required_with:clusterMembers.*.phone_number|string|max:255',
            'clusterMembers.*.phone_number' => 'nullable|string|max:20',
            'clusterMembers.*.nin' => 'nullable|digits:11',
            'clusterMembers.*.voters_card_number' => 'nullable|string|max:20',
            'clusterMembers.*.arty_membership_reg_number' => 'nullable|string|max:50',
        ]);

        $existing = $this->agentId ? PollingUnitAgent::findOrFail($this->agentId) : null;
        $this->authorize($existing ? 'update' : 'create', $existing ?? PollingUnitAgent::class);

        $data = [
            'first_name' => $this->first_name,
            'surname' => $this->surname,
            'polling_unit' => $this->polling_unit,
            'polling_unit_number' => $this->polling_unit_number,
            'lga_id' => $this->lga_id,
            'ward_id' => $this->ward_id,
            'state' => $this->state,
            'nin' => $this->nin,
            'voters_card_number' => $this->voters_card_number,
            'bvn' => $this->bvn ?: null,
            'account_number' => $this->account_number ?: null,
            'bank_name' => $this->bank_name ?: null,
            'leadership_roles' => $this->leadership_roles ?: null,
            'cluster_members' => $this->clusterMembers,
        ];

        $this->agentService->save($data, auth()->user(), $existing);

        $this->dispatch('notify', message: 'Polling unit agent saved successfully.');
        $this->redirect(route('admin.pu-agents'), navigate: true);
    }

    public function render()
    {
        $user = auth()->user();

        $lgaQuery = Lga::orderBy('name');
        if ($user->isSupervisor()) {
            $lgaQuery->where('id', $user->lga_id);
        }

        return view('livewire.admin.polling-unit-agent-form', [
            'lgas' => $lgaQuery->get(),
            'wards' => $this->lga_id
                ? Ward::where('lga_id', $this->lga_id)->orderBy('name')->get()
                : collect(),
            'nigerianStates' => PollingUnitAgentService::NIGERIAN_STATES,
            'leadershipRoles' => PollingUnitAgentService::LEADERSHIP_ROLES,
            'maxCluster' => PollingUnitAgentService::MAX_CLUSTER_MEMBERS,
        ])->layout('layouts.app', ['title' => $this->agentId ? 'Edit PU Agent' : 'Register PU Agent']);
    }
}
