<div>
    {{-- Filter Bar --}}
    <div class="flex flex-wrap gap-2 sm:gap-3 mb-6 bg-white border border-gray-200 rounded-xl p-3 sm:p-4 shadow-sm">
        <div class="flex-1 min-w-48">
            <label for="cand-search" class="sr-only">Search by name</label>
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" id="cand-search" type="search"
                    placeholder="Search candidate name..."
                    class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
            </div>
        </div>
        <div>
            <label for="cand-party" class="sr-only">Filter by party</label>
            <select wire:model.live="filterPartyId" id="cand-party"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All Parties</option>
                @foreach($allParties as $p)
                    <option value="{{ $p->id }}">{{ $p->acronym }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="cand-post" class="sr-only">Filter by post type</label>
            <select wire:model.live="filterPostType" id="cand-post"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All Posts</option>
                @foreach($postTypeLabels as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="cand-lga" class="sr-only">Filter by LGA</label>
            <select wire:model.live="filterLgaId" id="cand-lga"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                <option value="">All LGAs</option>
                @foreach($allLgas as $lga)
                    <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                @endforeach
            </select>
        </div>
        <button wire:click="openCreate"
            class="ml-auto bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            New Candidate
        </button>
    </div>

    {{-- Candidate Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Candidate</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Party</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Post</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Scope</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($candidates as $candidate)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                {{-- Party-coloured left accent --}}
                                <div class="w-1 h-8 rounded-full" style="background-color: {{ $candidate->party?->colour_hex ?? '#9ca3af' }}"></div>
                                <span class="font-medium text-gray-800">{{ $candidate->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            @if($candidate->party)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold text-white" style="background-color: {{ $candidate->party->colour_hex }}">
                                {{ $candidate->party->acronym }}
                            </span>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @php
                                $postColors = [
                                    'governorship' => 'bg-purple-100 text-purple-700',
                                    'senate' => 'bg-blue-100 text-blue-700',
                                    'house_of_reps' => 'bg-teal-100 text-teal-700',
                                    'state_assembly' => 'bg-amber-100 text-amber-700',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $postColors[$candidate->post_type] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $postTypeLabels[$candidate->post_type] ?? ucfirst($candidate->post_type) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-600">
                            @if(in_array($candidate->post_type, ['governorship', 'senate']))
                                <span class="text-xs text-purple-600 font-medium">Statewide</span>
                            @elseif($candidate->lga)
                                {{ $candidate->lga->name }}
                                @if($candidate->ward)
                                    <span class="text-gray-400">/</span> {{ $candidate->ward->name }}
                                @endif
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-1">
                                <button wire:click="openEdit({{ $candidate->id }})"
                                    class="text-green-700 hover:text-green-900 text-sm font-medium hover:bg-green-50 px-2 py-1 rounded transition">
                                    Edit
                                </button>
                                <button wire:click="confirmDelete({{ $candidate->id }})"
                                    wire:confirm="Delete {{ $candidate->name }}? This cannot be undone."
                                    class="text-red-600 hover:text-red-800 text-sm font-medium hover:bg-red-50 px-2 py-1 rounded transition">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-16 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <p class="text-gray-400 text-sm">No candidates found</p>
                        <p class="text-xs text-gray-300 mt-1">Adjust filters or add a new candidate</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile: card view --}}
        <div class="sm:hidden divide-y divide-gray-100">
            @forelse($candidates as $candidate)
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-1 h-10 rounded-full shrink-0" style="background-color: {{ $candidate->party?->colour_hex ?? '#9ca3af' }}"></div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 truncate">{{ $candidate->name }}</p>
                            <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                @if($candidate->party)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold text-white" style="background-color: {{ $candidate->party->colour_hex }}">{{ $candidate->party->acronym }}</span>
                                @endif
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $postColors[$candidate->post_type] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $postTypeLabels[$candidate->post_type] ?? ucfirst($candidate->post_type) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <button wire:click="openEdit({{ $candidate->id }})"
                        class="text-green-700 text-sm font-medium shrink-0">Edit</button>
                </div>
            </div>
            @empty
            <div class="px-5 py-16 text-center">
                <p class="text-gray-400 text-sm">No candidates found</p>
            </div>
            @endforelse
        </div>

        <div class="px-5 py-3 border-t border-gray-100 text-xs">{{ $candidates->links() }}</div>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="cand-modal-title"
        x-data
        x-init="$nextTick(() => $el.querySelector('input[type=text]')?.focus())"
        @keydown.escape.window="$wire.set('showModal', false)"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
    >
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-5">
                <h3 id="cand-modal-title" class="font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $editingId ? 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' : 'M12 6v6m0 0v6m0-6h6m-6 0H6' }}"/></svg>
                    </span>
                    {{ $editingId ? 'Edit Candidate' : 'Add Candidate' }}
                </h3>
                <button wire:click="$set('showModal', false)" aria-label="Close dialog"
                    class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="save" class="space-y-4" novalidate>
                <div>
                    <label for="cand-name" class="block text-sm text-gray-600 mb-1 font-medium">
                        Candidate Name <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input wire:model="name" id="cand-name" type="text"
                        aria-required="true" placeholder="e.g. Ibrahim Sani Dutse"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    @error('name') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="cand-party" class="block text-sm text-gray-600 mb-1 font-medium">
                        Party <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select wire:model="party_id" id="cand-party"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                        aria-required="true">
                        <option value="">Select Party</option>
                        @foreach($allParties as $p)
                            <option value="{{ $p->id }}">{{ $p->acronym }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('party_id') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="cand-post-type" class="block text-sm text-gray-600 mb-1 font-medium">
                        Post Type <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select wire:model.live="post_type" id="cand-post-type"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                        aria-required="true">
                        <option value="">Select Post</option>
                        @foreach($postTypeLabels as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('post_type') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>

                {{-- Conditional LGA/Ward fields --}}
                @if(in_array($post_type, ['house_of_reps', 'state_assembly']))
                <div>
                    <label for="cand-lga" class="block text-sm text-gray-600 mb-1 font-medium">
                        LGA <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select wire:model.live="lga_id" id="cand-lga"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                        aria-required="true">
                        <option value="">Select LGA</option>
                        @foreach($allLgas as $lga)
                            <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                        @endforeach
                    </select>
                    @error('lga_id') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>

                @if($lga_id && $formWards->isNotEmpty())
                <div>
                    <label for="cand-ward" class="block text-sm text-gray-600 mb-1 font-medium">Ward <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                    <select wire:model="ward_id" id="cand-ward"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                        <option value="">All wards in LGA</option>
                        @foreach($formWards as $ward)
                            <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                        @endforeach
                    </select>
                    @error('ward_id') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                @endif
                @elseif(in_array($post_type, ['governorship', 'senate']))
                <div class="bg-purple-50 border border-purple-100 rounded-lg p-3">
                    <p class="text-xs text-purple-600 font-medium flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Statewide post — no LGA/Ward scope needed
                    </p>
                </div>
                @endif

                <p class="text-xs text-gray-400">Fields marked <span class="text-red-500">*</span> are required.</p>
                <div class="flex gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="flex-1 bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg text-sm transition disabled:opacity-60 shadow-sm">
                        <span wire:loading.remove>{{ $editingId ? 'Update Candidate' : 'Add Candidate' }}</span>
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
