<?php
namespace App\Livewire\Dashboard;

use App\Models\Lga;
use App\Models\Party;
use App\Models\PollingStation;
use App\Models\PuPartyResult;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use App\Models\WardReport;
use App\Services\DeploymentService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Overview extends Component
{
    public array $summary = [];
    public array $byLga = [];
    public array $charts = [];
    public array $mapPins = [];
    public array $partyLeaderboard = [];

    public ?int $filterStateId = null;
    public ?int $filterLgaId = null;
    public string $filterStatus = '';
    public string $filterVia = '';
    public string $filterFrom = '';
    public string $filterTo = '';

    public bool $isNationalDeployment = false;

    public function mount()
    {
        $user = auth()->user();

        if ($user->isFieldAgent()) {
            $this->redirect(route('agent.home'), navigate: true);
            return;
        }

        $deploymentService = app(DeploymentService::class);
        $this->isNationalDeployment = $deploymentService->isNational();

        if ($this->isNationalDeployment && $user->isStateAdmin()) {
            $this->filterStateId = $user->state_id;
        }

        $this->loadData();
    }

    public function loadData(): void
    {
        $user = auth()->user();
        $base = WardReport::query()->forUser($user)->forDeployment();

        // State filter for national deployment
        if ($this->filterStateId) {
            $base->whereHas('lga', fn ($q) => $q->where('state_id', $this->filterStateId));
        }
        if ($this->filterLgaId)
            $base->where('ward_reports.lga_id', $this->filterLgaId);
        if ($this->filterStatus)
            $base->where('ward_reports.status', $this->filterStatus);
        if ($this->filterVia)
            $base->where('ward_reports.submitted_via', $this->filterVia);
        if ($this->filterFrom)
            $base->whereDate('ward_reports.created_at', '>=', $this->filterFrom);
        if ($this->filterTo)
            $base->whereDate('ward_reports.created_at', '<=', $this->filterTo);

        // Build ward/PU/agent queries with deployment scope
        $wardQuery = Ward::query()->forDeployment();
        $puQuery = PollingStation::active()->forDeployment();
        $agentQuery = User::whereIn('role', ['ward_agent', 'polling_unit_agent']);

        // Apply state filter to entity queries
        if ($this->filterStateId) {
            $wardQuery->whereHas('lga', fn ($q) => $q->where('state_id', $this->filterStateId));
            $puQuery->whereHas('lga', fn ($q) => $q->where('state_id', $this->filterStateId));
            $agentQuery->where('state_id', $this->filterStateId);
        }

        $this->summary = [
            'total_reports' => (clone $base)->count(),
            'submitted' => (clone $base)->where('status', 'submitted')->count(),
            'verified' => (clone $base)->where('status', 'verified')->count(),
            'flagged' => (clone $base)->where('status', 'flagged')->count(),
            'escalated' => (clone $base)->where('status', 'escalated')->count(),
            'security_incidents' => (clone $base)->where('security_present', true)->count(),
            'total_valid_votes' => (clone $base)->sum('valid_ballot_papers'),
            'total_cast_votes' => (clone $base)->sum('number_cast_votes'),
            'wards_reporting' => (clone $base)->distinct()->count('ward_id'),
            'total_wards' => (clone $wardQuery)->count(),
            'total_polling_stations' => (clone $puQuery)->count(),
            'pus_reporting' => (clone $base)->whereNotNull('polling_station_id')->distinct()->count('polling_station_id'),
            'total_agents' => (clone $agentQuery)->count(),
            'agents_logged_in_today' => (clone $agentQuery)
                ->whereDate('last_login_at', today())->count(),
        ];

        // By LGA table (exclude boundary_geojson to reduce payload size)
        $this->byLga = (clone $base)
            ->select('lga_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(CASE WHEN status="escalated" THEN 1 ELSE 0 END) as escalated'))
            ->groupBy('lga_id')
            ->with(['lga' => fn ($q) => $q->select('id', 'name', 'code', 'state_id')])
            ->get()
            ->toArray();

        // Charts data
        $this->charts = [
            'byStatus' => $this->buildStatusChart($base),
            'byChannel' => $this->buildChannelChart($base),
            'bySenatorial' => $this->buildSenatorialChart($base),
            'timeline' => $this->buildTimelineChart($base),
            'agentGender' => $this->buildGenderChart($user),
            'partyVotes' => $this->buildPartyVotesChart($base),
        ];

        // Party leaderboard
        $this->partyLeaderboard = $this->buildPartyLeaderboard($base);

        // Map pins
        $this->buildMapPins($base);

        // P1: dispatch browser event so Alpine can call chart.updateSeries() without DOM recreation
        $this->dispatch('charts-updated', charts: $this->charts, mapPins: $this->mapPins, partyLeaderboard: $this->partyLeaderboard);
    }

    public function clearFilters(): void
    {
        $user = auth()->user();

        // For state_admin, keep their state filter locked
        if ($user->isStateAdmin()) {
            $this->reset('filterLgaId', 'filterStatus', 'filterVia', 'filterFrom', 'filterTo');
        } else {
            $this->reset('filterStateId', 'filterLgaId', 'filterStatus', 'filterVia', 'filterFrom', 'filterTo');
        }

        $this->loadData();
    }

    private function buildStatusChart($base): array
    {
        $rows = (clone $base)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $labels = array_map('ucfirst', array_keys($rows));
        return ['labels' => $labels, 'series' => array_values($rows)];
    }

    private function buildChannelChart($base): array
    {
        $rows = (clone $base)
            ->select('submitted_via', DB::raw('COUNT(*) as count'))
            ->groupBy('submitted_via')
            ->pluck('count', 'submitted_via')
            ->toArray();

        $labels = array_map('strtoupper', array_keys($rows));
        return ['labels' => $labels, 'series' => array_values($rows)];
    }

    private function buildSenatorialChart($base): array
    {
        $rows = (clone $base)
            ->join('wards', 'ward_reports.ward_id', '=', 'wards.id')
            ->join('senatorial_districts', 'wards.senatorial_district_id', '=', 'senatorial_districts.id')
            ->select('senatorial_districts.name', DB::raw('COUNT(*) as count'))
            ->groupBy('senatorial_districts.id', 'senatorial_districts.name')
            ->orderByDesc('count')
            ->get();

        return [
            'labels' => $rows->pluck('name')->toArray(),
            'series' => $rows->pluck('count')->toArray(),
        ];
    }

    private function buildTimelineChart($base): array
    {
        $rows = (clone $base)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(13))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day')
            ->toArray();

        $labels = [];
        $series = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('d M');
            $series[] = $rows[$day] ?? 0;
        }
        return ['labels' => $labels, 'series' => $series];
    }

    private function buildGenderChart(User $user): array
    {
        $query = User::whereIn('role', ['ward_agent', 'polling_unit_agent']);

        // Apply deployment scope and role-based filters
        if ($user->isSupervisor()) {
            $query->where('lga_id', $user->lga_id);
        } elseif ($user->isStateAdmin()) {
            $query->where('state_id', $user->state_id);
        } elseif ($this->filterStateId) {
            $query->where('state_id', $this->filterStateId);
        }

        $rows = $query->select('gender', DB::raw('COUNT(*) as count'))
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();

        $map = ['male' => 'Male', 'female' => 'Female', '' => 'Unspecified', null => 'Unspecified'];
        $labels = [];
        $series = [];
        foreach ($rows as $gender => $count) {
            $labels[] = $map[$gender] ?? ucfirst($gender ?: 'Unspecified');
            $series[] = $count;
        }
        return ['labels' => $labels, 'series' => $series];
    }

    private function buildPartyVotesChart($base): array
    {
        $reportIds = (clone $base)->pluck('id');

        $rows = PuPartyResult::whereIn('ward_report_id', $reportIds)
            ->select('party_name', DB::raw('SUM(votes_scored) as total_votes'))
            ->groupBy('party_name')
            ->orderByDesc('total_votes')
            ->get();

        if ($rows->isEmpty()) {
            return ['labels' => [], 'series' => [], 'colours' => []];
        }

        $partyColours = Party::pluck('colour_hex', 'acronym')->toArray();

        return [
            'labels' => $rows->pluck('party_name')->toArray(),
            'series' => $rows->pluck('total_votes')->map(fn($v) => (int) $v)->toArray(),
            'colours' => $rows->pluck('party_name')->map(fn($name) => $partyColours[$name] ?? '#9ca3af')->toArray(),
        ];
    }

    private function buildPartyLeaderboard($base): array
    {
        $reportIds = (clone $base)->pluck('id');

        $rows = PuPartyResult::whereIn('ward_report_id', $reportIds)
            ->select('party_name', DB::raw('SUM(votes_scored) as total_votes'))
            ->groupBy('party_name')
            ->orderByDesc('total_votes')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $parties = Party::all()->keyBy('acronym');
        $maxVotes = $rows->max('total_votes');

        return $rows->map(function ($row) use ($parties, $maxVotes) {
            $party = $parties->get($row->party_name);
            return [
                'acronym' => $row->party_name,
                'name' => $party?->name ?? $row->party_name,
                'colour' => $party?->colour_hex ?? '#9ca3af',
                'logo_url' => $party?->logo_url,
                'votes' => (int) $row->total_votes,
                'percentage' => $maxVotes > 0 ? round(($row->total_votes / $maxVotes) * 100, 1) : 0,
            ];
        })->toArray();
    }

    private function buildMapPins($base): void
    {
        $reportedPus = (clone $base)
            ->whereNotNull('polling_station_id')
            ->select('polling_station_id', DB::raw('MAX(status) as latest_status'))
            ->groupBy('polling_station_id')
            ->pluck('latest_status', 'polling_station_id');

        $query = PollingStation::active()
            ->forDeployment()
            ->whereNotNull('gps_latitude')
            ->whereNotNull('gps_longitude')
            ->with(['ward:id,name', 'lga:id,name']);

        // Apply state filter for national deployment
        if ($this->filterStateId) {
            $query->whereHas('lga', fn ($q) => $q->where('state_id', $this->filterStateId));
        }

        if ($this->filterLgaId) {
            $query->where('lga_id', $this->filterLgaId);
        }

        $this->mapPins = $query->get()->map(function ($pu) use ($reportedPus) {
            return [
                'id' => $pu->id,
                'name' => $pu->name,
                'code' => $pu->code,
                'lat' => (float) $pu->gps_latitude,
                'lng' => (float) $pu->gps_longitude,
                'ward' => $pu->ward?->name ?? '',
                'lga' => $pu->lga?->name ?? '',
                'voters' => $pu->registered_voter_count ?? 0,
                'reported' => $reportedPus->has($pu->id),
                'status' => $reportedPus->get($pu->id, null),
            ];
        })->toArray();
    }

    public function updatedFilterStateId()
    {
        // Reset LGA filter when state changes
        $this->filterLgaId = null;
        $this->loadData();
    }
    public function updatedFilterLgaId()
    {
        $this->loadData();
    }
    public function updatedFilterStatus()
    {
        $this->loadData();
    }
    public function updatedFilterVia()
    {
        $this->loadData();
    }
    public function updatedFilterFrom()
    {
        $this->loadData();
    }
    public function updatedFilterTo()
    {
        $this->loadData();
    }

    public function render()
    {
        $user = auth()->user();
        $deploymentService = app(DeploymentService::class);

        // Build LGA query based on state filter or deployment scope
        $lgaQuery = Lga::query()->forDeployment();
        if ($this->filterStateId) {
            $lgaQuery->where('state_id', $this->filterStateId);
        }

        // Get states for filter dropdown (national deployment only)
        $states = collect();
        if ($this->isNationalDeployment) {
            $states = $deploymentService->getStatesForUser($user);
        }

        return view('livewire.dashboard.overview', [
            'lgas' => $lgaQuery->orderBy('name')->get(),
            'states' => $states,
        ])->layout('layouts.app', ['title' => 'Dashboard Overview']);
    }
}
