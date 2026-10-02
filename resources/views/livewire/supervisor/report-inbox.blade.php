<div class="flex flex-col lg:flex-row gap-4 lg:gap-6 lg:h-[calc(100vh-8rem)]">
    {{-- Left Panel: Report List --}}
    <div class="w-full lg:w-96 flex-shrink-0 flex flex-col bg-white rounded-xl border border-gray-200 overflow-hidden max-h-[50vh] lg:max-h-full shadow-sm">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                Reports
                @if($unreadCount > 0)
                    <span class="bg-red-500 text-white text-[10px] font-bold rounded-full px-2 py-0.5 animate-pulse"
                          aria-label="{{ $unreadCount }} unread">{{ $unreadCount }}</span>
                @endif
            </h3>
            <div class="flex items-center gap-2">
                <label for="inbox-filter" class="sr-only">Filter by status</label>
                <select wire:model.live="filterStatus" id="inbox-filter"
                    class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                    <option value="">All</option>
                    @foreach(['submitted','verified','flagged','escalated'] as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto divide-y divide-gray-50" role="list" aria-label="Report list">
            @forelse($reports as $report)
            <button wire:click="selectReport({{ $report->id }})"
                role="listitem"
                aria-selected="{{ $selectedReportId === $report->id ? 'true' : 'false' }}"
                class="w-full text-left p-4 transition-all duration-150 {{ $selectedReportId === $report->id ? 'bg-green-50 border-l-4 border-green-600' : 'border-l-4 border-transparent hover:bg-gray-50' }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $report->ward?->name ?? 'Ward #'.$report->ward_id }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $report->agent?->full_name }}</p>
                    </div>
                    @php $statusColors = [
                        'verified'  => 'bg-green-100 text-green-700',
                        'escalated' => 'bg-red-100 text-red-700',
                        'submitted' => 'bg-yellow-100 text-yellow-700',
                        'flagged'   => 'bg-orange-100 text-orange-700',
                    ]; @endphp
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium whitespace-nowrap shrink-0 {{ $statusColors[$report->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($report->status) }}
                    </span>
                </div>
                @if($report->pollingStation)
                <p class="text-[11px] text-blue-600 font-medium truncate mt-0.5">PU: {{ $report->pollingStation->code }} — {{ $report->pollingStation->name }}</p>
                @endif
                <div class="flex items-center gap-2 mt-1.5">
                    <span class="text-[11px] text-gray-400">{{ $report->created_at->diffForHumans() }}</span>
                    <span class="text-[11px] text-gray-300">&middot;</span>
                    <span class="text-[11px] text-gray-400 uppercase">{{ $report->submitted_via ?? 'web' }}</span>
                </div>
            </button>
            @empty
            <div class="flex flex-col items-center justify-center py-16 px-6">
                <svg class="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                <p class="text-sm text-gray-400 text-center">No reports yet</p>
                <p class="text-xs text-gray-300 mt-1">Reports will appear here as agents submit them</p>
            </div>
            @endforelse
        </div>
        @if($reports->hasPages())
        <div class="p-3 border-t border-gray-100 flex-shrink-0">
            <x-compact-pagination :paginator="$reports" />
        </div>
        @endif
    </div>

    {{-- Right Panel: Report Detail --}}
    <div class="flex-1 bg-white rounded-xl border border-gray-200 overflow-y-auto min-h-[300px] shadow-sm" aria-live="polite" aria-atomic="false">
        @if($selectedReport)
        <div class="p-5 sm:p-6">
            {{-- Header --}}
            <div class="flex flex-wrap items-start justify-between gap-4 mb-6 pb-4 border-b border-gray-100">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h2 class="text-lg font-bold text-gray-800">Report #{{ $selectedReport->id }}</h2>
                        @php $detailStatusColors = [
                            'verified'  => 'bg-green-100 text-green-700 border-green-200',
                            'escalated' => 'bg-red-100 text-red-700 border-red-200',
                            'submitted' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                            'flagged'   => 'bg-orange-100 text-orange-700 border-orange-200',
                        ]; @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium border {{ $detailStatusColors[$selectedReport->status] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
                            {{ ucfirst($selectedReport->status) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">{{ $selectedReport->ward?->name }}, {{ $selectedReport->lga?->name }}</p>
                    @if($selectedReport->pollingStation)
                    <p class="text-xs text-blue-600 font-medium mt-0.5">Polling Unit: {{ $selectedReport->pollingStation->code }} — {{ $selectedReport->pollingStation->name }}</p>
                    @endif
                    <p class="text-xs text-gray-400 mt-0.5">Submitted {{ $selectedReport->created_at->format('d M Y, H:i') }} ({{ $selectedReport->created_at->diffForHumans() }})</p>
                </div>

                {{-- Action buttons --}}
                <div class="flex flex-wrap gap-2">
                    @if(in_array($selectedReport->status, ['submitted','flagged']))
                    <button
                        wire:click="verify"
                        wire:confirm="Verify Report #{{ $selectedReport->id }}? This action cannot be undone."
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-lg transition disabled:opacity-60 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Verify
                    </button>
                    <button wire:click="openEscalateModal"
                        class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-lg transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Escalate
                    </button>
                    @endif
                    @if($selectedReport->status !== 'flagged')
                    <button wire:click="openFlagModal"
                        class="inline-flex items-center gap-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm px-4 py-2 rounded-lg transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                        Flag
                    </button>
                    @endif
                    <button wire:click="openClarificationModal"
                        class="inline-flex items-center gap-1.5 border border-gray-300 text-gray-700 text-sm px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Clarify
                    </button>
                </div>
            </div>

            {{-- Core data grid --}}
            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm mb-6">
                @foreach([
                    'Agent'             => $selectedReport->agent?->full_name,
                    'Submitted Via'     => strtoupper($selectedReport->submitted_via),
                    'INEC Agent'        => $selectedReport->inec_agent_name ?? '—',
                    'Counted Papers'    => number_format($selectedReport->counted_voter_papers ?? 0),
                    'Cast Votes'        => number_format($selectedReport->number_cast_votes),
                    'Valid Ballots'     => number_format($selectedReport->valid_ballot_papers),
                    'Invalid Ballots'   => number_format($selectedReport->invalid_ballot_papers),
                    'Papers Left'       => number_format($selectedReport->voting_papers_left ?? 0),
                    'Voting Closed'     => $selectedReport->voting_closed_time ?? '—',
                    'Security Present'  => $selectedReport->security_present ? 'Yes' : 'No',
                ] as $label => $value)
                <div class="bg-gray-50 rounded-lg p-3">
                    <dt class="text-gray-500 text-[10px] font-medium uppercase tracking-wide">{{ $label }}</dt>
                    <dd class="text-gray-800 font-medium mt-0.5">{{ $value }}</dd>
                </div>
                @endforeach
            </dl>

            {{-- PU-specific fields --}}
            @if($selectedReport->pollingStation)
            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm mb-6">
                @foreach([
                    'Polling Unit'       => $selectedReport->pollingStation->code . ' — ' . $selectedReport->pollingStation->name,
                    'EC8A Serial'        => $selectedReport->ec8a_serial_number ?? '—',
                    'Registered Voters'  => number_format($selectedReport->registered_voter_count_snapshot ?? 0),
                    'Turnout Rate'       => $selectedReport->turnout_rate ? number_format($selectedReport->turnout_rate, 1) . '%' : '—',
                ] as $label => $value)
                <div class="bg-blue-50 rounded-lg p-3">
                    <dt class="text-blue-500 text-[10px] font-medium uppercase tracking-wide">{{ $label }}</dt>
                    <dd class="text-gray-800 font-medium mt-0.5">{{ $value }}</dd>
                </div>
                @endforeach
            </dl>
            @endif

            {{-- Party Results (coloured bars) --}}
            @if($selectedReport->partyResults && $selectedReport->partyResults->count())
            @php
                $sortedResults = $selectedReport->partyResults->sortByDesc('votes_scored');
                $maxVotes = $sortedResults->max('votes_scored');
                $totalVotes = $sortedResults->sum('votes_scored');
            @endphp
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Party Results</p>
                    <span class="text-xs text-gray-400">{{ number_format($totalVotes) }} total votes</span>
                </div>
                <div class="bg-gray-50 rounded-lg border border-gray-100 p-3 space-y-3">
                    @foreach($sortedResults as $pr)
                    @php
                        $colour = $partyColours[$pr->party_name] ?? '#9ca3af';
                        $barWidth = $maxVotes > 0 ? round(($pr->votes_scored / $maxVotes) * 100, 1) : 0;
                        $votePercent = $totalVotes > 0 ? round(($pr->votes_scored / $totalVotes) * 100, 1) : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold text-white" style="background-color: {{ $colour }}">{{ $pr->party_name }}</span>
                                @if(!empty($candidateMap[$pr->party_name]))
                                    <span class="text-[11px] text-gray-500">{{ implode(', ', $candidateMap[$pr->party_name]) }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-gray-800 tabular-nums">{{ number_format($pr->votes_scored) }}</span>
                                <span class="text-[10px] text-gray-400 tabular-nums">({{ $votePercent }}%)</span>
                                @if($loop->first && $pr->votes_scored > 0)
                                    <svg class="w-3.5 h-3.5 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                @endif
                            </div>
                        </div>
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500" style="width: {{ $barWidth }}%; background-color: {{ $colour }}"></div>
                        </div>
                    </div>
                    @endforeach
                    {{-- Total row --}}
                    <div class="pt-2 border-t border-gray-200 flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-600">Total</span>
                        <span class="text-xs font-bold text-gray-800">{{ number_format($totalVotes) }}</span>
                    </div>
                </div>
            </div>
            @endif

            @if($selectedReport->security_present && $selectedReport->security_types)
            <div class="mb-4">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-2">Security Types</p>
                <div class="flex gap-2 flex-wrap">
                    @foreach($selectedReport->security_types as $type)
                    <span class="bg-orange-100 text-orange-700 text-xs px-2.5 py-1 rounded-lg font-medium">{{ $type }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($selectedReport->remarks)
            <div class="mb-4 bg-gray-50 rounded-lg p-4 text-sm text-gray-700">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Remarks</p>
                {{ $selectedReport->remarks }}
            </div>
            @endif

            @if($selectedReport->flag_reason)
            <div class="mb-4 bg-orange-50 border border-orange-200 rounded-lg p-4 text-sm text-orange-800" role="note">
                <p class="font-semibold mb-1 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                    Flagged
                </p>
                {{ $selectedReport->flag_reason }}
            </div>
            @endif

            @if($selectedReport->clarification_request)
            <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800" role="note">
                <p class="font-semibold mb-1 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Pending Clarification
                    <span class="text-xs font-normal text-blue-500">
                        {{ $selectedReport->clarification_sent_at?->diffForHumans() }}
                    </span>
                </p>
                {{ $selectedReport->clarification_request }}
            </div>
            @endif

            {{-- Supervisor Notes --}}
            <div class="border-t border-gray-100 pt-4 mt-2">
                <p class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Supervisor Notes
                </p>
                @if($selectedReport->supervisor_note)
                <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-600 whitespace-pre-wrap mb-3 font-mono border border-gray-100">{{ $selectedReport->supervisor_note }}</div>
                @endif
                <div class="flex gap-2">
                    <label for="supervisor-note-input" class="sr-only">Add supervisor note</label>
                    <textarea wire:model="supervisorNote" id="supervisor-note-input" rows="2"
                        placeholder="Add a note..."
                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition"></textarea>
                    <button wire:click="addNote" wire:loading.attr="disabled"
                        class="bg-gray-800 hover:bg-gray-900 text-white text-sm px-4 rounded-lg transition self-end py-2 disabled:opacity-60 shadow-sm">
                        Save
                    </button>
                </div>
                @error('supervisorNote') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
            </div>
        </div>
        @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center h-full min-h-[300px] text-center px-6">
            <svg class="w-16 h-16 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
            <p class="text-gray-400 font-medium">Select a report to view details</p>
            <p class="text-xs text-gray-300 mt-1">Click any report from the list on the left</p>
        </div>
        @endif
    </div>

    {{-- Escalate Modal --}}
    @if($showEscalateModal)
    <div role="dialog" aria-modal="true" aria-labelledby="escalate-modal-title"
        x-data x-init="$nextTick(() => document.getElementById('escalate-reason')?.focus())"
        @keydown.escape.window="$wire.set('showEscalateModal', false)"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-4">
                <h3 id="escalate-modal-title" class="font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    Escalate Report #{{ $selectedReportId }}
                </h3>
                <button wire:click="$set('showEscalateModal', false)" aria-label="Close dialog"
                    class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="mb-4">
                <label for="escalate-level" class="block text-sm text-gray-600 mb-1 font-medium">Escalation Level</label>
                <select wire:model="escalateLevel" id="escalate-level"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent transition">
                    <option value="1">Level 1 — LGA Coordinator (10 min)</option>
                    <option value="2">Level 2 — State Coordinator (30 min)</option>
                    <option value="3">Level 3 — Steering Committee (Immediate)</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="escalate-reason" class="block text-sm text-gray-600 mb-1 font-medium">Reason <span class="text-gray-400 font-normal">(min 10 chars)</span></label>
                <textarea wire:model="escalateReason" id="escalate-reason" rows="3"
                    placeholder="Describe the issue requiring escalation..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm resize-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition"></textarea>
                @error('escalateReason') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3">
                <button wire:click="escalate" wire:loading.attr="disabled"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 rounded-lg text-sm transition disabled:opacity-60 shadow-sm">
                    <span wire:loading.remove>Escalate</span>
                    <span wire:loading aria-live="polite">Escalating...</span>
                </button>
                <button wire:click="$set('showEscalateModal', false)"
                    class="flex-1 border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm hover:bg-gray-50 transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Flag Modal --}}
    @if($showFlagModal)
    <div role="dialog" aria-modal="true" aria-labelledby="flag-modal-title"
        x-data x-init="$nextTick(() => document.getElementById('flag-reason')?.focus())"
        @keydown.escape.window="$wire.set('showFlagModal', false)"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-4">
                <h3 id="flag-modal-title" class="font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                    </span>
                    Flag Report #{{ $selectedReportId }}
                </h3>
                <button wire:click="$set('showFlagModal', false)" aria-label="Close dialog"
                    class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="mb-4">
                <label for="flag-reason" class="block text-sm text-gray-600 mb-1 font-medium">Reason for Flagging <span class="text-gray-400 font-normal">(min 10 chars)</span></label>
                <textarea wire:model="flagReason" id="flag-reason" rows="3"
                    placeholder="Describe why this report is being flagged..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm resize-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"></textarea>
                @error('flagReason') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3">
                <button wire:click="flag" wire:loading.attr="disabled"
                    class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 rounded-lg text-sm transition disabled:opacity-60 shadow-sm">
                    <span wire:loading.remove>Flag Report</span>
                    <span wire:loading aria-live="polite">Flagging...</span>
                </button>
                <button wire:click="$set('showFlagModal', false)"
                    class="flex-1 border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm hover:bg-gray-50 transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Clarification Modal --}}
    @if($showClarificationModal)
    <div role="dialog" aria-modal="true" aria-labelledby="clarify-modal-title"
        x-data x-init="$nextTick(() => document.getElementById('clarify-question')?.focus())"
        @keydown.escape.window="$wire.set('showClarificationModal', false)"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-4">
                <h3 id="clarify-modal-title" class="font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    Request Clarification
                </h3>
                <button wire:click="$set('showClarificationModal', false)" aria-label="Close dialog"
                    class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-gray-500 mb-3 bg-blue-50 px-3 py-2 rounded-lg">Your question will be sent as an SMS to the agent and stored on the report.</p>
            <div class="mb-4">
                <label for="clarify-question" class="block text-sm text-gray-600 mb-1 font-medium">Question <span class="text-gray-400 font-normal">(min 10 chars)</span></label>
                <textarea wire:model="clarificationQuestion" id="clarify-question" rows="3"
                    placeholder="What do you need the agent to clarify?"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"></textarea>
                @error('clarificationQuestion') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3">
                <button wire:click="requestClarification" wire:loading.attr="disabled"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg text-sm transition disabled:opacity-60 shadow-sm">
                    <span wire:loading.remove>Send Request</span>
                    <span wire:loading aria-live="polite">Sending...</span>
                </button>
                <button wire:click="$set('showClarificationModal', false)"
                    class="flex-1 border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm hover:bg-gray-50 transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
