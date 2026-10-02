<?php
namespace App\Services;

use App\Events\ReportEscalated;
use App\Mail\EscalationMail;
use App\Models\Escalation;
use App\Models\JesrNotification;
use App\Models\User;
use App\Models\WardReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EscalationService
{
    public function __construct(private SmsService $sms) {}

    public function triggerEscalation(WardReport $report, int $level, User $actor, string $reason): Escalation
    {
        $escalation = Escalation::create([
            'report_id'    => $report->id,
            'level'        => $level,
            'escalated_by' => $actor->id,
            'reason'       => $reason,
        ]);

        match ($level) {
            1 => $this->handleLevel1($report, $actor, $reason),
            2 => $this->handleLevel2($report, $actor, $reason),
            3 => $this->handleLevel3($report, $actor, $reason),
        };

        broadcast(new ReportEscalated($escalation));

        return $escalation;
    }

    private function handleLevel1(WardReport $report, User $actor, string $reason): void
    {
        $supervisors = User::where('role', 'supervisor')
            ->where('lga_id', $report->lga_id)
            ->where('is_active', true)
            ->get();

        foreach ($supervisors as $supervisor) {
            JesrNotification::create([
                'recipient_user_id'   => $supervisor->id,
                'type'                => 'escalation_l1',
                'message'             => "LEVEL 1 ESCALATION — Report #{$report->id} from Ward {$report->ward_id}. Reason: {$reason}. Respond within 10 minutes.",
                'related_entity_type' => 'WardReport',
                'related_entity_id'   => $report->id,
            ]);

            $this->sms->send(
                $supervisor->phone,
                "JESR L1 ALERT: Report #{$report->id} escalated. Ward {$report->ward_id}. Reason: " . substr($reason, 0, 80) . ". Respond within 10min."
            );
        }
    }

    private function handleLevel2(WardReport $report, User $actor, string $reason): void
    {
        // L2: SMS + email to LGA supervisors and super_admins (within 30 min)
        $coordinators = User::where('is_active', true)
            ->where(function ($q) use ($report) {
                $q->where(function ($q2) use ($report) {
                    $q2->where('role', 'supervisor')->where('lga_id', $report->lga_id);
                })->orWhere('role', 'super_admin');
            })
            ->get();

        foreach ($coordinators as $coordinator) {
            JesrNotification::create([
                'recipient_user_id'   => $coordinator->id,
                'type'                => 'escalation_l2',
                'message'             => "LEVEL 2 ESCALATION — Report #{$report->id}. Coordinate with local security. Respond within 30 minutes.",
                'related_entity_type' => 'WardReport',
                'related_entity_id'   => $report->id,
            ]);

            $this->sms->send(
                $coordinator->phone,
                "JESR L2 ESCALATION: Report #{$report->id} — Coordinate with security. 30min window."
            );

            if ($coordinator->email) {
                Mail::to($coordinator->email)->queue(new EscalationMail($report, 2, $reason));
            }
        }
    }

    private function handleLevel3(WardReport $report, User $actor, string $reason): void
    {
        // L3: Immediate — SMS + email + push to State Steering Committee
        $admins = User::where('role', 'super_admin')->where('is_active', true)->get();

        foreach ($admins as $admin) {
            JesrNotification::create([
                'recipient_user_id'   => $admin->id,
                'type'                => 'escalation_l3',
                'message'             => "CRITICAL L3 ESCALATION — Report #{$report->id}. IMMEDIATE ACTION REQUIRED. Reason: {$reason}",
                'related_entity_type' => 'WardReport',
                'related_entity_id'   => $report->id,
            ]);

            $this->sms->send(
                $admin->phone,
                "JESR CRITICAL: Report #{$report->id} — L3 Escalation. IMMEDIATE response required. " . substr($reason, 0, 60)
            );

            if ($admin->email) {
                Mail::to($admin->email)->queue(new EscalationMail($report, 3, $reason));
            }
        }

        Log::critical("[JESR L3 ESCALATION] Report #{$report->id}: {$reason}");
    }
}
