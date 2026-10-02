<div class="max-w-5xl mx-auto"
     x-data="{
         sections: { agent: false, leadership: false, cluster: false },
         checkAgent(...fields) {
             this.sections.agent = fields.some(f => f && f.toString().trim() !== '');
         },
         checkLeadership(roles) {
             this.sections.leadership = Array.isArray(roles) && roles.length > 0;
         },
         checkCluster(members) {
             this.sections.cluster = Array.isArray(members) && members.length > 0 && members.some(m => m.name && m.name.trim() !== '');
         },
         get progress() { return Object.values(this.sections).filter(Boolean).length; },
         get totalSections() { return Object.keys(this.sections).length; }
     }"
     x-init="
         $watch('$wire.first_name',           v => checkAgent(v, $wire.surname, $wire.nin));
         $watch('$wire.surname',              v => checkAgent($wire.first_name, v, $wire.nin));
         $watch('$wire.nin',                  v => checkAgent($wire.first_name, $wire.surname, v));
         $watch('$wire.leadership_roles',     v => checkLeadership(v));
         $watch('$wire.clusterMembers',       v => checkCluster(v));
     "
>

    {{-- Page header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.pu-agents') }}" wire:navigate
            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition" aria-label="Back">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-xl font-bold text-gray-900">
                {{ $agentId ? 'Edit Polling Unit Agent' : 'Register Polling Unit Agent' }}
            </h2>
            <p class="text-sm text-gray-500 mt-0.5">APC Jigawa State Chapter — Polling Unit Data Collection Form</p>
        </div>
    </div>

    {{-- Progress indicator --}}
    <div class="mb-6 bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500">Form Progress</span>
            <span class="text-xs font-semibold transition-colors"
                  :class="progress === totalSections ? 'text-green-600' : 'text-gray-400'"
                  x-text="progress + '/' + totalSections + ' sections'"></span>
        </div>
        <div class="flex gap-1.5">
            <template x-for="(label, key) in { agent: 'Agent Info', leadership: 'Leadership', cluster: 'Cluster' }">
                <div class="flex-1">
                    <div class="h-1.5 rounded-full transition-all duration-300"
                         :class="sections[key] ? 'bg-green-500' : 'bg-gray-200'"></div>
                    <span class="text-[10px] mt-1 block text-center transition-colors"
                          :class="sections[key] ? 'text-green-600 font-medium' : 'text-gray-400'"
                          x-text="label"></span>
                </div>
            </template>
        </div>
    </div>

    <form wire:submit="save" class="space-y-5" novalidate>

        {{-- ── Section 1: Agent Information ─────────────────────────────────── --}}
        <fieldset class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6 shadow-sm transition-shadow hover:shadow-md">
            <legend class="flex items-center gap-2 font-semibold text-gray-800 px-1 mb-4 float-left w-full">
                <span class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold transition-colors"
                      :class="sections.agent ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">1</span>
                Agent Information
            </legend>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 clear-both">

                {{-- First Name --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">First Name <span class="text-red-500" aria-hidden="true">*</span></label>
                    <input wire:model="first_name" type="text" autocomplete="off" placeholder="e.g. Aminu"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition
                               @error('first_name') border-red-400 @enderror">
                    @error('first_name')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Surname --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Surname <span class="text-red-500" aria-hidden="true">*</span></label>
                    <input wire:model="surname" type="text" autocomplete="off" placeholder="e.g. Musa"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition
                               @error('surname') border-red-400 @enderror">
                    @error('surname')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Polling Unit name --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Polling Unit (PU) <span class="text-red-500" aria-hidden="true">*</span></label>
                    <input wire:model="polling_unit" type="text" autocomplete="off" placeholder="e.g. SABON GARI PRIMARY SCHOOL"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition
                               @error('polling_unit') border-red-400 @enderror">
                    @error('polling_unit')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Polling Unit number --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Polling Unit Number <span class="text-red-500" aria-hidden="true">*</span></label>
                    <input wire:model="polling_unit_number" type="text" autocomplete="off" placeholder="e.g. 001"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition
                               @error('polling_unit_number') border-red-400 @enderror">
                    @error('polling_unit_number')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Local Government --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Local Government <span class="text-red-500" aria-hidden="true">*</span></label>
                    @if($isSupervisorLocked)
                        <input type="text" value="{{ $lgas->first()?->name }}" readonly
                            class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2.5 text-sm text-gray-500 cursor-not-allowed">
                        <input type="hidden" wire:model="lga_id">
                    @else
                        <select wire:model.live="lga_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition
                                   @error('lga_id') border-red-400 @enderror">
                            <option value="">— select LGA —</option>
                            @foreach($lgas as $lga)
                                <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('lga_id')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Ward --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Ward <span class="text-red-500" aria-hidden="true">*</span></label>
                    <select wire:model.live="ward_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition
                               @error('ward_id') border-red-400 @enderror">
                        <option value="">— select ward —</option>
                        @foreach($wards as $ward)
                            <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                        @endforeach
                    </select>
                    @if($lga_id && $wards->isEmpty())
                        <p class="text-amber-600 text-xs mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                            </svg>
                            No wards found for this LGA.
                        </p>
                    @endif
                    @error('ward_id')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- State --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">State <span class="text-red-500" aria-hidden="true">*</span></label>
                    <select wire:model="state"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition
                               @error('state') border-red-400 @enderror">
                        <option value="">— select state —</option>
                        @foreach($nigerianStates as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                    @error('state')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- NIN --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">NIN <span class="text-red-500" aria-hidden="true">*</span></label>
                    <input wire:model="nin" type="text" inputmode="numeric" maxlength="11" autocomplete="off"
                        placeholder="11-digit NIN"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition font-mono tracking-wider
                               @error('nin') border-red-400 @enderror">
                    @error('nin')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Voters Card Number --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Voters Card Number <span class="text-red-500" aria-hidden="true">*</span></label>
                    <input wire:model="voters_card_number" type="text" autocomplete="off"
                        placeholder="e.g. 95C5E1BA0A09001"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition font-mono
                               @error('voters_card_number') border-red-400 @enderror">
                    @error('voters_card_number')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- BVN (sensitive) --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">
                        BVN
                        <span class="ml-1 inline-flex items-center gap-1 text-[10px] font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Sensitive
                        </span>
                    </label>
                    <input wire:model="bvn" type="text" inputmode="numeric" maxlength="11" autocomplete="off"
                        placeholder="11-digit BVN"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition font-mono
                               @error('bvn') border-red-400 @enderror">
                    @error('bvn')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Account Number (sensitive) --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">
                        Account Number
                        <span class="ml-1 inline-flex items-center gap-1 text-[10px] font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Sensitive
                        </span>
                    </label>
                    <input wire:model="account_number" type="text" inputmode="numeric" maxlength="10" autocomplete="off"
                        placeholder="10-digit account number"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition font-mono
                               @error('account_number') border-red-400 @enderror">
                    @error('account_number')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Bank Name --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Bank Name</label>
                    <input wire:model="bank_name" type="text" autocomplete="off" placeholder="e.g. First Bank"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                </div>

            </div>
        </fieldset>

        {{-- ── Section 2: For Office Use — Leadership Option ─────────────────── --}}
        <fieldset class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6 shadow-sm transition-shadow hover:shadow-md">
            <legend class="flex items-center gap-2 font-semibold text-gray-800 px-1 mb-1 float-left w-full">
                <span class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold transition-colors"
                      :class="sections.leadership ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">2</span>
                For Office Use — Leadership Option
            </legend>
            <p class="text-xs text-gray-400 mb-4 clear-both">Select all applicable leadership roles for this agent.</p>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach($leadershipRoles as $key => $label)
                    <label class="flex items-center gap-2.5 p-3 rounded-lg border cursor-pointer transition-all
                        {{ in_array($key, $leadership_roles)
                            ? 'border-green-500 bg-green-50 shadow-sm'
                            : 'border-gray-200 hover:border-green-300 hover:bg-green-50/50' }}">
                        <input type="checkbox"
                            wire:click="toggleRole('{{ $key }}')"
                            @checked(in_array($key, $leadership_roles))
                            class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-2 focus:ring-green-500 shrink-0">
                        <span class="text-sm font-medium leading-tight
                            {{ in_array($key, $leadership_roles) ? 'text-green-800' : 'text-gray-700' }}">
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
            @error('leadership_roles')
                <p class="mt-3 text-xs text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </fieldset>

        {{-- ── Section 3: Polling Unit Cluster Members ────────────────────────── --}}
        <fieldset class="bg-white rounded-xl border border-gray-200 shadow-sm transition-shadow hover:shadow-md overflow-hidden">
            <legend class="float-left w-full border-b border-gray-100">
                <div class="flex items-center justify-between px-5 sm:px-6 py-4">
                    <div class="flex items-center gap-2">
                        <span class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold transition-colors"
                              :class="sections.cluster ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">3</span>
                        <span class="font-semibold text-gray-800">Polling Unit Cluster Members</span>
                        <span class="ml-1 text-xs text-gray-400 font-normal">
                            {{ count($clusterMembers) }}/{{ $maxCluster }}
                        </span>
                    </div>
                    <button type="button" wire:click="addClusterRow"
                        @disabled(count($clusterMembers) >= $maxCluster)
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Member
                    </button>
                </div>
            </legend>

            <div class="clear-both">
                @if(count($clusterMembers) === 0)
                    <div class="px-6 py-12 text-center">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500">No cluster members yet</p>
                        <p class="text-xs text-gray-400 mt-1">Click "Add Member" to begin. Up to {{ $maxCluster }} members allowed.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide w-10">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Name <span class="text-red-400 normal-case">*</span></th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Phone</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">NIN</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Voters Card No.</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Party Reg. No.</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($clusterMembers as $index => $member)
                                    <tr wire:key="cluster-row-{{ $index }}" class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-4 py-2.5 text-xs text-gray-400 font-medium">{{ $index + 1 }}</td>
                                        <td class="px-4 py-2.5 min-w-[160px]">
                                            <input wire:model="clusterMembers.{{ $index }}.name" type="text"
                                                placeholder="Full name"
                                                class="w-full border border-gray-300 rounded-lg px-2.5 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition
                                                       @error("clusterMembers.{$index}.name") border-red-400 @enderror">
                                            @error("clusterMembers.{$index}.name")
                                                <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-2.5 min-w-[130px]">
                                            <input wire:model="clusterMembers.{{ $index }}.phone_number" type="text"
                                                inputmode="tel" placeholder="08xx…"
                                                class="w-full border border-gray-300 rounded-lg px-2.5 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <input wire:model="clusterMembers.{{ $index }}.nin" type="text"
                                                inputmode="numeric" maxlength="11" placeholder="11 digits"
                                                class="w-28 border border-gray-300 rounded-lg px-2.5 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition font-mono
                                                       @error("clusterMembers.{$index}.nin") border-red-400 @enderror">
                                            @error("clusterMembers.{$index}.nin")
                                                <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-2.5 min-w-[140px]">
                                            <input wire:model="clusterMembers.{{ $index }}.voters_card_number" type="text"
                                                placeholder="Card number"
                                                class="w-full border border-gray-300 rounded-lg px-2.5 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition font-mono">
                                        </td>
                                        <td class="px-4 py-2.5 min-w-[140px]">
                                            <input wire:model="clusterMembers.{{ $index }}.arty_membership_reg_number" type="text"
                                                placeholder="Reg. number"
                                                class="w-full border border-gray-300 rounded-lg px-2.5 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <button type="button" wire:click="removeClusterRow({{ $index }})"
                                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" aria-label="Remove row">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </fieldset>

        {{-- Footer: required notice + action buttons --}}
        <div class="flex items-center justify-between pt-1">
            <p class="text-xs text-gray-400">Fields marked <span class="text-red-500">*</span> are required.</p>
            <span class="text-xs text-gray-400"
                  x-show="progress < totalSections"
                  x-text="(totalSections - progress) + ' section(s) remaining'"></span>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.pu-agents') }}" wire:navigate
                class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm">
                Cancel
            </a>
            <button type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-green-700 hover:bg-green-800 rounded-lg transition shadow-sm hover:shadow-md disabled:opacity-60">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $agentId ? 'Update Agent' : 'Register Agent' }}
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Saving…
                </span>
            </button>
        </div>

    </form>
</div>
