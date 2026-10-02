<div class="max-w-4xl mx-auto space-y-6">

    {{-- Welcome header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex-1">
            <h2 class="text-lg font-semibold text-gray-900">Welcome, {{ auth()->user()->full_name }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ auth()->user()->ward?->name ?? 'Unassigned Ward' }}
                &mdash;
                {{ auth()->user()->lga?->name ?? 'Unassigned LGA' }}
                @if(auth()->user()->role === 'polling_unit_agent')
                    &mdash; {{ auth()->user()->pollingStation?->name ?? 'Unassigned Polling Unit' }}
                @endif
            </p>
        </div>
        <a href="{{ route('reports.create') }}" wire:navigate
           class="inline-flex items-center gap-2 bg-green-700 hover:bg-green-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Submit New Report
        </a>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        @php
        $cards = [
            ['label' => 'Total Submitted',  'value' => $summary['total'],     'color' => 'bg-gray-50  border-gray-200  text-gray-700',  'dot' => 'bg-gray-400'],
            ['label' => 'Pending Review',   'value' => $summary['submitted'], 'color' => 'bg-blue-50  border-blue-200  text-blue-700',  'dot' => 'bg-blue-500'],
            ['label' => 'Verified',         'value' => $summary['verified'],  'color' => 'bg-green-50 border-green-200 text-green-700', 'dot' => 'bg-green-500'],
            ['label' => 'Escalated',        'value' => $summary['escalated'], 'color' => 'bg-red-50   border-red-200   text-red-700',   'dot' => 'bg-red-500'],
            ['label' => 'Flagged',          'value' => $summary['flagged'],   'color' => 'bg-amber-50 border-amber-200 text-amber-700', 'dot' => 'bg-amber-500'],
        ];
        @endphp
        @foreach($cards as $card)
        <div class="bg-white border {{ $card['color'] }} rounded-xl p-4 border">
            <div class="flex items-center gap-1.5 mb-1">
                <span class="w-2 h-2 rounded-full {{ $card['dot'] }}"></span>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ $card['label'] }}</span>
            </div>
            <div class="text-2xl font-bold {{ explode(' ', $card['color'])[2] ?? 'text-gray-900' }}">{{ $card['value'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Recent submissions --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Recent Submissions</h3>
            <span class="text-xs text-gray-400">Last 10 reports</span>
        </div>

        @if(empty($recentReports))
        <div class="py-16 text-center text-gray-400">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm font-medium">No reports submitted yet</p>
            <p class="text-xs mt-1">Your submissions will appear here</p>
        </div>
        @else
        {{-- Desktop table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3 text-left">Date</th>
                        <th class="px-5 py-3 text-left">Ward / LGA</th>
                        <th class="px-5 py-3 text-left">Polling Unit</th>
                        <th class="px-5 py-3 text-center">Valid Votes</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-center">Via</th>
                        <th class="px-5 py-3 text-center">Security</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recentReports as $report)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3.5 text-gray-600 whitespace-nowrap text-xs">{{ $report['created_at'] }}</td>
                        <td class="px-5 py-3.5">
                            <div class="font-medium text-gray-800">{{ $report['ward'] }}</div>
                            <div class="text-xs text-gray-400">{{ $report['lga'] }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-gray-600 text-xs">{{ $report['polling_station'] }}</td>
                        <td class="px-5 py-3.5 text-center font-mono text-gray-700">{{ number_format($report['valid_votes'] ?? 0) }}</td>
                        <td class="px-5 py-3.5 text-center">
                            @php
                            $statusStyle = match($report['status']) {
                                'verified'  => 'bg-green-100 text-green-700',
                                'escalated' => 'bg-red-100 text-red-700',
                                'flagged'   => 'bg-amber-100 text-amber-700',
                                default     => 'bg-blue-100 text-blue-700',
                            };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusStyle }}">
                                {{ ucfirst($report['status']) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-center text-xs text-gray-500 uppercase">{{ $report['submitted_via'] }}</td>
                        <td class="px-5 py-3.5 text-center">
                            @if($report['has_security'])
                            <span class="inline-flex items-center gap-1 text-red-600 text-xs font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Yes
                            </span>
                            @else
                            <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="sm:hidden divide-y divide-gray-100">
            @foreach($recentReports as $report)
            <div class="p-4 space-y-2">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-medium text-gray-800 text-sm">{{ $report['ward'] }}</div>
                        <div class="text-xs text-gray-400">{{ $report['lga'] }} &middot; {{ $report['created_at'] }}</div>
                    </div>
                    @php
                    $statusStyle = match($report['status']) {
                        'verified'  => 'bg-green-100 text-green-700',
                        'escalated' => 'bg-red-100 text-red-700',
                        'flagged'   => 'bg-amber-100 text-amber-700',
                        default     => 'bg-blue-100 text-blue-700',
                    };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusStyle }} shrink-0">
                        {{ ucfirst($report['status']) }}
                    </span>
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span>Valid votes: <strong class="text-gray-700">{{ number_format($report['valid_votes'] ?? 0) }}</strong></span>
                    <span class="uppercase">{{ $report['submitted_via'] }}</span>
                    @if($report['has_security'])
                    <span class="text-red-600 font-medium">Security</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>
