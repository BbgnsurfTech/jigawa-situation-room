<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PU Agent Register — JESR</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .header { border-bottom: 2px solid #166534; padding-bottom: 10px; margin-bottom: 12px; }
        .header h1 { font-size: 14px; font-weight: bold; color: #166534; }
        .header .sub { font-size: 10px; color: #555; margin-top: 2px; }
        .meta { font-size: 8px; color: #777; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        thead th { background: #166534; color: #fff; padding: 5px 6px; text-align: left; font-size: 8px; font-weight: bold; }
        tbody tr:nth-child(even) { background: #f0fdf4; }
        tbody td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .badge { display: inline-block; background: #dcfce7; color: #166534; border-radius: 3px; padding: 1px 4px; font-size: 7px; margin: 1px; }
        .masked { color: #9ca3af; font-style: italic; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #ddd; padding: 4px 10px; font-size: 7px; color: #888; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Jigawa State Election Situation Room (JESR)</h1>
        <div class="sub">Polling Unit Agent Register — APC Jigawa State Chapter</div>
        <div class="meta">
            Generated: {{ $generated_at }} &nbsp;|&nbsp; By: {{ $generator->full_name }} ({{ ucwords(str_replace('_', ' ', $generator->role)) }})
            @if(!empty($filters['lga_id'])) &nbsp;|&nbsp; LGA filtered @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Surname</th>
                <th>First Name</th>
                <th>Polling Unit</th>
                <th>PU No.</th>
                <th>LGA</th>
                <th>Ward</th>
                <th>State</th>
                <th>NIN</th>
                <th>Voters Card</th>
                <th>Leadership Roles</th>
                <th>Bank</th>
                @if($isSuperAdmin)
                    <th>BVN</th>
                    <th>Account No.</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($agents as $i => $agent)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $agent->surname }}</td>
                    <td>{{ $agent->first_name }}</td>
                    <td>{{ $agent->polling_unit }}</td>
                    <td>{{ $agent->polling_unit_number }}</td>
                    <td>{{ $agent->lga?->name }}</td>
                    <td>{{ $agent->ward?->name }}</td>
                    <td>{{ $agent->state }}</td>
                    <td style="font-family: monospace;">{{ $agent->nin }}</td>
                    <td>{{ $agent->voters_card_number }}</td>
                    <td>
                        @foreach(($agent->leadership_roles ?? []) as $role)
                            <span class="badge">{{ \App\Services\PollingUnitAgentService::LEADERSHIP_ROLES[$role] ?? $role }}</span>
                        @endforeach
                    </td>
                    <td>{{ $agent->bank_name ?? '—' }}</td>
                    @if($isSuperAdmin)
                        <td style="font-family: monospace;">{{ $agent->getRawBvn() ?? '—' }}</td>
                        <td style="font-family: monospace;">{{ $agent->getRawAccountNumber() ?? '—' }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="14" style="text-align:center; padding: 20px; color: #888;">No agents found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>JESR — Confidential</span>
        <span>Total: {{ $agents->count() }} agents</span>
    </div>
</body>
</html>
