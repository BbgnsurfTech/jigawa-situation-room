<?php
namespace App\Services;

use App\Events\ReportSubmitted;
use App\Models\AuditLog;
use App\Models\JesrNotification;
use App\Models\PollingStation;
use App\Models\PuPartyResult;
use App\Models\ReportMedia;
use App\Models\User;
use App\Models\WardReport;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    public function createReport(array $data, User $agent, Request $request): WardReport
    {
        // Determine status: if has warning, flag for review
        $status = isset($data['counted_voter_papers_warning']) ? 'flagged' : 'submitted';

        // Auto-derive ward_id and lga_id from polling station if provided
        $pollingStationId = $data['polling_station_id'] ?? null;
        $registeredVoterSnapshot = null;
        $turnoutRate = null;

        if ($pollingStationId) {
            $station = PollingStation::find($pollingStationId);
            if ($station) {
                $data['ward_id'] = $data['ward_id'] ?? $station->ward_id;
                $data['lga_id']  = $data['lga_id'] ?? $station->lga_id;
                $registeredVoterSnapshot = $station->registered_voter_count;

                // Compute turnout rate
                if ($registeredVoterSnapshot > 0) {
                    $turnoutRate = round(
                        ((int) $data['number_cast_votes'] / $registeredVoterSnapshot) * 100,
                        2
                    );
                }
            }
        }

        $report = WardReport::create([
            'agent_user_id' => $agent->id,
            'lga_id' => $data['lga_id'],
            'ward_id' => $data['ward_id'],
            'polling_station_id' => $pollingStationId,
            'party_agent_id' => $data['party_agent_id'] ?? null,
            'inec_agent_name' => $data['inec_agent_name'] ?? null,
            'counted_voter_papers' => $data['counted_voter_papers'],
            'security_present' => $data['security_present'],
            'security_types' => $data['security_types'] ?? null,
            'voting_closed_time' => $data['voting_closed_time'] ?? null,
            'number_cast_votes' => $data['number_cast_votes'],
            'voting_papers_left' => $data['voting_papers_left'],
            'valid_ballot_papers' => $data['valid_ballot_papers'],
            'invalid_ballot_papers' => $data['invalid_ballot_papers'],
            'remarks' => $data['remarks'] ?? null,
            'status' => $status,
            'submitted_via' => $data['submitted_via'] ?? 'web',
            'device_id' => $data['device_id'] ?? null,
            'sync_status' => 'synced',
            'submitted_at_device' => $data['submitted_at_device'] ?? null,
            'ec8a_serial_number' => $data['ec8a_serial_number'] ?? null,
            'turnout_rate' => $turnoutRate,
            'registered_voter_count_snapshot' => $registeredVoterSnapshot,
        ]);

        // Save party results if provided
        if (!empty($data['party_results'])) {
            $this->savePartyResults($report, $data['party_results']);
        }

        // Handle media uploads
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $this->storeMedia($report, $photo, 'photo');
            }
        }
        if ($request->hasFile('video')) {
            $this->storeMedia($report, $request->file('video'), 'video');
        }
        if ($request->hasFile('audio')) {
            $this->storeMedia($report, $request->file('audio'), 'audio');
        }

        // Broadcast real-time event to dashboards
        broadcast(new ReportSubmitted($report));

        // Notify supervisors if security incident
        if ($report->security_present) {
            $this->notifySupervisors($report, $agent);
        }

        return $report;
    }

    public function updateReport(WardReport $report, array $data, User $actor): WardReport
    {
        $report->update(array_filter($data, fn($v) => $v !== null, ARRAY_FILTER_USE_BOTH));
        
        // Append correction reason to audit log
        AuditLog::create([
            'actor_user_id' => $actor->id,
            'actor_role' => $actor->role,
            'action_type' => 'update',
            'entity_type' => 'WardReport',
            'entity_id' => $report->id,
            'previous_value' => $report->getOriginal(),
            'new_value' => $report->getChanges(),
            'reason' => $data['correction_reason'] ?? 'No reason provided',
            'ip_address' => request()->ip(),
            'device_info' => request()->userAgent(),
        ]);

        return $report;
    }

    public function savePartyResults(WardReport $report, array $partyResults): void
    {
        // Clear existing results for idempotency on re-submission
        $report->partyResults()->delete();

        foreach ($partyResults as $result) {
            PuPartyResult::create([
                'ward_report_id' => $report->id,
                'party_name'     => strtoupper(trim($result['party_name'])),
                'election_type'  => $result['election_type'] ?? null,
                'votes_scored'   => (int) $result['votes_scored'],
            ]);
        }
    }

    private function storeMedia(WardReport $report, UploadedFile $file, string $type): ReportMedia
    {
        $path = $file->store("reports/{$report->id}/{$type}s", 'local');
        
        return ReportMedia::create([
            'report_id' => $report->id,
            'media_type' => $type,
            'storage_path' => $path,
            'upload_status' => 'uploaded',
        ]);
    }

    private function notifySupervisors(WardReport $report, User $agent): void
    {
        $supervisors = User::where('role', 'supervisor')
            ->where('lga_id', $report->lga_id)
            ->orWhere('role', 'super_admin')
            ->get();

        foreach ($supervisors as $supervisor) {
            JesrNotification::create([
                'recipient_user_id' => $supervisor->id,
                'type' => 'security_incident',
                'message' => "Security incident reported by {$agent->full_name} in ward {$report->ward_id}.",
                'related_entity_type' => 'WardReport',
                'related_entity_id' => $report->id,
            ]);
        }
    }
}
