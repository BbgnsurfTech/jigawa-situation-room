<?php
namespace App\Livewire\Supervisor;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\JesrNotification;
use App\Models\Party;
use App\Models\WardReport;
use App\Services\EscalationService;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReportInbox extends Component
{
    use WithPagination;

    public ?int $selectedReportId = null;
    public string $filterStatus = '';
    public int $unreadCount = 0;

    // Escalate modal
    public string $escalateReason = '';
    public int $escalateLevel = 1;
    public bool $showEscalateModal = false;

    // Flag modal
    public string $flagReason = '';
    public bool $showFlagModal = false;

    // Clarification modal
    public string $clarificationQuestion = '';
    public bool $showClarificationModal = false;

    // Notes
    public string $supervisorNote = '';

    public function boot(EscalationService $escalationService, SmsService $smsService): void
    {
        $this->escalationService = $escalationService;
        $this->smsService = $smsService;
    }

    public function getListeners(): array
    {
        $lgaId = auth()->user()?->lga_id;
        if (!$lgaId) {
            return [];
        }
        return [
            "echo-private:lga.{$lgaId},report.submitted" => 'handleNewReport',
            "echo-private:lga.{$lgaId},report.escalated" => 'handleEscalation',
        ];
    }

    public function mount(): void
    {
        // SEC-07: Defense-in-depth — ward agents must not access the inbox
        abort_unless(
            auth()->user()?->isSuperAdmin() || auth()->user()?->isSupervisor(),
            403
        );

        // Support click-through from dashboard map: ?pu=<id>
        if ($puId = request()->query('pu')) {
            $report = WardReport::where('polling_station_id', $puId)
                ->forUser(auth()->user())->latest()->first();
            if ($report) {
                $this->selectedReportId = $report->id;
            }
        }

        $this->loadUnreadCount();
    }

    public function handleNewReport($event)
    {
        $this->loadUnreadCount();
        $this->dispatch('notify', message: 'New report received from ' . ($event['agent_name'] ?? 'unknown'));
    }

    public function handleEscalation($event)
    {
        $this->dispatch('notify', message: 'Report #' . $event['report_id'] . ' escalated to Level ' . $event['level']);
    }

    public function loadUnreadCount()
    {
        $this->unreadCount = JesrNotification::where('recipient_user_id', auth()->id())
            ->where('is_read', false)->count();
    }

    public function selectReport(int $id)
    {
        $this->selectedReportId = $id;
        $this->reset('supervisorNote', 'flagReason', 'clarificationQuestion');
    }

    public function verify()
    {
        $report = WardReport::findOrFail($this->selectedReportId);
        $report->update(['status' => 'verified']);

        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'actor_role'    => auth()->user()->role,
            'action_type'   => 'verify',
            'entity_type'   => 'WardReport',
            'entity_id'     => $report->id,
            'ip_address'    => request()->ip(),
            'device_info'   => request()->userAgent(),
        ]);

        JesrNotification::create([
            'recipient_user_id'   => $report->agent_user_id,
            'type'                => 'report_verified',
            'message'             => "Your report #{$report->id} has been verified.",
            'related_entity_type' => 'WardReport',
            'related_entity_id'   => $report->id,
        ]);

        $this->dispatch('notify', message: "Report #{$report->id} verified.");
        $this->reset('selectedReportId');
    }

    // Escalation -------------------------------------------------------

    public function openEscalateModal()
    {
        $this->showEscalateModal = true;
    }

    public function escalate()
    {
        $this->validate([
            'escalateReason' => 'required|string|min:10',
            'escalateLevel'  => 'required|integer|between:1,3',
        ]);

        $report = WardReport::findOrFail($this->selectedReportId);
        $report->update(['status' => 'escalated']);

        $this->escalationService->triggerEscalation(
            $report,
            $this->escalateLevel,
            auth()->user(),
            $this->escalateReason,
        );

        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'actor_role'    => auth()->user()->role,
            'action_type'   => 'escalate',
            'entity_type'   => 'WardReport',
            'entity_id'     => $report->id,
            'new_value'     => ['level' => $this->escalateLevel, 'reason' => $this->escalateReason],
            'ip_address'    => request()->ip(),
            'device_info'   => request()->userAgent(),
        ]);

        $this->showEscalateModal = false;
        $this->reset('escalateReason', 'selectedReportId');
        $this->dispatch('notify', message: "Report escalated to Level {$this->escalateLevel}.");
    }

    // Flag --------------------------------------------------------------

    public function openFlagModal()
    {
        $this->showFlagModal = true;
    }

    public function flag()
    {
        $this->validate(['flagReason' => 'required|string|min:10']);

        $report = WardReport::findOrFail($this->selectedReportId);
        $report->update(['status' => 'flagged', 'flag_reason' => $this->flagReason]);

        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'actor_role'    => auth()->user()->role,
            'action_type'   => 'flag',
            'entity_type'   => 'WardReport',
            'entity_id'     => $report->id,
            'new_value'     => ['reason' => $this->flagReason],
            'ip_address'    => request()->ip(),
            'reason'        => $this->flagReason,
        ]);

        JesrNotification::create([
            'recipient_user_id'   => $report->agent_user_id,
            'type'                => 'report_flagged',
            'message'             => "Your report #{$report->id} has been flagged. Reason: {$this->flagReason}",
            'related_entity_type' => 'WardReport',
            'related_entity_id'   => $report->id,
        ]);

        $this->showFlagModal = false;
        $this->reset('flagReason');
        $this->dispatch('notify', message: "Report #{$report->id} flagged.");
    }

    // Clarification -----------------------------------------------------

    public function openClarificationModal()
    {
        $this->showClarificationModal = true;
    }

    public function requestClarification()
    {
        $this->validate(['clarificationQuestion' => 'required|string|min:10']);

        $report = WardReport::with('agent')->findOrFail($this->selectedReportId);
        $report->update([
            'clarification_request' => $this->clarificationQuestion,
            'clarification_sent_at' => now(),
        ]);

        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'actor_role'    => auth()->user()->role,
            'action_type'   => 'update',
            'entity_type'   => 'WardReport',
            'entity_id'     => $report->id,
            'new_value'     => ['clarification_request' => $this->clarificationQuestion],
            'ip_address'    => request()->ip(),
            'reason'        => 'Clarification requested',
        ]);

        JesrNotification::create([
            'recipient_user_id'   => $report->agent_user_id,
            'type'                => 'clarification_requested',
            'message'             => "Clarification needed on Report #{$report->id}: {$this->clarificationQuestion}",
            'related_entity_type' => 'WardReport',
            'related_entity_id'   => $report->id,
        ]);

        $this->smsService->send(
            $report->agent->phone,
            "JESR: Clarification needed on Report #{$report->id}: " . substr($this->clarificationQuestion, 0, 100)
        );

        $this->showClarificationModal = false;
        $this->reset('clarificationQuestion');
        $this->dispatch('notify', message: "Clarification request sent to agent.");
    }

    // Notes -------------------------------------------------------------

    public function addNote()
    {
        $this->validate(['supervisorNote' => 'required|string|min:3']);

        $report = WardReport::findOrFail($this->selectedReportId);
        $existing = $report->supervisor_note ? $report->supervisor_note . "\n---\n" : '';
        $report->update([
            'supervisor_note' => $existing . '[' . auth()->user()->full_name . ' @ ' . now()->format('d M y H:i') . '] ' . $this->supervisorNote,
        ]);

        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'actor_role'    => auth()->user()->role,
            'action_type'   => 'update',
            'entity_type'   => 'WardReport',
            'entity_id'     => $report->id,
            'new_value'     => ['note' => $this->supervisorNote],
            'ip_address'    => request()->ip(),
            'reason'        => 'Supervisor note appended',
        ]);

        $this->reset('supervisorNote');
        $this->dispatch('notify', message: "Note saved.");
    }

    public function render()
    {
        $user = auth()->user();

        // Sort by severity: escalated → flagged → submitted → others
        $query = WardReport::with(['agent', 'ward', 'lga', 'escalation', 'pollingStation'])
            ->forUser($user)
            ->orderByRaw("
                CASE
                    WHEN status = 'escalated' THEN 1
                    WHEN status = 'flagged'   THEN 2
                    WHEN status = 'submitted' THEN 3
                    ELSE 4
                END
            ")
            ->orderByDesc('created_at');

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $partyColours = Party::pluck('colour_hex', 'acronym')->toArray();

        // Build candidate lookup for selected report scope
        $candidateMap = [];
        $selectedReport = null;
        if ($this->selectedReportId) {
            $selectedReport = WardReport::with(['agent', 'ward', 'lga', 'media', 'escalation', 'pollingStation', 'partyResults'])->find($this->selectedReportId);
            if ($selectedReport) {
                $candidates = Candidate::with('party')
                    ->where(function ($q) use ($selectedReport) {
                        $q->whereIn('post_type', ['governorship', 'senate'])
                            ->orWhere('lga_id', $selectedReport->lga_id)
                            ->orWhere('ward_id', $selectedReport->ward_id);
                    })->get();
                foreach ($candidates as $c) {
                    $label = ucwords(str_replace('_', ' ', $c->post_type));
                    $candidateMap[$c->party?->acronym][] = $c->name . ' (' . $label . ')';
                }
            }
        }

        return view('livewire.supervisor.report-inbox', [
            'reports'        => $query->paginate(20),
            'selectedReport' => $selectedReport,
            'partyColours'   => $partyColours,
            'candidateMap'   => $candidateMap,
        ])->layout('layouts.app', ['title' => 'Report Inbox']);
    }

    // Non-serialisable services — re-bound each request via boot()
    private EscalationService $escalationService;
    private SmsService $smsService;
}
