<div wire:poll.15s="loadData">
    {{-- Filter Bar --}}
    <div class="flex flex-wrap gap-2 sm:gap-3 mb-6 bg-white border border-gray-200 rounded-xl p-3 sm:p-4 shadow-sm" role="search" aria-label="Filter dashboard data">
        {{-- State Filter (National Deployment Only) --}}
        @if($isNationalDeployment && $states->count() > 1)
        <div class="w-full sm:w-auto">
            <label for="dash-state" class="sr-only">Filter by State</label>
            <select wire:model.live="filterStateId" id="dash-state"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                @if(auth()->user()->isStateAdmin()) disabled @endif>
                <option value="">All States</option>
                @foreach($states as $state)
                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="w-full sm:w-auto">
            <label for="dash-lga" class="sr-only">Filter by LGA</label>
            <select wire:model.live="filterLgaId" id="dash-lga"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All LGAs</option>
                @foreach($lgas as $lga)
                    <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-[calc(50%-4px)] sm:w-auto">
            <label for="dash-status" class="sr-only">Filter by status</label>
            <select wire:model.live="filterStatus" id="dash-status"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All Statuses</option>
                @foreach(['submitted','verified','flagged','escalated','draft'] as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-[calc(50%-4px)] sm:w-auto">
            <label for="dash-channel" class="sr-only">Filter by channel</label>
            <select wire:model.live="filterVia" id="dash-channel"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All Channels</option>
                @foreach(['web','android','ios','sms'] as $v)
                    <option value="{{ $v }}">{{ strtoupper($v) }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-[calc(50%-4px)] sm:w-auto">
            <label for="dash-from" class="sr-only">From date</label>
            <input wire:model.live.debounce.500ms="filterFrom" id="dash-from" type="date"
                aria-label="From date"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
        </div>
        <div class="w-[calc(50%-4px)] sm:w-auto">
            <label for="dash-to" class="sr-only">To date</label>
            <input wire:model.live.debounce.500ms="filterTo" id="dash-to" type="date"
                aria-label="To date"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
        </div>

        @if($filterStateId || $filterLgaId || $filterStatus || $filterVia || $filterFrom || $filterTo)
        <button wire:click="clearFilters"
            class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Clear
        </button>
        @endif

        <span class="sm:ml-auto text-xs text-gray-400 self-center flex items-center gap-1.5">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
            </span>
            Live — 15s
        </span>
    </div>

    {{-- KPI Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
        @php $kpis = [
            ['Total Reports',    $summary['total_reports'],       'from-green-50 to-emerald-50',  'text-green-800',    'border-green-200', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>'],
            ['Submitted',        $summary['submitted'],           'from-yellow-50 to-amber-50',   'text-yellow-700',   'border-yellow-200', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>'],
            ['Verified',         $summary['verified'],            'from-emerald-50 to-green-50',  'text-emerald-700',  'border-emerald-200','<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
            ['Escalated',        $summary['escalated'],           'from-red-50 to-rose-50',       'text-red-700',      'border-red-200',    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>'],
            ['Security Alerts',  $summary['security_incidents'],  'from-orange-50 to-amber-50',   'text-orange-700',   'border-orange-200', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01"/>'],
            ['Valid Votes',      number_format($summary['total_valid_votes']),  'from-purple-50 to-violet-50',  'text-purple-700',   'border-purple-200', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>'],
            ['Wards Reporting',  $summary['wards_reporting'].'/'.$summary['total_wards'],  'from-green-100 to-emerald-100', 'text-green-900', 'border-green-300', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>'],
            ['PU Coverage',      $summary['pus_reporting'].'/'.$summary['total_polling_stations'],  'from-blue-50 to-indigo-50', 'text-blue-800', 'border-blue-200', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>'],
            ['Active Today',     $summary['agents_logged_in_today'].'/'.$summary['total_agents'], 'from-teal-50 to-cyan-50', 'text-teal-700', 'border-teal-200', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>'],
        ]; @endphp

        @foreach($kpis as [$label, $value, $gradient, $textColor, $borderColor, $iconPath])
        <div class="group rounded-xl border {{ $borderColor }} bg-gradient-to-br {{ $gradient }} p-4 transition-all duration-200 hover:shadow-md hover:scale-[1.02] cursor-default"
             role="region" aria-label="{{ $label }}: {{ $value }}">
            <div class="flex items-start justify-between mb-2">
                <svg class="w-5 h-5 {{ $textColor }} opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $iconPath !!}</svg>
                <span class="text-[10px] font-medium {{ $textColor }} opacity-50 uppercase tracking-wide">{{ $label }}</span>
            </div>
            <div class="text-2xl sm:text-3xl font-bold {{ $textColor }} tabular-nums" wire:loading.class="opacity-50">{{ $value }}</div>
            <div class="text-xs font-medium {{ $textColor }} opacity-60 mt-1">{{ $label }}</div>
        </div>
        @endforeach
    </div>

    {{-- Map Widget --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6 overflow-hidden" wire:ignore
        x-data="pollingUnitMap(@js($mapPins))"
        x-init="init()"
        @charts-updated.window="updatePins($event.detail.mapPins)">

        {{-- Map Header --}}
        <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                <h3 class="font-semibold text-gray-700 text-sm">Polling Unit Map</h3>
                <span class="text-xs text-gray-400" x-text="pins.length + ' PUs'"></span>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <label class="flex items-center gap-1.5 cursor-pointer select-none">
                    <input type="checkbox" x-model="layers.pins" @change="toggleLayer('pins')" checked class="rounded border-gray-300 text-green-600 focus:ring-green-500 w-3.5 h-3.5">
                    <span class="text-gray-600">PU Pins</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer select-none">
                    <input type="checkbox" x-model="layers.lga" @change="toggleLayer('lga')" checked class="rounded border-gray-300 text-green-600 focus:ring-green-500 w-3.5 h-3.5">
                    <span class="text-gray-600">LGA Boundaries</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer select-none">
                    <input type="checkbox" x-model="layers.ward" @change="toggleLayer('ward')" checked class="rounded border-gray-300 text-green-600 focus:ring-green-500 w-3.5 h-3.5">
                    <span class="text-gray-600">Ward Boundaries</span>
                </label>
            </div>
        </div>

        {{-- RAG Legend --}}
        <div class="px-5 py-2 bg-gray-50 border-b border-gray-100 flex flex-wrap items-center gap-4 text-xs text-gray-500">
            <span class="font-medium text-gray-600">Coverage:</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-green-500/30 border border-green-600"></span> Green &ge;75%</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-yellow-500/30 border border-yellow-600"></span> Amber 40-74%</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-red-500/30 border border-red-600"></span> Red &lt;40%</span>
            <span class="border-l border-gray-300 pl-4 font-medium text-gray-600">Pins:</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-600"></span> Verified</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-yellow-600"></span> Submitted</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-orange-600"></span> Flagged</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-600"></span> Escalated</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span> No Report</span>
        </div>

        {{-- Map Container --}}
        <div x-ref="map" class="h-[400px] lg:h-[500px] w-full"></div>
    </div>

    {{-- Charts Row 1 --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5 shadow-sm" wire:ignore
            x-data="timelineChart(@js($charts['timeline']))"
            x-init="init()"
            @charts-updated.window="update($event.detail.charts.timeline)"
            role="img" aria-label="Submission timeline for the last 14 days">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Submissions — Last 14 Days</h4>
            <div x-show="!ready" class="animate-pulse"><div class="h-48 bg-gray-100 rounded-lg"></div></div>
            <div x-show="noData" class="h-48 flex flex-col items-center justify-center text-gray-400">
                <svg class="w-10 h-10 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                <span class="text-sm">No submission data yet</span>
            </div>
            <div x-show="ready && !noData" x-ref="chart"></div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm" wire:ignore
            x-data="donutChart(@js($charts['byStatus']), 'Report Status')"
            x-init="init()"
            @charts-updated.window="update($event.detail.charts.byStatus)"
            role="img" aria-label="Report status distribution">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Status Distribution</h4>
            <div x-show="!ready" class="animate-pulse"><div class="h-48 bg-gray-100 rounded-full mx-auto w-48"></div></div>
            <div x-show="noData" class="h-48 flex flex-col items-center justify-center text-gray-400">
                <svg class="w-10 h-10 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                <span class="text-sm">No data</span>
            </div>
            <div x-show="ready && !noData" x-ref="chart"></div>
        </div>
    </div>

    {{-- Charts Row 2 --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm" wire:ignore
            x-data="donutChart(@js($charts['byChannel']), 'Submission Channel')"
            x-init="init()"
            @charts-updated.window="update($event.detail.charts.byChannel)"
            role="img" aria-label="Submissions by channel">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Submissions by Channel</h4>
            <div x-show="!ready" class="animate-pulse"><div class="h-48 bg-gray-100 rounded-full mx-auto w-48"></div></div>
            <div x-show="noData" class="h-48 flex flex-col items-center justify-center text-gray-400">
                <svg class="w-10 h-10 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>
                <span class="text-sm">No data</span>
            </div>
            <div x-show="ready && !noData" x-ref="chart"></div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm" wire:ignore
            x-data="barChart(@js($charts['bySenatorial']), 'Reports')"
            x-init="init()"
            @charts-updated.window="update($event.detail.charts.bySenatorial)"
            role="img" aria-label="Reports by senatorial district">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">By Senatorial District</h4>
            <div x-show="!ready" class="animate-pulse"><div class="h-48 bg-gray-100 rounded-lg"></div></div>
            <div x-show="noData" class="h-48 flex flex-col items-center justify-center text-gray-400">
                <svg class="w-10 h-10 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span class="text-sm">No data</span>
            </div>
            <div x-show="ready && !noData" x-ref="chart"></div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm" wire:ignore
            x-data="donutChart(@js($charts['agentGender']), 'Agent Gender')"
            x-init="init()"
            @charts-updated.window="update($event.detail.charts.agentGender)"
            role="img" aria-label="Agent gender breakdown">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Agent Gender Breakdown</h4>
            <div x-show="!ready" class="animate-pulse"><div class="h-48 bg-gray-100 rounded-full mx-auto w-48"></div></div>
            <div x-show="noData" class="h-48 flex flex-col items-center justify-center text-gray-400">
                <svg class="w-10 h-10 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="text-sm">No data</span>
            </div>
            <div x-show="ready && !noData" x-ref="chart"></div>
        </div>
    </div>

    {{-- Party Vote Race + Leaderboard --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        {{-- Party Vote Race (horizontal bar chart) --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5 shadow-sm" wire:ignore
            x-data="partyRaceChart(@js($charts['partyVotes']))"
            x-init="init()"
            @charts-updated.window="update($event.detail.charts.partyVotes)"
            role="img" aria-label="Party vote race">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                <h4 class="text-sm font-semibold text-gray-700">Party Vote Race</h4>
            </div>
            <div x-show="!ready" class="animate-pulse"><div class="h-48 bg-gray-100 rounded-lg"></div></div>
            <div x-show="noData" class="h-48 flex flex-col items-center justify-center text-gray-400">
                <svg class="w-10 h-10 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span class="text-sm">No party vote data yet</span>
            </div>
            <div x-show="ready && !noData" x-ref="chart"></div>
        </div>

        {{-- Party Leaderboard --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"
            x-data="{ leaderboard: @js($partyLeaderboard) }"
            @charts-updated.window="leaderboard = $event.detail.partyLeaderboard || []">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                <h4 class="text-sm font-semibold text-gray-700">Party Leaderboard</h4>
            </div>

            <template x-if="leaderboard.length === 0">
                <div class="h-48 flex flex-col items-center justify-center text-gray-400">
                    <svg class="w-10 h-10 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    <span class="text-sm">No party vote data yet</span>
                </div>
            </template>

            <div class="space-y-3 max-h-[320px] overflow-y-auto">
                <template x-for="(party, idx) in leaderboard" :key="party.acronym">
                    <div>
                        <div class="flex items-center gap-2.5 mb-1">
                            {{-- Leader trophy --}}
                            <template x-if="idx === 0 && party.votes > 0">
                                <svg class="w-4 h-4 text-yellow-500 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M5 3h14l-1.5 5H20a1 1 0 011 1v1a5 5 0 01-3.5 4.77V16a2 2 0 01-2 2h-7a2 2 0 01-2-2v-1.23A5 5 0 012 10V9a1 1 0 011-1h1.5L3 3zm2.5 2l1 3.33h7l1-3.33H7.5z"/></svg>
                            </template>
                            <template x-if="idx !== 0 || party.votes === 0">
                                <span class="w-4 text-center text-[10px] font-bold text-gray-400 shrink-0" x-text="idx + 1"></span>
                            </template>

                            {{-- Party circle --}}
                            <template x-if="party.logo_url">
                                <img :src="party.logo_url" :alt="party.acronym" class="w-7 h-7 rounded-full object-cover border" :style="'border-color: ' + party.colour">
                            </template>
                            <template x-if="!party.logo_url">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0" :style="'background-color: ' + party.colour" x-text="party.acronym.substring(0,2)"></div>
                            </template>

                            <span class="text-xs font-bold text-gray-800" x-text="party.acronym"></span>
                            <span class="text-xs text-gray-500 ml-auto tabular-nums" x-text="party.votes.toLocaleString()"></span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700 ease-out" :style="'width: ' + party.percentage + '%; background-color: ' + party.colour"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- By LGA table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 text-sm">Submissions by LGA</h3>
            <span class="text-xs text-gray-400">{{ count($byLga) }} LGAs reporting</span>
        </div>
        @if(count($byLga))
        {{-- Desktop: table view --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm min-w-[400px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="text-left px-5 py-3 text-gray-500 font-medium text-xs uppercase tracking-wide">LGA</th>
                        <th scope="col" class="text-center px-5 py-3 text-gray-500 font-medium text-xs uppercase tracking-wide">Reports</th>
                        <th scope="col" class="text-center px-5 py-3 text-gray-500 font-medium text-xs uppercase tracking-wide">Escalated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($byLga as $row)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $row['lga']['name'] ?? 'Unknown' }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">{{ $row['count'] }}</span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if($row['escalated'] > 0)
                                <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">{{ $row['escalated'] }}</span>
                            @else
                                <span class="text-gray-300">0</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- Mobile: card view --}}
        <div class="sm:hidden divide-y divide-gray-100">
            @foreach($byLga as $row)
            <div class="px-4 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $row['lga']['name'] ?? 'Unknown' }}</p>
                    <p class="text-xs text-gray-400">{{ $row['count'] }} report{{ $row['count'] !== 1 ? 's' : '' }}</p>
                </div>
                @if($row['escalated'] > 0)
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">{{ $row['escalated'] }} esc.</span>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div class="px-5 py-16 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <p class="text-gray-400 text-sm">No data for the current filters.</p>
            @if($filterLgaId || $filterStatus || $filterVia || $filterFrom || $filterTo)
            <button wire:click="clearFilters" class="mt-2 text-sm text-green-600 hover:text-green-700 underline">Clear filters</button>
            @endif
        </div>
        @endif
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
function pollingUnitMap(initialPins) {
    return {
        map: null,
        pins: initialPins || [],
        pinLayer: null,
        lgaLayer: null,
        wardLayer: null,
        boundaryData: null,
        updateCount: 0,
        layers: { pins: true, lga: true, ward: true },

        statusColors: {
            verified:  '#16a34a',
            submitted: '#ca8a04',
            flagged:   '#ea580c',
            escalated: '#dc2626',
        },
        ragColors: {
            green: { fill: '#16a34a', opacity: 0.2 },
            amber: { fill: '#ca8a04', opacity: 0.2 },
            red:   { fill: '#dc2626', opacity: 0.2 },
        },

        // Jigawa State bounding box (with buffer)
        jigawaBounds: L.latLngBounds([[11.0, 8.5], [13.5, 10.8]]),

        init() {
            this.map = L.map(this.$refs.map, {
                center: [12.22, 9.56],
                zoom: 8,
                minZoom: 7,
                maxZoom: 18,
                maxBounds: this.jigawaBounds,
                maxBoundsViscosity: 1.0,
                zoomControl: true,
                preferCanvas: true,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 18,
            }).addTo(this.map);

            this.pinLayer  = L.layerGroup().addTo(this.map);
            this.lgaLayer  = L.layerGroup().addTo(this.map);
            this.wardLayer = L.layerGroup().addTo(this.map);

            // Zoom-adaptive ward visibility
            this.map.on('zoomend', () => {
                if (this.layers.ward) {
                    if (this.map.getZoom() < 10) {
                        this.map.removeLayer(this.wardLayer);
                    } else {
                        this.map.addLayer(this.wardLayer);
                    }
                }
            });

            this.renderPins();
            this.loadBoundaries();
        },

        renderPins() {
            this.pinLayer.clearLayers();
            this.pins.forEach(pu => {
                if (!pu.lat || !pu.lng) return;
                const color = pu.reported ? (this.statusColors[pu.status] || '#9ca3af') : '#9ca3af';
                const marker = L.circleMarker([pu.lat, pu.lng], {
                    radius: 6,
                    fillColor: color,
                    color: '#fff',
                    weight: 1.5,
                    fillOpacity: 0.85,
                });

                const statusLabel = pu.reported ? (pu.status || 'submitted').charAt(0).toUpperCase() + (pu.status || 'submitted').slice(1) : 'No Report';
                marker.bindTooltip(
                    `<div class="text-xs leading-relaxed">
                        <strong>${pu.name}</strong><br>
                        <span class="text-gray-500">${pu.code}</span><br>
                        Ward: ${pu.ward} | LGA: ${pu.lga}<br>
                        Voters: ${(pu.voters || 0).toLocaleString()}<br>
                        Status: <strong>${statusLabel}</strong>
                    </div>`,
                    { direction: 'top', offset: [0, -8] }
                );

                marker.on('click', () => {
                    window.location.href = `/supervisor/inbox?pu=${pu.id}`;
                });

                marker.addTo(this.pinLayer);
            });
        },

        async loadBoundaries() {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const resp = await fetch('/api/v1/dashboard/map/boundaries', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!resp.ok) return;
                this.boundaryData = await resp.json();
                this.renderBoundaries();
            } catch (e) {
                console.warn('Failed to load map boundaries:', e);
            }
        },

        renderBoundaries() {
            if (!this.boundaryData) return;

            this.lgaLayer.clearLayers();
            this.wardLayer.clearLayers();

            // LGA boundaries
            (this.boundaryData.lgas || []).forEach(lga => {
                if (!lga.geojson) return;
                const rag = this.ragColors[lga.rag] || this.ragColors.red;
                const layer = L.geoJSON(lga.geojson, {
                    style: {
                        color: rag.fill,
                        weight: 2.5,
                        fillColor: rag.fill,
                        fillOpacity: rag.opacity,
                        dashArray: null,
                    },
                });
                layer.bindTooltip(
                    `<div class="text-xs"><strong>${lga.name}</strong> (${lga.code})<br>PUs: ${lga.reporting}/${lga.total_pus} (${lga.coverage}%)</div>`,
                    { sticky: true }
                );
                layer.addTo(this.lgaLayer);
            });

            // Ward boundaries
            (this.boundaryData.wards || []).forEach(ward => {
                if (!ward.geojson) return;
                const rag = this.ragColors[ward.rag] || this.ragColors.red;
                const layer = L.geoJSON(ward.geojson, {
                    style: {
                        color: rag.fill,
                        weight: 1.5,
                        fillColor: rag.fill,
                        fillOpacity: rag.opacity * 0.8,
                        dashArray: '5,5',
                    },
                });
                layer.bindTooltip(
                    `<div class="text-xs"><strong>${ward.name}</strong> (${ward.code})<br>PUs: ${ward.reporting}/${ward.total_pus} (${ward.coverage}%)</div>`,
                    { sticky: true }
                );
                layer.addTo(this.wardLayer);
            });

            // Auto-hide wards if zoomed out
            if (this.map.getZoom() < 10 && this.layers.ward) {
                this.map.removeLayer(this.wardLayer);
            }
        },

        toggleLayer(name) {
            if (name === 'pins') {
                this.layers.pins ? this.pinLayer.addTo(this.map) : this.map.removeLayer(this.pinLayer);
            } else if (name === 'lga') {
                this.layers.lga ? this.lgaLayer.addTo(this.map) : this.map.removeLayer(this.lgaLayer);
            } else if (name === 'ward') {
                if (this.layers.ward && this.map.getZoom() >= 10) {
                    this.wardLayer.addTo(this.map);
                } else {
                    this.map.removeLayer(this.wardLayer);
                }
            }
        },

        updatePins(newPins) {
            this.pins = newPins || [];
            this.renderPins();
            this.updateCount++;
            // Reload boundaries every 5th update (~75s at 15s poll)
            if (this.updateCount % 5 === 0) {
                this.loadBoundaries();
            }
        },
    };
}

function partyRaceChart(data) {
    return {
        ready: false,
        noData: false,
        chartInst: null,
        data: data,
        init() {
            if (typeof ApexCharts === 'undefined') { this.noData = true; this.ready = true; return; }
            if (!this.data.series || !this.data.series.length) {
                this.noData = true; this.ready = true; return;
            }
            const chartHeight = Math.max(200, this.data.labels.length * 40);
            this.chartInst = new ApexCharts(this.$refs.chart, {
                chart: { type: 'bar', height: chartHeight, toolbar: { show: false }, animations: { enabled: true, easing: 'easeinout', speed: 800 } },
                series: [{ name: 'Votes', data: this.data.series }],
                xaxis: { categories: this.data.labels, labels: { style: { fontSize: '12px', fontWeight: 600, fontFamily: 'inherit' } } },
                colors: this.data.colours || ['#16a34a'],
                plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '65%', distributed: true, dataLabels: { position: 'top' } } },
                dataLabels: { enabled: true, formatter: function(val) { return val.toLocaleString(); }, offsetX: 30, style: { fontSize: '11px', fontWeight: 600, colors: ['#374151'] } },
                legend: { show: false },
                grid: { borderColor: '#f1f5f9', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
                tooltip: { y: { formatter: function(val) { return val.toLocaleString() + ' votes'; } } },
            });
            this.chartInst.render();
            this.ready = true;
        },
        update(newData) {
            if (!newData || !newData.series || !newData.series.length) {
                this.noData = true;
                return;
            }
            this.noData = false;
            if (!this.chartInst) {
                this.data = newData;
                this.init();
                return;
            }
            this.chartInst.updateOptions({
                xaxis: { categories: newData.labels || [] },
                colors: newData.colours || ['#16a34a'],
            }, false, false);
            this.chartInst.updateSeries([{ name: 'Votes', data: newData.series || [] }], true);
        }
    };
}

function donutChart(data, title) {
    return {
        ready: false,
        noData: false,
        chartInst: null,
        data: data,
        init() {
            if (typeof ApexCharts === 'undefined') { this.noData = true; this.ready = true; return; }
            if (!this.data.series || !this.data.series.length) {
                this.noData = true; this.ready = true; return;
            }
            this.chartInst = new ApexCharts(this.$refs.chart, {
                chart:  { type: 'donut', height: 220, toolbar: { show: false }, animations: { enabled: true, easing: 'easeinout', speed: 600 } },
                series: this.data.series,
                labels: this.data.labels,
                colors: ['#16a34a','#ca8a04','#dc2626','#ea580c','#7c3aed','#0891b2'],
                legend: { position: 'bottom', fontSize: '12px', fontFamily: 'inherit' },
                plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', fontSize: '13px', fontWeight: 600 } } } } },
                dataLabels: { enabled: false },
                stroke: { width: 2, colors: ['#fff'] },
            });
            this.chartInst.render();
            this.ready = true;
        },
        update(newData) {
            if (!this.chartInst || !newData) return;
            this.chartInst.updateOptions({ labels: newData.labels || [] }, false, false);
            this.chartInst.updateSeries(newData.series || [], true);
        }
    };
}

function barChart(data, seriesName) {
    return {
        ready: false,
        noData: false,
        chartInst: null,
        data: data,
        init() {
            if (typeof ApexCharts === 'undefined') { this.noData = true; this.ready = true; return; }
            if (!this.data.series || !this.data.series.length) {
                this.noData = true; this.ready = true; return;
            }
            this.chartInst = new ApexCharts(this.$refs.chart, {
                chart:  { type: 'bar', height: 220, toolbar: { show: false }, animations: { enabled: true, easing: 'easeinout', speed: 600 } },
                series: [{ name: seriesName, data: this.data.series }],
                xaxis:  { categories: this.data.labels, labels: { style: { fontSize: '11px', fontFamily: 'inherit' } } },
                colors: ['#16a34a'],
                plotOptions: { bar: { borderRadius: 6, horizontal: false, columnWidth: '55%' } },
                dataLabels: { enabled: false },
                grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            });
            this.chartInst.render();
            this.ready = true;
        },
        update(newData) {
            if (!this.chartInst || !newData) return;
            this.chartInst.updateOptions({ xaxis: { categories: newData.labels || [] } }, false, false);
            this.chartInst.updateSeries([{ name: seriesName, data: newData.series || [] }], true);
        }
    };
}

function timelineChart(data) {
    return {
        ready: false,
        noData: false,
        chartInst: null,
        data: data,
        init() {
            if (typeof ApexCharts === 'undefined') { this.noData = true; this.ready = true; return; }
            this.chartInst = new ApexCharts(this.$refs.chart, {
                chart:  { type: 'area', height: 220, toolbar: { show: false }, zoom: { enabled: false }, animations: { enabled: true, easing: 'easeinout', speed: 600 } },
                series: [{ name: 'Submissions', data: this.data.series }],
                xaxis:  { categories: this.data.labels, labels: { style: { fontSize: '11px', fontFamily: 'inherit' } } },
                colors: ['#16a34a'],
                fill:   { type: 'gradient', gradient: { opacityFrom: 0.45, opacityTo: 0.05, shadeIntensity: 1 } },
                stroke: { curve: 'smooth', width: 2.5 },
                dataLabels: { enabled: false },
                grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
                tooltip: { theme: 'dark' },
            });
            this.chartInst.render();
            this.ready = true;
        },
        update(newData) {
            if (!this.chartInst || !newData) return;
            this.chartInst.updateOptions({ xaxis: { categories: newData.labels || [] } }, false, false);
            this.chartInst.updateSeries([{ name: 'Submissions', data: newData.series || [] }], true);
        }
    };
}
</script>
@endpush
