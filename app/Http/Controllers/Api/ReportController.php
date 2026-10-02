<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Models\AuditLog;
use App\Models\Escalation;
use App\Models\JesrNotification;
use App\Models\WardReport;
use App\Services\EscalationService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private EscalationService $escalationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user    = $request->user();
        $filters = $request->only(['lga_id', 'ward_id', 'polling_station_id', 'status', 'submitted_via', 'from', 'to', 'party_id']);

        $reports = WardReport::with(['agent', 'lga', 'ward', 'escalation', 'pollingStation'])
            ->forUser($user)
            ->filtered($filters)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($reports);
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $data     = $request->validated();
        $warnings = [];

        foreach ($data as $key => $value) {
            if (str_ends_with($key, '_warning')) {
                $warnings[] = $value;
                unset($data[$key]);
            }
        }

        $partyResults = $data['party_results'] ?? null;
        unset($data['party_results']);

        $report = $this->reportService->createReport($data, $request->user(), $request);

        if ($partyResults) {
            $this->reportService->savePartyResults($report, $partyResults);
        }

        return response()->json([
            'message'   => 'Report submitted successfully.',
            'report_id' => $report->id,
            'status'    => $report->status,
            'warnings'  => $warnings,
        ], 201);
    }

    public function batch(Request $request): JsonResponse
    {
        $request->validate(['reports' => ['required', 'array', 'max:50']]);

        $results = [];

        foreach ($request->input('reports', []) as $index => $reportData) {
            // Validate each item through StoreReportRequest rules
            $validator = validator($reportData, (new StoreReportRequest())->rules());

            if ($validator->fails()) {
                $results[] = [
                    'index'   => $index,
                    'status'  => 'error',
                    'errors'  => $validator->errors()->toArray(),
                    'data'    => $reportData,
                ];
                continue;
            }

            try {
                $report    = $this->reportService->createReport($validator->validated(), $request->user(), $request);
                $results[] = ['index' => $index, 'status' => 'success', 'report_id' => $report->id];
            } catch (\Exception $e) {
                $results[] = ['index' => $index, 'status' => 'error', 'message' => $e->getMessage()];
            }
        }

        $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));

        return response()->json([
            'message' => "Batch processed: {$successCount}/" . count($results) . " succeeded.",
            'results' => $results,
        ]);
    }

    public function show(Request $request, WardReport $report): JsonResponse
    {
        $this->authorize('view', $report);
        $report->load(['agent', 'lga', 'ward', 'media', 'escalation', 'pollingStation', 'partyResults', 'auditLogs.actor']);

        return response()->json($report);
    }

    public function update(UpdateReportRequest $request, WardReport $report): JsonResponse
    {
        $this->authorize('update', $report);

        if ($request->user()->isFieldAgent() && !$report->isWithinCorrectionWindow()) {
            return response()->json([
                'message' => 'Correction window has expired. Contact your supervisor.',
            ], 403);
        }

        $report = $this->reportService->updateReport($report, $request->validated(), $request->user());

        return response()->json(['message' => 'Report updated.', 'report' => $report]);
    }

    public function escalate(Request $request, WardReport $report): JsonResponse
    {
        $this->authorize('escalate', $report);

        $request->validate([
            'level'  => ['required', 'integer', 'between:1,3'],
            'reason' => ['required', 'string', 'min:10'],
        ]);

        $report->update(['status' => 'escalated']);

        // Wire through EscalationService — sends SMS + creates notifications
        $escalation = $this->escalationService->triggerEscalation(
            $report,
            $request->level,
            $request->user(),
            $request->reason
        );

        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'actor_role'    => $request->user()->role,
            'action_type'   => 'escalate',
            'entity_type'   => 'WardReport',
            'entity_id'     => $report->id,
            'new_value'     => ['level' => $request->level, 'reason' => $request->reason],
            'ip_address'    => $request->ip(),
            'device_info'   => $request->userAgent(),
        ]);

        JesrNotification::create([
            'recipient_user_id' => $report->agent_user_id,
            'type'              => 'report_escalated',
            'message'           => "Your report #{$report->id} has been escalated to Level {$request->level}.",
            'related_entity_type' => 'WardReport',
            'related_entity_id' => $report->id,
        ]);

        return response()->json([
            'message'       => "Report escalated to Level {$request->level}.",
            'escalation_id' => $escalation->id,
        ]);
    }

    public function verify(Request $request, WardReport $report): JsonResponse
    {
        $this->authorize('verify', $report);

        $report->update(['status' => 'verified']);

        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'actor_role'    => $request->user()->role,
            'action_type'   => 'verify',
            'entity_type'   => 'WardReport',
            'entity_id'     => $report->id,
            'ip_address'    => $request->ip(),
            'device_info'   => $request->userAgent(),
        ]);

        JesrNotification::create([
            'recipient_user_id' => $report->agent_user_id,
            'type'              => 'report_verified',
            'message'           => "Your report #{$report->id} has been verified.",
            'related_entity_type' => 'WardReport',
            'related_entity_id' => $report->id,
        ]);

        return response()->json(['message' => 'Report verified.']);
    }

    public function flag(Request $request, WardReport $report): JsonResponse
    {
        $this->authorize('escalate', $report); // supervisors & admins can flag

        $request->validate([
            'reason' => ['required', 'string', 'min:10'],
        ]);

        $report->update([
            'status'      => 'flagged',
            'flag_reason' => $request->reason,
        ]);

        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'actor_role'    => $request->user()->role,
            'action_type'   => 'flag',
            'entity_type'   => 'WardReport',
            'entity_id'     => $report->id,
            'new_value'     => ['reason' => $request->reason],
            'ip_address'    => $request->ip(),
            'device_info'   => $request->userAgent(),
            'reason'        => $request->reason,
        ]);

        JesrNotification::create([
            'recipient_user_id'  => $report->agent_user_id,
            'type'               => 'report_flagged',
            'message'            => "Your report #{$report->id} has been flagged. Reason: {$request->reason}",
            'related_entity_type' => 'WardReport',
            'related_entity_id'  => $report->id,
        ]);

        return response()->json(['message' => 'Report flagged.']);
    }

    public function requestClarification(Request $request, WardReport $report): JsonResponse
    {
        $this->authorize('escalate', $report);

        $request->validate([
            'question' => ['required', 'string', 'min:10'],
        ]);

        $report->update([
            'clarification_request' => $request->question,
            'clarification_sent_at' => now(),
        ]);

        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'actor_role'    => $request->user()->role,
            'action_type'   => 'update',
            'entity_type'   => 'WardReport',
            'entity_id'     => $report->id,
            'new_value'     => ['clarification_request' => $request->question],
            'ip_address'    => $request->ip(),
            'device_info'   => $request->userAgent(),
            'reason'        => 'Clarification requested',
        ]);

        JesrNotification::create([
            'recipient_user_id'  => $report->agent_user_id,
            'type'               => 'clarification_requested',
            'message'            => "Clarification needed on Report #{$report->id}: {$request->question}",
            'related_entity_type' => 'WardReport',
            'related_entity_id'  => $report->id,
        ]);

        // Send SMS to agent
        app(\App\Services\SmsService::class)->send(
            $report->agent->phone,
            "JESR: Clarification needed on Report #{$report->id}: " . substr($request->question, 0, 100)
        );

        return response()->json(['message' => 'Clarification request sent to agent.']);
    }

    public function addNote(Request $request, WardReport $report): JsonResponse
    {
        $this->authorize('escalate', $report);

        $request->validate(['note' => ['required', 'string', 'min:3']]);

        $existing = $report->supervisor_note ? $report->supervisor_note . "\n---\n" : '';
        $report->update([
            'supervisor_note' => $existing . '[' . auth()->user()->full_name . ' @ ' . now()->format('d M y H:i') . '] ' . $request->note,
        ]);

        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'actor_role'    => $request->user()->role,
            'action_type'   => 'update',
            'entity_type'   => 'WardReport',
            'entity_id'     => $report->id,
            'new_value'     => ['note' => $request->note],
            'ip_address'    => $request->ip(),
            'reason'        => 'Supervisor note appended',
        ]);

        return response()->json(['message' => 'Note saved.']);
    }
}
