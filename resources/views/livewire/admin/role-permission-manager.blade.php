<div class="space-y-6">

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Role &amp; Permission Management</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Toggle permissions per role. Changes apply immediately.
                <span class="text-green-600 font-medium">Super Admin</span> always retains all permissions.
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            @if($lastSaved)
            <span class="text-xs text-green-600 font-medium flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Saved at {{ $lastSaved }}
            </span>
            @endif
            <button
                wire:click="syncPermissions"
                wire:confirm="Sync all permissions from system defaults? Missing permissions will be created and roles will receive their default grants."
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 text-sm font-medium text-blue-700 border border-blue-300 bg-blue-50 hover:bg-blue-100 px-3 py-2 rounded-lg transition"
                title="Run if permissions are missing from the database"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Sync Permissions
            </button>
            <button
                wire:click="resetAll"
                wire:confirm="Reset ALL roles to system defaults? This will overwrite any custom permissions."
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 border border-gray-300 bg-white hover:bg-gray-50 px-3 py-2 rounded-lg transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Reset All to Defaults
            </button>
        </div>
    </div>

    {{-- Role legend --}}
    @php
    $roleConfig = [
        'super_admin'       => ['label' => 'Super Admin',       'color' => 'bg-violet-100 text-violet-700 border-violet-200',  'dot' => 'bg-violet-500', 'locked' => true],
        'state_admin'       => ['label' => 'State Admin',       'color' => 'bg-purple-100 text-purple-700 border-purple-200',  'dot' => 'bg-purple-500', 'locked' => false],
        'supervisor'        => ['label' => 'Supervisor',         'color' => 'bg-amber-100  text-amber-700  border-amber-200',   'dot' => 'bg-amber-500',  'locked' => false],
        'ward_agent'        => ['label' => 'Ward Agent',         'color' => 'bg-green-100  text-green-700  border-green-200',   'dot' => 'bg-green-500',  'locked' => false],
        'polling_unit_agent'=> ['label' => 'PU Agent',          'color' => 'bg-teal-100   text-teal-700   border-teal-200',    'dot' => 'bg-teal-500',   'locked' => false],
    ];
    @endphp

    <div class="flex flex-wrap gap-2">
        @foreach($roleNames as $role)
        @php $cfg = $roleConfig[$role] ?? ['label' => $role, 'color' => 'bg-gray-100 text-gray-600 border-gray-200', 'dot' => 'bg-gray-400', 'locked' => false]; @endphp
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $cfg['color'] }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $cfg['dot'] }}"></span>
            {{ $cfg['label'] }}
            @if($cfg['locked'])
            <svg class="w-3 h-3 opacity-60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            @endif
        </span>
        @endforeach
        <span class="text-xs text-gray-400 flex items-center gap-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            = locked (always granted)
        </span>
    </div>

    {{-- Permission matrix --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

        {{-- Sticky column header --}}
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[720px]">
            <thead class="sticky top-0 z-10">
                <tr class="bg-gray-900 text-white">
                    <th class="px-5 py-3.5 text-left font-semibold text-gray-200 w-64">Permission</th>
                    @foreach($roleNames as $role)
                    @php $cfg = $roleConfig[$role] ?? []; @endphp
                    <th class="px-3 py-3.5 text-center font-semibold min-w-[110px]">
                        <div class="flex flex-col items-center gap-1">
                            <span class="text-xs text-gray-100">{{ $cfg['label'] ?? $role }}</span>
                            @if(!($cfg['locked'] ?? false))
                            <button
                                wire:click="resetRole('{{ $role }}')"
                                wire:confirm="Reset {{ $cfg['label'] ?? $role }} to defaults?"
                                class="text-[10px] text-gray-400 hover:text-gray-200 underline underline-offset-2 transition"
                                title="Reset to defaults"
                            >reset</button>
                            @else
                            <span class="text-[10px] text-violet-400">immutable</span>
                            @endif
                        </div>
                    </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @foreach($groups as $groupName => $permissions)

                {{-- Group header row --}}
                <tr class="bg-gray-50">
                    <td colspan="{{ count($roleNames) + 1 }}" class="px-5 py-2">
                        <span class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">{{ $groupName }}</span>
                    </td>
                </tr>

                {{-- Permission rows --}}
                @foreach($permissions as $permName => $permLabel)
                <tr class="hover:bg-gray-50/60 transition group">
                    {{-- Permission label --}}
                    <td class="px-5 py-3.5">
                        <div class="font-medium text-gray-800">{{ $permLabel }}</div>
                        <div class="text-[11px] text-gray-400 font-mono mt-0.5">{{ $permName }}</div>
                    </td>

                    {{-- Toggle cells --}}
                    @foreach($roleNames as $role)
                    @php
                        $granted = $matrix[$role][$permName] ?? false;
                        $locked  = $role === 'super_admin';
                        $cfg     = $roleConfig[$role] ?? [];
                    @endphp
                    <td class="px-3 py-3.5 text-center">
                        @if($locked)
                            {{-- Super Admin: always granted, visually locked --}}
                            <div class="inline-flex items-center justify-center w-10 h-6 rounded-full bg-violet-500 cursor-not-allowed opacity-80" title="Always granted">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                            </div>
                        @else
                            <button
                                wire:click="toggle('{{ $role }}', '{{ $permName }}')"
                                wire:loading.attr="disabled"
                                aria-label="{{ $granted ? 'Revoke' : 'Grant' }} {{ $permLabel }} for {{ $cfg['label'] ?? $role }}"
                                aria-pressed="{{ $granted ? 'true' : 'false' }}"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-1
                                    {{ $granted ? 'bg-green-500' : 'bg-gray-200' }}"
                            >
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out
                                    {{ $granted ? 'translate-x-5' : 'translate-x-0' }}"
                                ></span>
                            </button>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach

                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    {{-- Coverage summary cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        @foreach($roleNames as $role)
        @php
            $cfg      = $roleConfig[$role] ?? ['label' => $role, 'color' => 'bg-gray-100 text-gray-700 border-gray-200', 'dot' => 'bg-gray-400'];
            $total    = collect($groups)->flatMap(fn($p) => array_keys($p))->count();
            $granted  = $role === 'super_admin' ? $total : count(array_filter($matrix[$role] ?? []));
        @endphp
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center gap-1.5 mb-2">
                <span class="w-2 h-2 rounded-full {{ $cfg['dot'] }}"></span>
                <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wide truncate">{{ $cfg['label'] }}</span>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ $granted }}</div>
            <div class="text-xs text-gray-400 mt-0.5">of {{ $total }} permissions</div>
            <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full {{ $cfg['dot'] }}"
                     style="width: {{ $total > 0 ? round(($granted / $total) * 100) : 0 }}%">
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Info note --}}
    <div class="flex items-start gap-3 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-sm text-blue-700">
        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>
            Changes apply immediately to all active sessions. Use <code class="bg-blue-100 px-1 rounded font-mono text-xs">$user->can('permission.name')</code>
            in your code to check these permissions. All role-based middleware and policies are the authoritative enforcement layer.
        </span>
    </div>

</div>
