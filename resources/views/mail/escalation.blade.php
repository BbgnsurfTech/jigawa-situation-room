<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: sans-serif; background: #f8fafc; margin: 0; padding: 20px; color: #1e293b; }
  .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; }
  .header { padding: 24px; background: {{ $level === 3 ? '#dc2626' : ($level === 2 ? '#ea580c' : '#16a34a') }}; color: #fff; }
  .header h1 { margin: 0; font-size: 18px; font-weight: 700; }
  .header p { margin: 4px 0 0; opacity: 0.85; font-size: 13px; }
  .body { padding: 24px; }
  .detail { background: #f8fafc; border-radius: 6px; padding: 16px; margin-bottom: 16px; }
  .detail dt { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 600; letter-spacing: .05em; }
  .detail dd { margin: 2px 0 12px; font-size: 14px; font-weight: 500; }
  .reason { background: #fef2f2; border-left: 4px solid #f87171; padding: 12px 16px; border-radius: 4px; font-size: 14px; line-height: 1.5; }
  .footer { padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>
      @if($level === 3) ⚠️ CRITICAL
      @elseif($level === 2) ⚡ Level 2 Escalation
      @else 🔔 Level 1 Alert
      @endif
    </h1>
    <p>JESR — Jigawa State Election Situation Room</p>
  </div>

  <div class="body">
    <p style="font-size:14px; margin-bottom:16px;">
      @if($level === 3)
        <strong>Immediate action required.</strong> A Level 3 escalation has been triggered.
      @elseif($level === 2)
        A Level 2 escalation requires your attention within <strong>30 minutes</strong>.
      @else
        A Level 1 alert requires your response within <strong>10 minutes</strong>.
      @endif
    </p>

    <dl class="detail">
      <dt>Report ID</dt>
      <dd>#{{ $report->id }}</dd>
      <dt>Ward</dt>
      <dd>{{ $report->ward?->name ?? 'Ward #'.$report->ward_id }}</dd>
      <dt>LGA</dt>
      <dd>{{ $report->lga?->name ?? 'LGA #'.$report->lga_id }}</dd>
      <dt>Submitted Via</dt>
      <dd>{{ strtoupper($report->submitted_via) }}</dd>
      <dt>Report Status</dt>
      <dd>{{ ucfirst($report->status) }}</dd>
      <dt>Escalation Time</dt>
      <dd>{{ now()->format('d M Y, H:i T') }}</dd>
    </dl>

    <p style="font-size:13px; font-weight:600; color:#475569; margin-bottom:6px;">Reason for Escalation</p>
    <div class="reason">{{ $reason }}</div>
  </div>

  <div class="footer">
    This is an automated alert from JESR. Do not reply to this email.<br>
    Log in to the Situation Room dashboard to take action.
  </div>
</div>
</body>
</html>
