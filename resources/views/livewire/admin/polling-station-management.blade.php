<div>
    {{-- Search & Filter Bar --}}
    <div class="flex flex-wrap gap-2 sm:gap-3 mb-6 bg-white border border-gray-200 rounded-xl p-3 sm:p-4 shadow-sm">
        <div class="flex-1 min-w-48">
            <label for="ps-search" class="sr-only">Search by name or code</label>
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" id="ps-search" type="search"
                    placeholder="Search name or code..."
                    class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
            </div>
        </div>
        <div>
            <label for="ps-filter-lga" class="sr-only">Filter by LGA</label>
            <select wire:model.live="filterLgaId" id="ps-filter-lga"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All LGAs</option>
                @foreach($lgas as $lga) <option value="{{ $lga->id }}">{{ $lga->name }}</option> @endforeach
            </select>
        </div>
        <div>
            <label for="ps-filter-ward" class="sr-only">Filter by Ward</label>
            <select wire:model.live="filterWardId" id="ps-filter-ward"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                @if(!$filterLgaId) disabled @endif>
                <option value="">All Wards</option>
                @foreach($filterWards as $ward) <option value="{{ $ward->id }}">{{ $ward->name }}</option> @endforeach
            </select>
        </div>
        <div class="hidden sm:block">
            <label for="ps-filter-active" class="sr-only">Filter by status</label>
            <select wire:model.live="filterActive" id="ps-filter-active"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
        <button wire:click="openCreate"
            class="ml-auto bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            New Station
        </button>
    </div>

    {{-- Polling Stations Table — Desktop --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Name</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Code</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Ward</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">LGA</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Voters</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Agent</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Status</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($stations as $station)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center text-xs font-bold text-green-700 shrink-0">
                                    {{ strtoupper(substr($station->name, 0, 2)) }}
                                </div>
                                <span class="font-medium text-gray-800 truncate max-w-[200px]">{{ $station->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-600 font-mono text-xs">{{ $station->code }}</td>
                        <td class="px-5 py-3 text-gray-500 text-sm">{{ $station->ward?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500 text-sm">{{ $station->lga?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600 text-sm">{{ $station->registered_voter_count ? number_format($station->registered_voter_count) : '—' }}</td>
                        <td class="px-5 py-3 text-gray-500 text-sm truncate max-w-[140px]">{{ $station->assignedAgent?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <button
                                wire:click="toggleActive({{ $station->id }})"
                                wire:confirm="{{ $station->is_active
                                    ? 'Deactivate this polling station?'
                                    : 'Activate this polling station?' }}"
                                class="{{ $station->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }} text-xs px-2.5 py-1 rounded-full font-medium transition"
                            >
                                {{ $station->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="px-5 py-3">
                            <button wire:click="openEdit({{ $station->id }})"
                                class="text-green-700 hover:text-green-900 text-sm font-medium hover:bg-green-50 px-2 py-1 rounded transition">
                                Edit
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-5 py-16 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <p class="text-gray-400 text-sm">No polling stations found</p>
                        <p class="text-xs text-gray-300 mt-1">Try adjusting your search or filters</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile: card view --}}
        <div class="sm:hidden divide-y divide-gray-100">
            @forelse($stations as $station)
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center text-xs font-bold text-green-700 shrink-0">
                            {{ strtoupper(substr($station->name, 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 truncate">{{ $station->name }}</p>
                            <p class="text-xs text-gray-500 font-mono">{{ $station->code }}</p>
                        </div>
                    </div>
                    <button wire:click="openEdit({{ $station->id }})"
                        class="text-green-700 text-sm font-medium shrink-0">Edit</button>
                </div>
                <div class="flex items-center gap-2 mt-2 ml-13 flex-wrap">
                    <span class="text-xs text-gray-400">{{ $station->ward?->name ?? '' }} / {{ $station->lga?->name ?? '' }}</span>
                    <span class="{{ $station->is_active ? 'text-green-600' : 'text-red-500' }} text-[10px] font-medium ml-auto">
                        {{ $station->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            @empty
            <div class="px-5 py-16 text-center">
                <p class="text-gray-400 text-sm">No polling stations found</p>
            </div>
            @endforelse
        </div>

        <div class="px-5 py-3 border-t border-gray-100 text-xs">{{ $stations->links() }}</div>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="ps-modal-title"
        x-data
        x-init="$nextTick(() => $el.querySelector('input,select,button')?.focus())"
        @keydown.escape.window="$wire.set('showModal', false)"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
    >
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-5">
                <h3 id="ps-modal-title" class="font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $editingId ? 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' : 'M12 6v6m0 0v6m0-6h6m-6 0H6' }}"/></svg>
                    </span>
                    {{ $editingId ? 'Edit Polling Station' : 'Create Polling Station' }}
                </h3>
                <button wire:click="$set('showModal', false)" aria-label="Close dialog"
                    class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="save" class="space-y-4" novalidate>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label for="ps-name" class="block text-sm text-gray-600 mb-1 font-medium">
                            Name <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input wire:model="name" id="ps-name" type="text"
                            aria-required="true" placeholder="e.g. ABAYA PRI. SCH"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                        @error('name') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ps-code" class="block text-sm text-gray-600 mb-1 font-medium">
                            Code <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input wire:model="code" id="ps-code" type="text" maxlength="20"
                            aria-required="true" placeholder="e.g. PU-DUT-001-01"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm uppercase focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                        @error('code') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ps-voters" class="block text-sm text-gray-600 mb-1 font-medium">Registered Voters</label>
                        <input wire:model="registered_voter_count" id="ps-voters" type="number" min="0"
                            placeholder="e.g. 500"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                        @error('registered_voter_count') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ps-lga" class="block text-sm text-gray-600 mb-1 font-medium">
                            LGA <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select wire:model.live="lga_id" id="ps-lga"
                            aria-required="true"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                            <option value="">Select LGA</option>
                            @foreach($lgas as $lga) <option value="{{ $lga->id }}">{{ $lga->name }}</option> @endforeach
                        </select>
                        @error('lga_id') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ps-ward" class="block text-sm text-gray-600 mb-1 font-medium">
                            Ward <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select wire:model="ward_id" id="ps-ward"
                            aria-required="true"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                            @if(!$lga_id) disabled @endif>
                            <option value="">Select Ward</option>
                            @foreach($formWards as $ward) <option value="{{ $ward->id }}">{{ $ward->name }}</option> @endforeach
                        </select>
                        @error('ward_id') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ps-lat" class="block text-sm text-gray-600 mb-1 font-medium">GPS Latitude</label>
                        <input wire:model="gps_latitude" id="ps-lat" type="text" inputmode="decimal"
                            placeholder="e.g. 11.7568"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                        @error('gps_latitude') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ps-lng" class="block text-sm text-gray-600 mb-1 font-medium">GPS Longitude</label>
                        <input wire:model="gps_longitude" id="ps-lng" type="text" inputmode="decimal"
                            placeholder="e.g. 9.3489"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                        @error('gps_longitude') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-green-600 focus:ring-green-500" />
                            Active
                        </label>
                    </div>
                </div>
                <p class="text-xs text-gray-400">Fields marked <span class="text-red-500">*</span> are required.</p>
                <div class="flex gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="flex-1 bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg text-sm transition disabled:opacity-60 shadow-sm">
                        <span wire:loading.remove>{{ $editingId ? 'Update Station' : 'Create Station' }}</span>
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
