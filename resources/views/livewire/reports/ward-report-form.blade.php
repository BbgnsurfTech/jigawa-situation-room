<div class="max-w-3xl mx-auto"
     x-data="{
         sections: { a: false, b: false, c: false, d: false, e: false },
         checkSection(name, ...fields) {
             this.sections[name] = fields.some(f => f && f.toString().trim() !== '');
         },
         get progress() {
             return Object.values(this.sections).filter(Boolean).length;
         },
         get totalSections() {
             return Object.keys(this.sections).length;
         }
     }"
     x-init="
         $watch('$wire.lga_id',              v => checkSection('a', v, $wire.ward_id));
         $watch('$wire.ward_id',             v => checkSection('a', $wire.lga_id, v));
         $watch('$wire.counted_voter_papers', v => checkSection('b', v));
         $watch('$wire.number_cast_votes',   v => checkSection('c', v, $wire.valid_ballot_papers, $wire.invalid_ballot_papers));
         $watch('$wire.valid_ballot_papers', v => checkSection('c', $wire.number_cast_votes, v, $wire.invalid_ballot_papers));
         $watch('$wire.invalid_ballot_papers', v => checkSection('c', $wire.number_cast_votes, $wire.valid_ballot_papers, v));
         $watch('$wire.security_present',    v => checkSection('d', v));
         $watch('$wire.ec8a_serial_number',  v => checkSection('e', v));
     "
>
    @if($submitted)
    <div class="bg-white border border-green-200 rounded-2xl p-8 text-center shadow-sm">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h2 class="text-xl font-bold text-green-800 mb-2">Report Submitted</h2>
        <p class="text-green-700 text-sm mb-1">Your ward report has been received successfully.</p>
        <p class="text-green-600 text-xs font-mono bg-green-50 inline-block px-3 py-1 rounded-full mt-1">Reference: #{{ $reportId }}</p>
        <div class="mt-6">
            <button wire:click="$set('submitted', false)" class="inline-flex items-center gap-2 text-sm bg-green-700 hover:bg-green-800 text-white px-5 py-2.5 rounded-lg transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Submit Another Report
            </button>
        </div>
    </div>
    @else

    {{-- Progress indicator --}}
    <div class="mb-6 bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500">Form Progress</span>
            <span class="text-xs font-semibold" :class="progress === totalSections ? 'text-green-600' : 'text-gray-500'" x-text="progress + '/' + totalSections + ' sections'"></span>
        </div>
        <div class="flex gap-1.5">
            <template x-for="(label, key) in {a: 'Station', b: 'Papers', c: 'Ballots', d: 'Security', e: 'Party'}">
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

    <form wire:submit="submit" class="space-y-5" novalidate>

        @if($errorMessage)
        <div role="alert" class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm flex items-start gap-3">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $errorMessage }}
        </div>
        @endif

        {{-- Part A: Station Info --}}
        <fieldset class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6 shadow-sm transition-shadow hover:shadow-md">
            <legend class="flex items-center gap-2 font-semibold text-gray-800 px-1 mb-4 float-left w-full">
                <span class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold transition-colors"
                      :class="sections.a ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">A</span>
                Polling Station Info
            </legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 clear-both">
                @if(auth()->user()->isFieldAgent())
                <div>
                    <label for="field-lga" class="block text-sm text-gray-600 mb-1">LGA</label>
                    <input id="field-lga" type="text" readonly
                        value="{{ $lgas->firstWhere('id', $lga_id)?->name ?? '—' }}"
                        class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2.5 text-sm text-gray-500 cursor-not-allowed" />
                </div>
                <div>
                    <label for="field-ward" class="block text-sm text-gray-600 mb-1">Ward</label>
                    <input id="field-ward" type="text" readonly
                        value="{{ $wards->firstWhere('id', $ward_id)?->name ?? '—' }}"
                        class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2.5 text-sm text-gray-500 cursor-not-allowed" />
                </div>
                {{-- PU Agent: show assigned station (read-only) --}}
                @if($is_pu_agent)
                <div class="sm:col-span-2">
                    <label for="field-pu" class="block text-sm text-gray-600 mb-1">Polling Unit</label>
                    @php $station = \App\Models\PollingStation::find($polling_station_id); @endphp
                    <input id="field-pu" type="text" readonly
                        value="{{ $station?->name ?? '—' }} ({{ $station?->code ?? '' }})"
                        class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2.5 text-sm text-gray-500 cursor-not-allowed" />
                    @if($registered_voter_count)
                    <p class="text-xs text-green-600 mt-1">Registered voters: {{ number_format($registered_voter_count) }}</p>
                    @endif
                </div>
                @elseif($pollingStations->isNotEmpty())
                {{-- Ward Agent: optional PU selection --}}
                <div class="sm:col-span-2">
                    <label for="field-pu" class="block text-sm text-gray-600 mb-1">Polling Unit <span class="text-gray-400 text-xs">(optional)</span></label>
                    <select wire:model="polling_station_id" id="field-pu"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                        <option value="">-- Ward-level report --</option>
                        @foreach($pollingStations as $ps) <option value="{{ $ps->id }}">{{ $ps->name }} ({{ $ps->code }})</option> @endforeach
                    </select>
                </div>
                @endif
                @else
                <div>
                    <label for="field-lga" class="block text-sm text-gray-600 mb-1">LGA <span class="text-red-500" aria-hidden="true">*</span></label>
                    <select wire:model.live="lga_id" id="field-lga"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                        aria-required="true">
                        <option value="">Select LGA</option>
                        @foreach($lgas as $lga) <option value="{{ $lga->id }}">{{ $lga->name }}</option> @endforeach
                    </select>
                    @error('lga_id') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="field-ward" class="block text-sm text-gray-600 mb-1">Ward <span class="text-red-500" aria-hidden="true">*</span></label>
                    <select wire:model="ward_id" id="field-ward"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                        aria-required="true">
                        <option value="">Select Ward</option>
                        @foreach($wards as $ward) <option value="{{ $ward->id }}">{{ $ward->name }}</option> @endforeach
                    </select>
                    @if($lga_id && $wards->isEmpty())
                        <p class="text-amber-600 text-xs mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                            No wards found for this LGA.
                        </p>
                    @endif
                    @error('ward_id') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                @endif

                <div>
                    <label for="field-inec-agent" class="block text-sm text-gray-600 mb-1">INEC Agent Name</label>
                    <input wire:model="inec_agent_name" id="field-inec-agent" type="text" placeholder="Full name"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                </div>
                <div>
                    <label for="field-party-agent" class="block text-sm text-gray-600 mb-1">Party Agent ID</label>
                    <input wire:model="party_agent_id" id="field-party-agent" type="text"
                        placeholder="e.g. PA-2024-001"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                </div>
            </div>
        </fieldset>

        {{-- Part B: Voter Papers --}}
        <fieldset class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6 shadow-sm transition-shadow hover:shadow-md">
            <legend class="flex items-center gap-2 font-semibold text-gray-800 px-1 mb-4 float-left w-full">
                <span class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold transition-colors"
                      :class="sections.b ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">B</span>
                Voter Papers
            </legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 clear-both">
                <div>
                    <label for="field-counted" class="block text-sm text-gray-600 mb-1">
                        Counted Voter Papers <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input wire:model="counted_voter_papers" id="field-counted" type="number" min="0"
                        inputmode="numeric" aria-required="true" placeholder="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    @error('counted_voter_papers') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="field-close-time" class="block text-sm text-gray-600 mb-1">Voting Closed Time</label>
                    <input wire:model="voting_closed_time" id="field-close-time" type="time"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    @error('voting_closed_time') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        {{-- Part C: Ballot Count --}}
        <fieldset class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6 shadow-sm transition-shadow hover:shadow-md">
            <legend class="flex items-center gap-2 font-semibold text-gray-800 px-1 mb-4 float-left w-full">
                <span class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold transition-colors"
                      :class="sections.c ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">C</span>
                Ballot Count
            </legend>

            @if($ballotWarning)
            <div role="alert" class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-3 text-sm flex items-start gap-2 clear-both">
                <svg class="w-5 h-5 shrink-0 mt-0.5 text-yellow-600" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>{{ $ballotWarning }}</span>
            </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 clear-both">
                <div>
                    <label for="field-cast" class="block text-sm text-gray-600 mb-1">
                        Number Cast Votes <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input wire:model.live="number_cast_votes" id="field-cast" type="number" min="0"
                        inputmode="numeric" aria-required="true" placeholder="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    @error('number_cast_votes') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="field-left" class="block text-sm text-gray-600 mb-1">
                        Voting Papers Left <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input wire:model="voting_papers_left" id="field-left" type="number" min="0"
                        inputmode="numeric" aria-required="true" placeholder="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    @error('voting_papers_left') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="field-valid" class="block text-sm text-gray-600 mb-1">
                        Valid Ballot Papers <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input wire:model.live="valid_ballot_papers" id="field-valid" type="number" min="0"
                        inputmode="numeric" aria-required="true" placeholder="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    @error('valid_ballot_papers') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="field-invalid" class="block text-sm text-gray-600 mb-1">
                        Invalid Ballot Papers <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input wire:model.live="invalid_ballot_papers" id="field-invalid" type="number" min="0"
                        inputmode="numeric" aria-required="true" placeholder="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    @error('invalid_ballot_papers') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Inline summary --}}
            @if($number_cast_votes || $valid_ballot_papers || $invalid_ballot_papers)
            <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-100 clear-both">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Running Tally</p>
                <div class="flex flex-wrap gap-4 text-sm">
                    @if($valid_ballot_papers && $invalid_ballot_papers)
                    <span class="text-gray-700">Valid + Invalid = <strong>{{ (int)$valid_ballot_papers + (int)$invalid_ballot_papers }}</strong></span>
                    @endif
                    @if($number_cast_votes)
                    <span class="text-gray-700">Cast Votes: <strong>{{ number_format((int)$number_cast_votes) }}</strong></span>
                    @endif
                    @if($this->turnoutRate !== null)
                    <span class="text-gray-700">Turnout: <strong class="{{ $this->turnoutRate > 100 ? 'text-red-600' : 'text-green-700' }}">{{ $this->turnoutRate }}%</strong></span>
                    @endif
                </div>
            </div>
            @endif
        </fieldset>

        {{-- Part D: Security --}}
        <fieldset class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6 shadow-sm transition-shadow hover:shadow-md">
            <legend class="flex items-center gap-2 font-semibold text-gray-800 px-1 mb-4 float-left w-full">
                <span class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold transition-colors"
                      :class="sections.d ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">D</span>
                Security
            </legend>
            <div class="clear-both">
                <div class="flex items-center gap-3 mb-4">
                    <input wire:model.live="security_present" type="checkbox" id="sec"
                        class="rounded border-gray-300 text-green-600 focus:ring-green-500 w-4 h-4" />
                    <label for="sec" class="text-sm text-gray-700 font-medium">Security Personnel Present</label>
                </div>
                @if($security_present)
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-3 p-3 bg-gray-50 rounded-lg" x-transition>
                    @foreach($availableSecurityTypes as $type)
                    <label class="flex items-center gap-2 text-sm cursor-pointer hover:text-green-700 transition">
                        <input type="checkbox" wire:model="security_types" value="{{ $type }}"
                            class="rounded border-gray-300 text-green-600 focus:ring-green-500" />
                        {{ $type }}
                    </label>
                    @endforeach
                </div>
                @error('security_types') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                @endif
                <div class="mt-4">
                    <label for="field-remarks" class="block text-sm text-gray-600 mb-1">Remarks (optional)</label>
                    <textarea wire:model="remarks" id="field-remarks" rows="3" placeholder="Any additional observations..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm resize-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition"></textarea>
                </div>
            </div>
        </fieldset>

        {{-- Part E: Party Results & EC8A --}}
        <fieldset class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6 shadow-sm transition-shadow hover:shadow-md">
            <legend class="flex items-center gap-2 font-semibold text-gray-800 px-1 mb-4 float-left w-full">
                <span class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold transition-colors"
                      :class="sections.e ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">E</span>
                Party Results & EC8A
            </legend>
            <div class="space-y-4 clear-both">
                <div>
                    <label for="field-ec8a" class="block text-sm text-gray-600 mb-1">EC8A Serial Number</label>
                    <input wire:model="ec8a_serial_number" id="field-ec8a" type="text" placeholder="e.g. EC8A-JIG-001"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-gray-600 font-medium">Party Vote Results</p>
                        @if($activeElections->count() > 0)
                        <button type="button" wire:click="addPartyRow"
                            class="text-xs bg-green-50 text-green-700 hover:bg-green-100 px-3 py-1 rounded-lg transition font-medium">
                            + Add Result
                        </button>
                        @endif
                    </div>

                    @if($activeElections->isEmpty())
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                        <p class="text-sm text-amber-700">No active elections configured. Contact administrator to activate elections.</p>
                    </div>
                    @elseif(count($party_results) > 0)
                    <div class="space-y-3">
                        @foreach($party_results as $index => $row)
                        <div wire:key="party-row-{{ $index }}" class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex gap-2 items-start mb-2">
                                {{-- Election Type Selection --}}
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Election Type</label>
                                    <select wire:model.live="party_results.{{ $index }}.election_type"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                                        <option value="">Select Election</option>
                                        @foreach($activeElections as $election)
                                            <option value="{{ $election->type }}">{{ $election->getTypeLabel() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" wire:click="removePartyRow({{ $index }})"
                                    class="text-red-400 hover:text-red-600 p-2 mt-5 transition" aria-label="Remove result row">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            @if(!empty($row['election_type']))
                            <div class="flex gap-2 items-start">
                                {{-- Party Selection --}}
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Party</label>
                                    <select wire:model.live="party_results.{{ $index }}.party_name"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                                        <option value="">Select Party</option>
                                        @foreach($parties as $party)
                                            <option value="{{ $party->acronym }}">{{ $party->acronym }} — {{ $party->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                {{-- Votes Input --}}
                                <div class="w-28">
                                    <label class="block text-xs text-gray-500 mb-1">Votes</label>
                                    <input wire:model.live="party_results.{{ $index }}.votes_scored" type="number" min="0"
                                        placeholder="0" inputmode="numeric"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                                </div>
                            </div>

                            {{-- Candidate hint --}}
                            @if(!empty($row['party_name']) && !empty($candidateLookup[$row['election_type']][$row['party_name']]))
                                @php $partyColour = $parties->firstWhere('acronym', $row['party_name'])?->colour_hex ?? '#6b7280'; @endphp
                                <div class="ml-1 mt-2 flex items-start gap-1.5">
                                    <span class="w-2 h-2 rounded-full mt-1 shrink-0" style="background-color: {{ $partyColour }}"></span>
                                    <span class="text-xs text-gray-500">
                                        Candidate: <strong>{{ $candidateLookup[$row['election_type']][$row['party_name']] }}</strong>
                                    </span>
                                </div>
                            @endif
                            @else
                            <p class="text-xs text-gray-400 italic">Select an election type to add party and votes.</p>
                            @endif
                        </div>
                        @endforeach

                        {{-- Running total vs valid ballots --}}
                        <div class="p-2 bg-gray-50 rounded-lg border border-gray-100 text-xs flex items-center justify-between">
                            <span class="text-gray-500">Party votes total: <strong class="text-gray-800">{{ $this->partyVotesTotal }}</strong></span>
                            @if($valid_ballot_papers)
                                @if($this->partyVotesTotal == (int)$valid_ballot_papers)
                                    <span class="text-green-600 font-medium">Matches valid ballots</span>
                                @else
                                    <span class="text-amber-600 font-medium">Valid ballots: {{ $valid_ballot_papers }}</span>
                                @endif
                            @endif
                        </div>
                    </div>
                    @else
                    <p class="text-xs text-gray-400 italic">No party results added. Click "Add Result" to enter vote counts by election and party.</p>
                    @endif
                </div>
            </div>
        </fieldset>

        <div class="flex items-center justify-between">
            <p class="text-xs text-gray-400">Fields marked <span class="text-red-500">*</span> are required.</p>
            <span class="text-xs text-gray-400" x-show="progress < totalSections" x-text="(totalSections - progress) + ' section(s) remaining'"></span>
        </div>

        <button type="submit" wire:loading.attr="disabled"
            class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-xl transition disabled:opacity-60 flex items-center justify-center gap-2 shadow-sm hover:shadow-md">
            <span wire:loading.remove class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Submit Report
            </span>
            <span wire:loading class="flex items-center gap-2" aria-live="polite">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Submitting...
            </span>
        </button>
    </form>
    @endif
</div>
