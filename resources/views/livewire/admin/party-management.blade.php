<div>
    {{-- Search & Action Bar --}}
    <div class="flex flex-wrap gap-2 sm:gap-3 mb-6 bg-white border border-gray-200 rounded-xl p-3 sm:p-4 shadow-sm">
        <div class="flex-1 min-w-48">
            <label for="party-search" class="sr-only">Search by name or acronym</label>
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" id="party-search" type="search"
                    placeholder="Search name or acronym..."
                    class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
            </div>
        </div>
        <button wire:click="openCreate"
            class="ml-auto bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            New Party
        </button>
    </div>

    {{-- Party Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm min-w-[600px]">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Party</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Acronym</th>
                        <th scope="col" class="text-center px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Colour</th>
                        <th scope="col" class="text-center px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Logo</th>
                        <th scope="col" class="text-center px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Candidates</th>
                        <th scope="col" class="text-left px-5 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($parties as $party)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                {{-- Party colour circle or logo --}}
                                @if($party->logo_url)
                                    <img src="{{ $party->logo_url }}" alt="{{ $party->acronym }}" class="w-9 h-9 rounded-full object-cover border-2 shadow-sm" style="border-color: {{ $party->colour_hex }}">
                                @else
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white shadow-sm" style="background-color: {{ $party->colour_hex }}">
                                        {{ strtoupper(substr($party->acronym, 0, 2)) }}
                                    </div>
                                @endif
                                <span class="font-medium text-gray-800">{{ $party->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold text-white shadow-sm" style="background-color: {{ $party->colour_hex }}">
                                {{ $party->acronym }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <span class="w-5 h-5 rounded-full border border-gray-200 shadow-inner" style="background-color: {{ $party->colour_hex }}"></span>
                                <span class="text-xs text-gray-500 font-mono">{{ $party->colour_hex }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if($party->logo_url)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-700">Has Logo</span>
                            @else
                                <span class="text-gray-300 text-xs">None</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ $party->candidates_count }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-1">
                                <button wire:click="openEdit({{ $party->id }})"
                                    class="text-green-700 hover:text-green-900 text-sm font-medium hover:bg-green-50 px-2 py-1 rounded transition">
                                    Edit
                                </button>
                                <button wire:click="confirmDelete({{ $party->id }})"
                                    wire:confirm="Delete {{ $party->acronym }}? This cannot be undone."
                                    class="text-red-600 hover:text-red-800 text-sm font-medium hover:bg-red-50 px-2 py-1 rounded transition">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-16 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                        <p class="text-gray-400 text-sm">No parties found</p>
                        <p class="text-xs text-gray-300 mt-1">Try adjusting your search or create a new party</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile: card view --}}
        <div class="sm:hidden divide-y divide-gray-100">
            @forelse($parties as $party)
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        @if($party->logo_url)
                            <img src="{{ $party->logo_url }}" alt="{{ $party->acronym }}" class="w-10 h-10 rounded-full object-cover border-2 shadow-sm" style="border-color: {{ $party->colour_hex }}">
                        @else
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold text-white shadow-sm shrink-0" style="background-color: {{ $party->colour_hex }}">
                                {{ strtoupper(substr($party->acronym, 0, 2)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 truncate">{{ $party->name }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold text-white mt-0.5" style="background-color: {{ $party->colour_hex }}">{{ $party->acronym }}</span>
                        </div>
                    </div>
                    <button wire:click="openEdit({{ $party->id }})"
                        class="text-green-700 text-sm font-medium shrink-0">Edit</button>
                </div>
                <div class="flex items-center gap-2 mt-2 ml-13">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700">{{ $party->candidates_count }} candidates</span>
                </div>
            </div>
            @empty
            <div class="px-5 py-16 text-center">
                <p class="text-gray-400 text-sm">No parties found</p>
            </div>
            @endforelse
        </div>

        <div class="px-5 py-3 border-t border-gray-100 text-xs">{{ $parties->links() }}</div>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="party-modal-title"
        x-data="{
            pickerColour: @js($colour_hex),
            hexInput: @js($colour_hex),
            syncFromPicker() { this.hexInput = this.pickerColour; $wire.set('colour_hex', this.pickerColour); },
            syncFromInput() {
                if (/^#[0-9A-Fa-f]{6}$/.test(this.hexInput)) {
                    this.pickerColour = this.hexInput;
                    $wire.set('colour_hex', this.hexInput);
                }
            }
        }"
        x-init="$nextTick(() => $el.querySelector('input[type=text]')?.focus())"
        @keydown.escape.window="$wire.set('showModal', false)"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
    >
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-5">
                <h3 id="party-modal-title" class="font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $editingId ? 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' : 'M12 6v6m0 0v6m0-6h6m-6 0H6' }}"/></svg>
                    </span>
                    {{ $editingId ? 'Edit Party' : 'Create Party' }}
                </h3>
                <button wire:click="$set('showModal', false)" aria-label="Close dialog"
                    class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="save" class="space-y-4" novalidate>
                <div>
                    <label for="party-name" class="block text-sm text-gray-600 mb-1 font-medium">
                        Name <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input wire:model="name" id="party-name" type="text"
                        aria-required="true" placeholder="e.g. All Progressives Congress"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    @error('name') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="party-acronym" class="block text-sm text-gray-600 mb-1 font-medium">
                        Acronym <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input wire:model="acronym" id="party-acronym" type="text" maxlength="10"
                        aria-required="true" placeholder="e.g. APC"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm uppercase focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    @error('acronym') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1 font-medium">
                        Brand Colour <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="color" x-model="pickerColour" @input="syncFromPicker()"
                            class="w-12 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5" />
                        <input type="text" x-model="hexInput" @input="syncFromInput()" maxlength="7"
                            placeholder="#16a34a"
                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-mono focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    </div>
                    @error('colour_hex') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="party-logo" class="block text-sm text-gray-600 mb-1 font-medium">Logo</label>
                    <input wire:model="logo" id="party-logo" type="file" accept="image/*"
                        class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100 transition" />
                    @error('logo') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                    <p class="text-xs text-gray-400 mt-1">Max 2MB. PNG, JPG, or SVG recommended.</p>

                    {{-- Logo preview --}}
                    <div class="mt-2 flex items-center gap-3">
                        @if($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="w-12 h-12 rounded-full object-cover border-2" style="border-color: {{ $colour_hex }}">
                            <span class="text-xs text-green-600">New logo preview</span>
                        @elseif($existingLogoUrl)
                            <img src="{{ $existingLogoUrl }}" alt="Current" class="w-12 h-12 rounded-full object-cover border-2" style="border-color: {{ $colour_hex }}">
                            <span class="text-xs text-gray-400">Current logo</span>
                        @endif
                    </div>
                </div>

                {{-- Live preview pill --}}
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                    <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wide mb-2">Preview</p>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white" x-bind:style="'background-color: ' + pickerColour" x-text="$wire.acronym ? $wire.acronym.substring(0,2).toUpperCase() : 'XX'"></div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold text-white" x-bind:style="'background-color: ' + pickerColour" x-text="$wire.acronym || 'ACRONYM'"></span>
                        <span class="text-sm text-gray-600" x-text="$wire.name || 'Party Name'"></span>
                    </div>
                </div>

                <p class="text-xs text-gray-400">Fields marked <span class="text-red-500">*</span> are required.</p>
                <div class="flex gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="flex-1 bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg text-sm transition disabled:opacity-60 shadow-sm">
                        <span wire:loading.remove>{{ $editingId ? 'Update Party' : 'Create Party' }}</span>
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
