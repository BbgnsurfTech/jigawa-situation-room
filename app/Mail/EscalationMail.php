<?php

namespace App\Mail;

use App\Models\Escalation;
use App\Models\WardReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EscalationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly WardReport $report,
        public readonly int $level,
        public readonly string $reason,
    ) {}

    public function envelope(): Envelope
    {
        $labels = [1 => 'L1 Alert', 2 => 'L2 Escalation', 3 => '⚠️ CRITICAL L3 Escalation'];
        return new Envelope(
            subject: "[JESR] {$labels[$this->level]} — Report #{$this->report->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.escalation',
        );
    }
}
