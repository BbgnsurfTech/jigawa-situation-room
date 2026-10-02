<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Lga;
use App\Models\User;
use App\Models\Ward;
use App\Models\WardReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private function scopeByRole(Request $request, $query)
    {
        $user = $request->user();
        if ($user->isSupervisor() && $user->lga_id) {
            $query->where('lga_id', $user->lga_id);
        }
        return $query;
    }

    private function getFilters(Request $request): array
    {
        return $request->only(['lga_id', 'ward_id', 'party_id', 'from', 'to', 'submitted_via', 'status']);
    }

    public function summary(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);
        $query = WardReport::query()->forUser($request->user())->filtered($filters);

        $summary = [
            'total_reports' => (clone $query)->count(),
            'submitted' => (clone $query)->where('status', 'submitted')->count(),
            'verified' => (clone $query)->where('status', 'verified')->count(),
            'flagged' => (clone $query)->where('status', 'flagged')->count(),
            'escalated' => (clone $query)->where('status', 'escalated')->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'security_incidents' => (clone $query)->where('security_present', true)->count(),
            'total_valid_votes' => (clone $query)->sum('valid_ballot_papers'),
            'total_invalid_votes' => (clone $query)->sum('invalid_ballot_papers'),
            'total_cast_votes' => (clone $query)->sum('number_cast_votes'),
            'total_agents' => User::where('role', 'ward_agent')->count(),
            'agents_logged_in_today' => User::where('role', 'ward_agent')
                ->whereDate('last_login_at', today())->count(),
            'wards_reporting' => (clone $query)->distinct()->count('ward_id'),
            'total_wards' => Ward::count(),
        ];

        return response()->json($summary);
    }

    public function agentLoginStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = User::whereIn('role', ['ward_agent', 'polling_unit_agent'])->where('is_active', true);

        if ($user->isSuperAdmin()) {
            // super_admin sees all agents; no scope restriction
        } elseif ($user->isStateAdmin() && $user->state_id) {
            $query->where('state_id', $user->state_id);
        } elseif ($user->isSupervisor() && $user->lga_id) {
            $query->where('lga_id', $user->lga_id);
        }

        $loginFilter = $request->input('login_status'); // 'logged_in' | 'not_logged_in'

        $agents = $query->with('lga', 'ward')
            ->when($loginFilter === 'logged_in', fn($q) => $q->whereDate('last_login_at', today()))
            ->when($loginFilter === 'not_logged_in', fn($q) => $q->where(function($q) {
                $q->whereNull('last_login_at')->orWhereDate('last_login_at', '<', today());
            }))
            ->select('id', 'full_name', 'phone', 'gender', 'lga_id', 'ward_id', 'last_login_at', 'is_active')
            ->get();

        $byLga = $agents->groupBy('lga_id')->map(fn($group) => [
            'lga_id' => $group->first()->lga_id,
            'lga_name' => $group->first()->lga?->name ?? 'Unknown',
            'total' => $group->count(),
            'logged_in_today' => $group->filter(fn($a) => $a->last_login_at?->isToday())->count(),
            'not_logged_in' => $group->filter(fn($a) => !$a->last_login_at?->isToday())->count(),
        ])->values();

        return response()->json([
            'total' => $agents->count(),
            'logged_in_today' => $agents->filter(fn($a) => $a->last_login_at?->isToday())->count(),
            'not_logged_in' => $agents->filter(fn($a) => !$a->last_login_at?->isToday())->count(),
            'by_lga' => $byLga,
            'agents' => $agents,
        ]);
    }

    public function byWard(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        $data = WardReport::query()
            ->forUser($request->user())
            ->filtered($filters)
            ->select(
                'ward_id', 'lga_id',
                DB::raw('COUNT(*) as submission_count'),
                DB::raw('MAX(created_at) as latest_report_at'),
                DB::raw('SUM(CASE WHEN status = "verified" THEN 1 ELSE 0 END) as verified_count'),
                DB::raw('SUM(CASE WHEN status = "flagged" THEN 1 ELSE 0 END) as flagged_count'),
                DB::raw('SUM(CASE WHEN status = "escalated" THEN 1 ELSE 0 END) as escalated_count'),
                DB::raw('SUM(valid_ballot_papers) as total_valid_votes'),
                DB::raw('SUM(invalid_ballot_papers) as total_invalid_votes'),
            )
            ->groupBy('ward_id', 'lga_id')
            ->with('ward', 'lga')
            ->orderByDesc('submission_count')
            ->get();

        return response()->json(['data' => $data, 'total_wards_reporting' => $data->count()]);
    }

    public function byLga(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        $data = WardReport::query()
            ->forUser($request->user())
            ->filtered($filters)
            ->select(
                'lga_id',
                DB::raw('COUNT(*) as submission_count'),
                DB::raw('COUNT(DISTINCT ward_id) as wards_reporting'),
                DB::raw('SUM(CASE WHEN status = "escalated" THEN 1 ELSE 0 END) as escalated_count'),
                DB::raw('SUM(CASE WHEN security_present = 1 THEN 1 ELSE 0 END) as security_incidents'),
                DB::raw('SUM(valid_ballot_papers) as total_valid_votes'),
                DB::raw('SUM(invalid_ballot_papers) as total_invalid_votes'),
                DB::raw('SUM(number_cast_votes) as total_cast_votes'),
            )
            ->groupBy('lga_id')
            ->with('lga')
            ->get();

        return response()->json(['data' => $data, 'lgas_reporting' => $data->count()]);
    }

    public function byParty(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        // Group by reporting agent party affiliation (since votes aren't party-coded in reports)
        $data = WardReport::query()
            ->forUser($request->user())
            ->filtered($filters)
            ->join('users', 'ward_reports.agent_user_id', '=', 'users.id')
            ->select(
                'users.party_affiliation as party',
                DB::raw('COUNT(ward_reports.id) as report_count'),
                DB::raw('SUM(ward_reports.valid_ballot_papers) as total_valid_votes'),
                DB::raw('SUM(ward_reports.invalid_ballot_papers) as total_invalid_votes'),
                DB::raw('SUM(ward_reports.number_cast_votes) as total_cast_votes'),
            )
            ->groupBy('users.party_affiliation')
            ->orderByDesc('total_valid_votes')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function bySenatorialDistrict(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        $data = WardReport::query()
            ->forUser($request->user())
            ->filtered($filters)
            ->join('wards', 'ward_reports.ward_id', '=', 'wards.id')
            ->join('senatorial_districts', 'wards.senatorial_district_id', '=', 'senatorial_districts.id')
            ->select(
                'senatorial_districts.id',
                'senatorial_districts.name',
                DB::raw('COUNT(ward_reports.id) as submission_count'),
                DB::raw('SUM(CASE WHEN ward_reports.status = "escalated" THEN 1 ELSE 0 END) as escalations'),
                DB::raw('SUM(CASE WHEN ward_reports.status = "flagged" THEN 1 ELSE 0 END) as flags'),
                DB::raw('SUM(CASE WHEN ward_reports.status = "verified" THEN 1 ELSE 0 END) as verified'),
                DB::raw('SUM(ward_reports.valid_ballot_papers) as total_valid_votes'),
            )
            ->groupBy('senatorial_districts.id', 'senatorial_districts.name')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function candidates(Request $request): JsonResponse
    {
        $query = Candidate::with('party', 'ward', 'lga');

        if ($request->party_id) $query->where('party_id', $request->party_id);
        if ($request->ward_id) $query->where('ward_id', $request->ward_id);
        if ($request->lga_id) $query->where('lga_id', $request->lga_id);

        // Join reported votes by ward to estimate candidate performance
        $candidates = $query->get()->map(function ($candidate) {
            $reports = WardReport::where('ward_id', $candidate->ward_id)
                ->where('status', '!=', 'draft')
                ->get();
            $candidate->total_valid_votes_in_ward = $reports->sum('valid_ballot_papers');
            $candidate->total_reports_in_ward = $reports->count();
            return $candidate;
        });

        return response()->json([
            'data' => $candidates,
            'total' => $candidates->count(),
        ]);
    }

    public function securityIncidents(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        $incidents = WardReport::query()
            ->forUser($request->user())
            ->filtered($filters)
            ->where('security_present', true)
            ->with(['agent', 'lga', 'ward', 'escalation'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'ward' => $report->ward?->name,
                    'lga' => $report->lga?->name,
                    'agent' => $report->agent?->full_name,
                    'security_types' => $report->security_types,
                    'status' => $report->status,
                    'escalation_level' => $report->escalation?->level,
                    'reported_at' => $report->created_at,
                    'is_overdue' => $report->escalation?->isOverdue(),
                ];
            });

        return response()->json([
            'data' => $incidents,
            'total' => $incidents->count(),
            'by_security_type' => $incidents->flatMap(fn($i) => $i['security_types'] ?? [])->countBy()->all(),
        ]);
    }
}
