<div>
    {{-- Filter Bar --}}
    <div class="flex flex-wrap gap-2 sm:gap-3 mb-5 bg-white border border-gray-200 rounded-xl p-3 sm:p-4 shadow-sm" role="search" aria-label="Filter audit logs">
        <div class="flex-1 min-w-48">
            <label for="audit-search" class="sr-only">Search entity, reason, or IP</label>
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" id="audit-search" type="search"
                    placeholder="Search entity, reason, IP..."
                    class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
            </div>
        </div>

        <div class="w-[calc(50%-4px)] sm:w-auto">
            <label for="audit-action" class="sr-only">Filter by action</label>
            <select wire:model.live="filterAction" id="audit-action"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All Actions</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}">{{ ucfirst($a) }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-[calc(50%-4px)] sm:w-auto">
            <label for="audit-role" class="sr-only">Filter by role</label>
            <select wire:model.live="filterRole" id="audit-role"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All Roles</option>
                @foreach($roles as $r)
                    <option value="{{ $r }}">{{ str_replace('_', ' ', ucfirst($r)) }}</option>
                @endforeach
            </select>
        </div>

        <div class="hidden sm:block">
            <label for="audit-user" class="sr-only">Filter by user</label>
            <select wire:model.live="filterUserId" id="audit-user"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All Users</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->full_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-[calc(50%-4px)] sm:w-auto">
            <label for="audit-from" class="sr-only">From date</label>
            <input wire:model.live="filterFrom" id="audit-from" type="date"
                aria-label="From date"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
        </div>

        <div class="w-[calc(50%-4px)] sm:w-auto">
            <label for="audit-to" class="sr-only">To date</label>
            <input wire:model.live="filterTo" id="audit-to" type="date"
                aria-label="To date"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
        </div>

        <a href="{{ $exportUrl }}"
            class="inline-flex items-center gap-1.5 bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm"
            aria-label="Export filtered audit log as CSV">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export
        </a>

        @if($filterAction || $filterRole || $filterUserId || $filterFrom || $filterTo || $search)
        <button wire:click="clearFilters"
            class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Clear
        </button>
        @endif
    </div>

    {{-- Immutability notice --}}
    <div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2.5 text-xs text-amber-800 flex items-center gap-2 shadow-sm" role="note">
        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        <strong>Append-only:</strong> Audit logs are immutable — no entries can be modified or deleted.
    </div>

    {{-- Table — Desktop --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">ID</th>
                        <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Actor</th>
                        <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Role</th>
                        <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Action</th>
                        <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Entity</th>
                        <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Reason</th>
                        <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">IP</th>
                        <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition-colors text-xs">
                        <td class="px-4 py-3 font-mono text-gray-400">{{ $log->id }}</td>
                        <td class="px-4 py-3 text-gray-800 font-medium">{{ $log->actor?->full_name ?? 'System' }}</td>
                        <td class="px-4 py-3">
                            @php $roleColors = [
                                'super_admin' => 'bg-purple-100 text-purple-700',
                                'supervisor'  => 'bg-green-100 text-green-800',
                                'ward_agent'  => 'bg-gray-100 text-gray-600',
                            ]; @endphp
                            <span class="px-1.5 py-0.5 rounded text-xs font-medium {{ $roleColors[$log->actor_role] ?? '' }}">
                                {{ str_replace('_', ' ', $log->actor_role) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @php $actionColors = [
                                'delete'   => 'bg-red-100 text-red-700',
                                'escalate' => 'bg-red-100 text-red-700',
                                'flag'     => 'bg-red-100 text-red-700',
                                'create'   => 'bg-green-100 text-green-700',
                                'login'    => 'bg-green-100 text-green-700',
                                'verify'   => 'bg-green-100 text-green-700',
                                'update'   => 'bg-yellow-100 text-yellow-700',
                                'logout'   => 'bg-yellow-100 text-yellow-700',
                            ]; @endphp
                            <span class="px-1.5 py-0.5 rounded font-medium {{ $actionColors[$log->action_type] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $log->action_type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $log->entity_type }} #{{ $log->entity_id }}</td>
                        <td class="px-4 py-3 text-gray-500 max-w-48">
                            <span class="block truncate" title="{{ $log->reason }}">{{ $log->reason ?? '—' }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-gray-400">{{ $log->ip_address ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-400 whitespace-nowrap">{{ $log->created_at->format('d M y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-16 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p class="text-gray-400 text-sm">No audit log entries found</p>
                        <p class="text-xs text-gray-300 mt-1">Try adjusting your filters</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile: card view --}}
        <div class="sm:hidden divide-y divide-gray-100">
            @forelse($logs as $log)
            <div class="p-4">
                <div class="flex items-start justify-between gap-2 mb-1.5">
                    <div>
                        <span class="text-xs font-medium text-gray-800">{{ $log->actor?->full_name ?? 'System' }}</span>
                        <span class="text-gray-300 mx-1">&middot;</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium {{ $actionColors[$log->action_type] ?? 'bg-gray-100 text-gray-600' }}">{{ $log->action_type }}</span>
                    </div>
                    <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ $log->created_at->format('d M H:i') }}</span>
                </div>
                <p class="text-xs text-gray-600">{{ $log->entity_type }} #{{ $log->entity_id }}</p>
                @if($log->reason)
                <p class="text-xs text-gray-400 mt-1 truncate">{{ $log->reason }}</p>
                @endif
            </div>
            @empty
            <div class="px-4 py-16 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="text-gray-400 text-sm">No entries found</p>
            </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-gray-100 text-xs">{{ $logs->links() }}</div>
    </div>
</div>
