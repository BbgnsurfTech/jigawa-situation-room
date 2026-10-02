<?php

namespace App\Services;

use App\Models\PollingUnitAgent;
use App\Models\PollingUnitClusterMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PollingUnitAgentService
{
    public const MAX_CLUSTER_MEMBERS = 20;

    public const NIGERIAN_STATES = [
        'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa',
        'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo',
        'Ekiti', 'Enugu', 'FCT', 'Gombe', 'Imo', 'Jigawa',
        'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara',
        'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun',
        'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara',
    ];

    public const LEADERSHIP_ROLES = [
        'agent_1' => 'Agent 1',
        'it_agent' => 'I.T Agent',
        'pu_chairman' => 'Polling Unit Chairman',
        'pu_secretary' => 'Polling Unit Secretary',
        'pu_treasurer' => 'Polling Unit Treasurer',
        'pu_youth_leader' => 'Polling Unit Youth Leader',
        'pu_women_leader' => 'Polling Unit Women Leader',
    ];

    public function save(array $data, User $actor, ?PollingUnitAgent $existing = null): PollingUnitAgent
    {
        return DB::transaction(function () use ($data, $existing) {
            $clusterData = $data['cluster_members'] ?? [];
            unset($data['cluster_members']);

            if ($existing) {
                $existing->update($data);
                $agent = $existing->fresh();
            } else {
                $agent = PollingUnitAgent::create($data);
            }

            $this->syncClusterMembers($agent, $clusterData);

            return $agent;
        });
    }

    private function syncClusterMembers(PollingUnitAgent $agent, array $members): void
    {
        $agent->clusterMembers()->delete();

        $capped = array_slice($members, 0, self::MAX_CLUSTER_MEMBERS);

        foreach ($capped as $member) {
            if (empty(trim($member['name'] ?? ''))) {
                continue;
            }

            PollingUnitClusterMember::create([
                'polling_unit_agent_id' => $agent->id,
                'name' => $member['name'],
                'phone_number' => $member['phone_number'] ?? null,
                'nin' => $member['nin'] ?? null,
                'voters_card_number' => $member['voters_card_number'] ?? null,
                'arty_membership_reg_number' => $member['arty_membership_reg_number'] ?? null,
            ]);
        }
    }
}
