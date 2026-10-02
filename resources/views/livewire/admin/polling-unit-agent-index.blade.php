<div>
    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">PU Agent Register</h2>
            <p class="text-sm text-gray-500 mt-0.5">APC Jigawa State Chapter — Polling Unit Data Collection</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm disabled:opacity-60">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span wire:loading.remove wire:target="exportCsv">CSV</span>
                <span wire:loading wire:target="exportCsv">Exporting…</span>
            </button>
            <button wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm disabled:opacity-60">
                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span wire:loading.remove wire:target="exportPdf">PDF</span>
                <span wire:loading wire:target="exportPdf">Exporting…</span>
            </button>
            <a href="{{ route('admin.pu-agents.create') }}" wire:navigate
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-green-700 hover:bg-green-800 rounded-lg transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Register Agent
            </a>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="flex flex-wrap gap-2 sm:gap-3 mb-5 bg-white border border-gray-200 rounded-xl p-3 sm:p-4 shadow-sm">
        <div class="flex-1 min-w-52">
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="search"
                    placeholder="Search name, NIN, or polling unit…"
                    class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
            </div>
        </div>
        <div>
            <select wire:model.live="filterLgaId"
                @if($isSupervisor) disabled @endif
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition disabled:bg-gray-100 disabled:cursor-not-allowed">
                <option value="">All LGAs</option>
                @foreach($lgas as $lga)
                    <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select wire:model.live="filterWardId"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All Wards</option>
                @foreach($wards as $ward)
                    <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Desktop table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm min-w-[720px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Agent</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">NIN</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Polling Unit</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">LGA / Ward</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Leadership Roles</th>
                        @if($isSuperAdmin)
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">BVN</th>
                        @endif
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($agents as $agent)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-100 to-green-200 flex items-center justify-center text-xs font-bold text-green-700 shrink-0">
                                        {{ strtoupper(substr($agent->first_name, 0, 1) . substr($agent->surname, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-800 truncate">{{ $agent->surname }}, {{ $agent->first_name }}</p>
                                        <p class="text-xs text-gray-400">{{ $agent->state }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-gray-500">
                                {{ substr($agent->nin, 0, 3) }}****{{ substr($agent->nin, -4) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="text-gray-700 font-medium truncate max-w-[160px]">{{ $agent->polling_unit }}</p>
                                <p class="text-xs text-gray-400">No. {{ $agent->polling_unit_number }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="text-gray-700">{{ $agent->lga?->name }}</p>
                                <p class="text-xs text-gray-400">{{ $agent->ward?->name }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap gap-1">
                                    @forelse(($agent->leadership_roles ?? []) as $role)
                                        <span class="inline-flex text-[10px] font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-800 whitespace-nowrap">
                                            {{ \App\Services\PollingUnitAgentService::LEADERSHIP_ROLES[$role] ?? $role }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            @if($isSuperAdmin)
                                <td class="px-5 py-3.5 font-mono text-xs text-gray-500">
                                    {{ $agent->bvn ?? '—' }}
                                </td>
                            @endif
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('admin.pu-agents.edit', $agent->id) }}" wire:navigate
                                        class="px-2.5 py-1 text-xs font-medium text-green-700 hover:text-green-900 hover:bg-green-50 rounded-lg transition">
                                        Edit
                                    </a>
                                    @if($isSuperAdmin)
                                        <button
                                            x-data
                                            x-on:click="if(confirm('Permanently delete this agent record? This cannot be undone.')) $wire.delete({{ $agent->id }})"
                                            wire:loading.attr="disabled"
                                            class="px-2.5 py-1 text-xs font-medium text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="px-5 py-16 text-center">
                                <svg class="mx-auto w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <p class="text-sm font-medium text-gray-400">No agents found</p>
                                <p class="text-xs text-gray-300 mt-1">Try adjusting the filters or register a new agent.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile card view --}}
        <div class="sm:hidden divide-y divide-gray-100">
            @forelse($agents as $agent)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-100 to-green-200 flex items-center justify-center text-sm font-bold text-green-700 shrink-0">
                                {{ strtoupper(substr($agent->first_name, 0, 1) . substr($agent->surname, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 truncate">{{ $agent->surname }}, {{ $agent->first_name }}</p>
                                <p class="text-xs text-gray-500">{{ $agent->polling_unit }} · {{ $agent->lga?->name }}</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.pu-agents.edit', $agent->id) }}" wire:navigate
                            class="text-sm font-medium text-green-700 shrink-0">Edit</a>
                    </div>
                    <div class="mt-2 pl-13 flex flex-wrap gap-1">
                        @foreach(($agent->leadership_roles ?? []) as $role)
                            <span class="inline-flex text-[10px] font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-800">
                                {{ \App\Services\PollingUnitAgentService::LEADERSHIP_ROLES[$role] ?? $role }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="px-5 py-16 text-center">
                    <p class="text-sm text-gray-400">No agents found.</p>
                </div>
            @endforelse
        </div>

        @if($agents->hasPages())
            <div class="px-5 py-3 border-t border-gray-100 text-xs">
                {{ $agents->links() }}
            </div>
        @endif
    </div>
</div>
