<div>
    {{-- Search & Action Bar --}}
    <div class="flex flex-wrap gap-2 sm:gap-3 mb-6 bg-white border border-gray-200 rounded-xl p-3 sm:p-4 shadow-sm">
        {{-- State Filter (National Deployment Only) --}}
        @if($isNationalDeployment && $states->count() > 1)
        <div>
            <label for="lga-filter-state" class="sr-only">Filter by State</label>
            <select wire:model.live="filterStateId" id="lga-filter-state"
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
            <label for="lga-search" class="sr-only">Search by name or code</label>
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" id="lga-search" type="search"
                    placeholder="Search name or code..."
                    class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
            </div>
        </div>
        <button wire:click="openCreate"
            class="ml-auto bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            New LGA
        </button>
    </div>

    {{-- LGA Table — Desktop --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm min-w-[500px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Name</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Code</th>
                        @if($isNationalDeployment)
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">State</th>
                        @endif
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Wards</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Map</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lgas as $lga)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center text-xs font-bold text-green-700 shrink-0">
                                    {{ strtoupper(substr($lga->name, 0, 2)) }}
                                </div>
                                <span class="font-medium text-gray-800">{{ $lga->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-600 font-mono text-xs">{{ $lga->code }}</td>
                        @if($isNationalDeployment)
                        <td class="px-5 py-3 text-gray-500 text-sm">{{ $lga->state?->name ?? '—' }}</td>
                        @endif
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ $lga->wards_count }}</span>
                        </td>
                        <td class="px-5 py-3">
                            @if($lga->boundary_geojson)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-700">Boundary</span>
                            @else
                                <span class="text-gray-300 text-xs">None</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-1">
                                <button wire:click="openEdit({{ $lga->id }})"
                                    class="text-green-700 hover:text-green-900 text-sm font-medium hover:bg-green-50 px-2 py-1 rounded transition">
                                    Edit
                                </button>
                                <button wire:click="confirmDelete({{ $lga->id }})"
                                    wire:confirm="Delete {{ $lga->name }}? This cannot be undone."
                                    class="text-red-600 hover:text-red-800 text-sm font-medium hover:bg-red-50 px-2 py-1 rounded transition">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ $isNationalDeployment ? 6 : 5 }}" class="px-5 py-16 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <p class="text-gray-400 text-sm">No LGAs found</p>
                        <p class="text-xs text-gray-300 mt-1">Try adjusting your search</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile: card view --}}
        <div class="sm:hidden divide-y divide-gray-100">
            @forelse($lgas as $lga)
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center text-xs font-bold text-green-700 shrink-0">
                            {{ strtoupper(substr($lga->name, 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 truncate">{{ $lga->name }}</p>
                            <p class="text-xs text-gray-500 font-mono">{{ $lga->code }}</p>
                        </div>
                    </div>
                    <button wire:click="openEdit({{ $lga->id }})"
                        class="text-green-700 text-sm font-medium shrink-0">Edit</button>
                </div>
                <div class="flex items-center gap-2 mt-2 ml-13">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700">{{ $lga->wards_count }} wards</span>
                </div>
            </div>
            @empty
            <div class="px-5 py-16 text-center">
                <p class="text-gray-400 text-sm">No LGAs found</p>
            </div>
            @endforelse
        </div>

        <div class="px-5 py-3 border-t border-gray-100 text-xs">{{ $lgas->links() }}</div>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="lga-modal-title"
        x-data
        x-init="$nextTick(() => $el.querySelector('input,select,button')?.focus())"
        @keydown.escape.window="$wire.set('showModal', false)"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
    >
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-5">
                <h3 id="lga-modal-title" class="font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $editingId ? 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' : 'M12 6v6m0 0v6m0-6h6m-6 0H6' }}"/></svg>
                    </span>
                    {{ $editingId ? 'Edit LGA' : 'Create LGA' }}
                </h3>
                <button wire:click="$set('showModal', false)" aria-label="Close dialog"
                    class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="save" class="space-y-4" novalidate>
                <div>
                    <label for="lga-name" class="block text-sm text-gray-600 mb-1 font-medium">
                        Name <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input wire:model="name" id="lga-name" type="text"
                        aria-required="true" placeholder="e.g. Dutse"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    @error('name') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="lga-code" class="block text-sm text-gray-600 mb-1 font-medium">
                        Code <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input wire:model="code" id="lga-code" type="text" maxlength="3"
                        aria-required="true" placeholder="e.g. DUT"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm uppercase focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    @error('code') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    <p class="text-xs text-gray-400 mt-1">3-letter alphabetic code (auto-uppercased)</p>
                </div>
                @if($isNationalDeployment && $states->count() > 0)
                <div>
                    <label for="lga-state" class="block text-sm text-gray-600 mb-1 font-medium">State</label>
                    <select wire:model="state_id" id="lga-state"
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
                <div>
                    <label for="lga-boundary" class="block text-sm text-gray-600 mb-1 font-medium">Boundary GeoJSON</label>
                    <textarea wire:model="boundary_geojson_raw" id="lga-boundary" rows="4"
                        placeholder='Paste GeoJSON geometry (Polygon/MultiPolygon)...'
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-xs font-mono focus:ring-2 focus:ring-green-500 focus:border-transparent transition"></textarea>
                    @error('boundary_geojson_raw') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    <p class="text-xs text-gray-400 mt-1">Optional. Paste a GeoJSON geometry object for map display.</p>
                </div>
                <p class="text-xs text-gray-400">Fields marked <span class="text-red-500">*</span> are required.</p>
                <div class="flex gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="flex-1 bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg text-sm transition disabled:opacity-60 shadow-sm">
                        <span wire:loading.remove>{{ $editingId ? 'Update LGA' : 'Create LGA' }}</span>
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
