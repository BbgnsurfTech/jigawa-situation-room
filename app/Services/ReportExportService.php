<?php
namespace App\Services;

use App\Models\Candidate;
use App\Models\User;
use App\Models\WardReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public const REPORT_TYPES = [
        'agents_by_ward',
        'by_gender',
        'by_ward',
        'by_lga',
        'by_party',
        'by_senatorial_district',
        'candidate_distribution',
    ];

    public function exportPdf(string $type, array $filters, \App\Models\User $generator): \Illuminate\Http\Response
    {
        $data = $this->getData($type, $filters, $generator);
        $title = $this->getReportTitle($type);

        $pdf = Pdf::loadView('pdf.report', [
            'title' => $title,
            'type' => $type,
            'data' => $data,
            'filters' => $filters,
            'generator' => $generator,
            'generated_at' => now()->format('d M Y, H:i:s'),
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download("jesr-{$type}-" . now()->format('Y-m-d') . ".pdf");
    }

    public function exportCsv(string $type, array $filters, User $generator): StreamedResponse
    {
        $data = $this->getData($type, $filters, $generator);
        $title = $this->getReportTitle($type);
        $filename = "jesr-{$type}-" . now()->format('Y-m-d') . ".csv";

        return response()->streamDownload(function () use ($data, $title, $type) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['JIGAWA STATE ELECTION SITUATION ROOM (JESR)']);
            fputcsv($handle, [$title]);
            fputcsv($handle, ['Generated: ' . now()->format('d M Y H:i:s')]);
            fputcsv($handle, []);

            $this->writeCsvRows($handle, $type, $data);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function getData(string $type, array $filters, User $user): mixed
    {
        return match($type) {
            'agents_by_ward'         => $this->agentsByWard($filters, $user),
            'by_gender'              => $this->byGender($filters, $user),
            'by_ward'                => $this->byWard($filters, $user),
            'by_lga'                 => $this->byLga($filters, $user),
            'by_party'               => $this->byParty($filters, $user),
            'by_senatorial_district' => $this->bySenatorialDistrict($filters, $user),
            'candidate_distribution' => $this->candidateDistribution($filters, $user),
            default => throw new \InvalidArgumentException("Unknown report type: {$type}"),
        };
    }

    private function agentsByWard(array $filters, User $user): mixed
    {
        $query = User::whereIn('role', ['ward_agent', 'polling_unit_agent'])
            ->with('lga', 'ward');

        if ($user->isSupervisor() && $user->lga_id) {
            $query->where('lga_id', $user->lga_id);
        } elseif ($user->isStateAdmin() && $user->state_id) {
            $query->where('state_id', $user->state_id);
        }

        return $query
            ->when(!empty($filters['lga_id']), fn($q) => $q->where('lga_id', $filters['lga_id']))
            ->when(!empty($filters['ward_id']), fn($q) => $q->where('ward_id', $filters['ward_id']))
            ->withCount('wardReports')
            ->orderBy('lga_id')->orderBy('ward_id')
            ->get();
    }

    private function byGender(array $filters, User $user): mixed
    {
        $query = User::whereIn('role', ['ward_agent', 'polling_unit_agent']);

        if ($user->isSupervisor() && $user->lga_id) {
            $query->where('lga_id', $user->lga_id);
        } elseif ($user->isStateAdmin() && $user->state_id) {
            $query->where('state_id', $user->state_id);
        }

        return $query
            ->when(!empty($filters['lga_id']), fn($q) => $q->where('lga_id', $filters['lga_id']))
            ->select('gender', DB::raw('COUNT(*) as count'))
            ->groupBy('gender')
            ->get();
    }

    private function byWard(array $filters, User $user): mixed
    {
        return WardReport::query()
            ->forUser($user)
            ->filtered($filters)
            ->select(
                'ward_id', 'lga_id',
                DB::raw('COUNT(*) as total_reports'),
                DB::raw('SUM(CASE WHEN status="verified" THEN 1 ELSE 0 END) as verified'),
                DB::raw('SUM(CASE WHEN status="flagged" THEN 1 ELSE 0 END) as flagged'),
                DB::raw('SUM(CASE WHEN status="escalated" THEN 1 ELSE 0 END) as escalated'),
                DB::raw('SUM(number_cast_votes) as total_votes_cast'),
                DB::raw('SUM(valid_ballot_papers) as total_valid'),
                DB::raw('SUM(invalid_ballot_papers) as total_invalid'),
            )
            ->groupBy('ward_id', 'lga_id')
            ->with('ward', 'lga')
            ->orderBy('lga_id')
            ->get();
    }

    private function byLga(array $filters, User $user): mixed
    {
        return WardReport::query()
            ->forUser($user)
            ->filtered($filters)
            ->select(
                'lga_id',
                DB::raw('COUNT(*) as total_reports'),
                DB::raw('COUNT(DISTINCT ward_id) as wards_reporting'),
                DB::raw('SUM(number_cast_votes) as total_votes_cast'),
                DB::raw('SUM(valid_ballot_papers) as total_valid'),
                DB::raw('SUM(invalid_ballot_papers) as total_invalid'),
                DB::raw('SUM(CASE WHEN security_present=1 THEN 1 ELSE 0 END) as security_incidents'),
                DB::raw('SUM(CASE WHEN status="escalated" THEN 1 ELSE 0 END) as escalations'),
            )
            ->groupBy('lga_id')
            ->with('lga')
            ->get();
    }

    private function byParty(array $filters, User $user): mixed
    {
        return WardReport::query()
            ->forUser($user)
            ->filtered($filters)
            ->join('users', 'ward_reports.agent_user_id', '=', 'users.id')
            ->select(
                'users.party_affiliation as party',
                DB::raw('COUNT(ward_reports.id) as report_count'),
                DB::raw('SUM(ward_reports.valid_ballot_papers) as total_valid'),
                DB::raw('SUM(ward_reports.invalid_ballot_papers) as total_invalid'),
                DB::raw('SUM(ward_reports.number_cast_votes) as total_votes_cast'),
            )
            ->groupBy('users.party_affiliation')
            ->orderByDesc('total_valid')
            ->get();
    }

    private function bySenatorialDistrict(array $filters, User $user): mixed
    {
        return WardReport::query()
            ->forUser($user)
            ->filtered($filters)
            ->join('wards', 'ward_reports.ward_id', '=', 'wards.id')
            ->join('senatorial_districts', 'wards.senatorial_district_id', '=', 'senatorial_districts.id')
            ->select(
                'senatorial_districts.name',
                DB::raw('COUNT(ward_reports.id) as total_reports'),
                DB::raw('COUNT(DISTINCT ward_reports.ward_id) as wards_reporting'),
                DB::raw('SUM(ward_reports.valid_ballot_papers) as total_valid'),
                DB::raw('SUM(ward_reports.invalid_ballot_papers) as total_invalid'),
                DB::raw('SUM(CASE WHEN ward_reports.status="escalated" THEN 1 ELSE 0 END) as escalations'),
                DB::raw('SUM(CASE WHEN ward_reports.status="flagged" THEN 1 ELSE 0 END) as flags'),
                DB::raw('SUM(CASE WHEN ward_reports.status="verified" THEN 1 ELSE 0 END) as verified'),
            )
            ->groupBy('senatorial_districts.name')
            ->get();
    }

    private function candidateDistribution(array $filters, User $user): mixed
    {
        return Candidate::with('party', 'ward', 'lga')
            ->when(!empty($filters['lga_id']), fn($q) => $q->where('lga_id', $filters['lga_id']))
            ->when(!empty($filters['ward_id']), fn($q) => $q->where('ward_id', $filters['ward_id']))
            ->when(!empty($filters['party_id']), fn($q) => $q->where('party_id', $filters['party_id']))
            ->get()
            ->map(function ($candidate) {
                $reports = WardReport::where('ward_id', $candidate->ward_id)
                    ->whereNotIn('status', ['draft'])
                    ->get();
                $candidate->ward_valid_votes = $reports->sum('valid_ballot_papers');
                $candidate->ward_report_count = $reports->count();
                return $candidate;
            })
            ->sortByDesc('ward_valid_votes');
    }

    private function getReportTitle(string $type): string
    {
        return match($type) {
            'agents_by_ward' => 'Ward Agents by Ward',
            'by_gender' => 'Submissions by Gender',
            'by_ward' => 'Submissions by Ward',
            'by_lga' => 'Submissions by Local Government Area',
            'by_party' => 'Submissions by Political Party',
            'by_senatorial_district' => 'Submissions by Senatorial District',
            'candidate_distribution' => 'Candidate Distribution across Wards',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    private function writeCsvRows($handle, string $type, mixed $data): void
    {
        match($type) {
            'agents_by_ward' => $this->csvAgentsByWard($handle, $data),
            'by_gender' => $this->csvByGender($handle, $data),
            'by_ward' => $this->csvByWard($handle, $data),
            'by_lga' => $this->csvByLga($handle, $data),
            'by_party' => $this->csvByParty($handle, $data),
            'by_senatorial_district' => $this->csvBySenatorialDistrict($handle, $data),
            'candidate_distribution' => $this->csvCandidateDistribution($handle, $data),
            default => null,
        };
    }

    private function csvAgentsByWard($handle, $data): void
    {
        fputcsv($handle, ['LGA', 'Ward', 'Agent Name', 'Phone', 'Gender', 'Party', 'Reports Submitted', 'Last Login']);
        foreach ($data as $agent) {
            fputcsv($handle, [$agent->lga?->name, $agent->ward?->name, $agent->full_name, $agent->phone, $agent->gender, $agent->party_affiliation, $agent->ward_reports_count, $agent->last_login_at?->format('d/m/Y H:i')]);
        }
    }

    private function csvByGender($handle, $data): void
    {
        fputcsv($handle, ['Gender', 'Count']);
        foreach ($data as $row) { fputcsv($handle, [$row->gender ?? 'Not specified', $row->count]); }
    }

    private function csvByWard($handle, $data): void
    {
        fputcsv($handle, ['LGA', 'Ward', 'Total Reports', 'Verified', 'Flagged', 'Escalated', 'Total Votes Cast', 'Valid', 'Invalid']);
        foreach ($data as $row) { fputcsv($handle, [$row->lga?->name, $row->ward?->name, $row->total_reports, $row->verified, $row->flagged, $row->escalated, $row->total_votes_cast, $row->total_valid, $row->total_invalid]); }
    }

    private function csvByLga($handle, $data): void
    {
        fputcsv($handle, ['LGA', 'Total Reports', 'Wards Reporting', 'Votes Cast', 'Valid', 'Invalid', 'Security Incidents', 'Escalations']);
        foreach ($data as $row) { fputcsv($handle, [$row->lga?->name, $row->total_reports, $row->wards_reporting, $row->total_votes_cast, $row->total_valid, $row->total_invalid, $row->security_incidents, $row->escalations]); }
    }

    private function csvByParty($handle, $data): void
    {
        fputcsv($handle, ['Party', 'Report Count', 'Total Valid Votes', 'Total Invalid', 'Total Cast']);
        foreach ($data as $row) { fputcsv($handle, [$row->party ?? 'Independent', $row->report_count, $row->total_valid, $row->total_invalid, $row->total_votes_cast]); }
    }

    private function csvBySenatorialDistrict($handle, $data): void
    {
        fputcsv($handle, ['Senatorial District', 'Total Reports', 'Wards Reporting', 'Valid Votes', 'Invalid', 'Escalations', 'Flags', 'Verified']);
        foreach ($data as $row) { fputcsv($handle, [$row->name, $row->total_reports, $row->wards_reporting, $row->total_valid, $row->total_invalid, $row->escalations, $row->flags, $row->verified]); }
    }

    private function csvCandidateDistribution($handle, $data): void
    {
        fputcsv($handle, ['Candidate', 'Party', 'Post Type', 'LGA', 'Ward', 'Ward Valid Votes', 'Reports in Ward']);
        foreach ($data as $candidate) { fputcsv($handle, [$candidate->name, $candidate->party?->acronym, $candidate->post_type, $candidate->lga?->name, $candidate->ward?->name, $candidate->ward_valid_votes ?? 0, $candidate->ward_report_count ?? 0]); }
    }
}
