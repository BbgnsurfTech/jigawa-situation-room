<div>
    {{-- Search & Filter Bar --}}
    <div class="flex flex-wrap gap-2 sm:gap-3 mb-6 bg-white border border-gray-200 rounded-xl p-3 sm:p-4 shadow-sm">
        {{-- State Filter (National Deployment Only) --}}
        @if($isNationalDeployment && $states->count() > 1)
        <div>
            <label for="user-filter-state" class="sr-only">Filter by State</label>
            <select wire:model.live="filterStateId" id="user-filter-state"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                @if(auth()->user()->isStateAdmin()) disabled @endif>
                <option value="">All States</option>
                @foreach($states as $state)
                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="flex-1 min-w-48">
            <label for="user-search" class="sr-only">Search by name or phone</label>
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" id="user-search" type="search"
                    placeholder="Search name or phone..."
                    class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
            </div>
        </div>
        <div>
            <label for="user-filter-role" class="sr-only">Filter by role</label>
            <select wire:model.live="filterRole" id="user-filter-role"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All Roles</option>
                <option value="super_admin">Super Admin</option>
                @if($isNationalDeployment)
                <option value="state_admin">State Admin</option>
                @endif
                <option value="supervisor">Supervisor</option>
                <option value="ward_agent">Ward Agent</option>
                <option value="polling_unit_agent">PU Agent</option>
            </select>
        </div>
        <div class="hidden sm:block">
            <label for="user-filter-lga" class="sr-only">Filter by LGA</label>
            <select wire:model.live="filterLgaId" id="user-filter-lga"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All LGAs</option>
                @foreach($lgas as $lga) <option value="{{ $lga->id }}">{{ $lga->name }}</option> @endforeach
            </select>
        </div>
        <button wire:click="openCreate"
            class="ml-auto bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            New User
        </button>
    </div>

    {{-- Users Table — Desktop --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm min-w-[600px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Name</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Phone</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Role</th>
                        @if($isNationalDeployment)
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">State</th>
                        @endif
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">LGA</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Status</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center text-xs font-bold text-green-700 shrink-0">
                                    {{ strtoupper(substr($user->full_name ?? 'U', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-800 truncate">{{ $user->full_name }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-600 font-mono text-xs">{{ $user->phone }}</td>
                        <td class="px-5 py-3">
                            @php $roleColors = [
                                'super_admin'        => 'bg-purple-100 text-purple-700',
                                'state_admin'        => 'bg-indigo-100 text-indigo-700',
                                'supervisor'         => 'bg-green-100 text-green-800',
                                'ward_agent'         => 'bg-gray-100 text-gray-700',
                                'polling_unit_agent' => 'bg-blue-100 text-blue-700',
                            ]; @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $roleColors[$user->role] ?? '' }}">
                                {{ str_replace('_', ' ', ucfirst($user->role)) }}
                            </span>
                        </td>
                        @if($isNationalDeployment)
                        <td class="px-5 py-3 text-gray-500 text-sm">{{ $user->state?->name ?? '—' }}</td>
                        @endif
                        <td class="px-5 py-3 text-gray-500 text-sm">{{ $user->lga?->name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <button
                                wire:click="toggleActive({{ $user->id }})"
                                wire:confirm="{{ $user->is_active
                                    ? 'Deactivate ' . $user->full_name . '? They will be logged out and unable to sign in.'
                                    : 'Activate ' . $user->full_name . '?' }}"
                                class="{{ $user->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }} text-xs px-2.5 py-1 rounded-full font-medium transition"
                                aria-label="{{ $user->is_active ? 'Deactivate ' : 'Activate ' }}{{ $user->full_name }}"
                            >
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="px-5 py-3">
                            <button wire:click="openEdit({{ $user->id }})"
                                class="text-green-700 hover:text-green-900 text-sm font-medium hover:bg-green-50 px-2 py-1 rounded transition">
                                Edit
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ $isNationalDeployment ? 7 : 6 }}" class="px-5 py-16 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="text-gray-400 text-sm">No users found</p>
                        <p class="text-xs text-gray-300 mt-1">Try adjusting your search or filters</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile: card view --}}
        <div class="sm:hidden divide-y divide-gray-100">
            @forelse($users as $user)
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center text-xs font-bold text-green-700 shrink-0">
                            {{ strtoupper(substr($user->full_name ?? 'U', 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 truncate">{{ $user->full_name }}</p>
                            <p class="text-xs text-gray-500 font-mono">{{ $user->phone }}</p>
                        </div>
                    </div>
                    <button wire:click="openEdit({{ $user->id }})"
                        class="text-green-700 text-sm font-medium shrink-0">Edit</button>
                </div>
                <div class="flex items-center gap-2 mt-2 ml-13 pl-13">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $roleColors[$user->role] ?? '' }}">
                        {{ str_replace('_', ' ', ucfirst($user->role)) }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $user->lga?->name ?? '' }}</span>
                    <span class="{{ $user->is_active ? 'text-green-600' : 'text-red-500' }} text-[10px] font-medium ml-auto">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            @empty
            <div class="px-5 py-16 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <p class="text-gray-400 text-sm">No users found</p>
            </div>
            @endforelse
        </div>

        <div class="px-5 py-3 border-t border-gray-100 text-xs">{{ $users->links() }}</div>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="user-modal-title"
        x-data
        x-init="$nextTick(() => $el.querySelector('input,select,button')?.focus())"
        @keydown.escape.window="$wire.set('showModal', false)"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
    >
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-5">
                <h3 id="user-modal-title" class="font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $editingUserId ? 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' : 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' }}"/></svg>
                    </span>
                    {{ $editingUserId ? 'Edit User' : 'Create User' }}
                </h3>
                <button wire:click="$set('showModal', false)" aria-label="Close dialog"
                    class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit="save" class="space-y-4" novalidate>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label for="um-full-name" class="block text-sm text-gray-600 mb-1 font-medium">
                            Full Name <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input wire:model="full_name" id="um-full-name" type="text"
                            aria-required="true" placeholder="Enter full name"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                        @error('full_name') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="um-phone" class="block text-sm text-gray-600 mb-1 font-medium">
                            Phone <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input wire:model="phone" id="um-phone" type="tel" inputmode="numeric"
                            aria-required="true" placeholder="08012345678"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                        @error('phone') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="um-email" class="block text-sm text-gray-600 mb-1 font-medium">Email</label>
                        <input wire:model="email" id="um-email" type="email" autocomplete="email" placeholder="user@example.com"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                        @error('email') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    @if(!$editingUserId)
                    <div class="col-span-2">
                        <label for="um-password" class="block text-sm text-gray-600 mb-1 font-medium">
                            Password <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input wire:model="password" id="um-password" type="password"
                            autocomplete="new-password" aria-required="true" placeholder="Minimum 8 characters"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                        @error('password') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    @endif
                    <div>
                        <label for="um-role" class="block text-sm text-gray-600 mb-1 font-medium">
                            Role <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select wire:model.live="role" id="um-role"
                            aria-required="true"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                            <option value="ward_agent">Ward Agent</option>
                            <option value="polling_unit_agent">Polling Unit Agent</option>
                            <option value="supervisor">Supervisor</option>
                            @if($isNationalDeployment)
                            <option value="state_admin">State Admin</option>
                            @endif
                            <option value="super_admin">Super Admin</option>
                        </select>
                        @error('role') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="um-gender" class="block text-sm text-gray-600 mb-1 font-medium">Gender</label>
                        <select wire:model="gender" id="um-gender"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                            <option value="">Not specified</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                        @error('gender') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    {{-- State Selection (National Deployment) --}}
                    @if($isNationalDeployment && $states->count() > 0 && in_array($role, ['state_admin','supervisor','ward_agent','polling_unit_agent']))
                    <div>
                        <label for="um-state" class="block text-sm text-gray-600 mb-1 font-medium">State</label>
                        <select wire:model.live="state_id" id="um-state"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                            @if(auth()->user()->isStateAdmin()) disabled @endif>
                            <option value="">Select State</option>
                            @foreach($states as $state)
                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                            @endforeach
                        </select>
                        @error('state_id') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    @endif

                    @if(in_array($role, ['supervisor','ward_agent','polling_unit_agent']))
                    <div>
                        <label for="um-lga" class="block text-sm text-gray-600 mb-1 font-medium">LGA</label>
                        <select wire:model.live="lga_id" id="um-lga"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                            <option value="">Select LGA</option>
                            @foreach($lgas as $lga) <option value="{{ $lga->id }}">{{ $lga->name }}</option> @endforeach
                        </select>
                    </div>
                    @if(in_array($role, ['ward_agent','polling_unit_agent']))
                    <div>
                        <label for="um-ward" class="block text-sm text-gray-600 mb-1 font-medium">Ward</label>
                        <select wire:model.live="ward_id" id="um-ward"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                            <option value="">Select Ward</option>
                            @foreach($wards as $ward) <option value="{{ $ward->id }}">{{ $ward->name }}</option> @endforeach
                        </select>
                    </div>
                    @endif
                    @if($role === 'polling_unit_agent' && $pollingStations->isNotEmpty())
                    <div class="col-span-2">
                        <label for="um-pu" class="block text-sm text-gray-600 mb-1 font-medium">Polling Station</label>
                        <select wire:model="polling_station_id" id="um-pu"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                            <option value="">Select Polling Station</option>
                            @foreach($pollingStations as $ps) <option value="{{ $ps->id }}">{{ $ps->name }} ({{ $ps->code }})</option> @endforeach
                        </select>
                    </div>
                    @endif
                    @endif
                    <div class="col-span-2 flex gap-6 pt-1">
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-green-600 focus:ring-green-500" />
                            Active
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" wire:model="mfa_enabled" class="rounded border-gray-300 text-green-600 focus:ring-green-500" />
                            MFA Enabled
                        </label>
                    </div>
                </div>
                <p class="text-xs text-gray-400">Fields marked <span class="text-red-500">*</span> are required.</p>
                <div class="flex gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="flex-1 bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg text-sm transition disabled:opacity-60 shadow-sm">
                        <span wire:loading.remove>{{ $editingUserId ? 'Update User' : 'Create User' }}</span>
                        <span wire:loading aria-live="polite">Saving...</span>
                    </button>
                    <button type="button" wire:click="$set('showModal', false)"
                        class="flex-1 border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm hover:bg-gray-50 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
