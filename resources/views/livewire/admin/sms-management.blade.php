<div>
    {{-- Sandbox mode notice --}}
    @if(config('jesr.africastalking.sandbox', true))
    <div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2.5 text-xs text-amber-800 flex items-center gap-2 shadow-sm" role="note">
        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <strong>Sandbox mode:</strong> Messages are logged to the database, not sent via carrier.
    </div>
    @endif

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-4 mb-6">
        @php $smsStats = [
            ['Total',     $stats['total'],     'text-gray-700',  'from-gray-50 to-slate-50',  'border-gray-200',  '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>'],
            ['Inbound',   $stats['inbound'],   'text-green-700', 'from-green-50 to-emerald-50', 'border-green-200', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'],
            ['Outbound',  $stats['outbound'],  'text-teal-700',  'from-teal-50 to-cyan-50',   'border-teal-200',  '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>'],
            ['Delivered',  $stats['delivered'], 'text-green-700', 'from-green-50 to-emerald-50', 'border-green-200', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'],
            ['Failed',     $stats['failed'],    'text-red-700',   'from-red-50 to-rose-50',    'border-red-200',   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>'],
        ]; @endphp
        @foreach($smsStats as [$label, $count, $textColor, $gradient, $borderColor, $iconPath])
        <div class="rounded-xl border {{ $borderColor }} bg-gradient-to-br {{ $gradient }} p-4 transition-all duration-200 hover:shadow-md" role="status" aria-label="{{ $label }}: {{ $count }}">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 {{ $textColor }} opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $iconPath !!}</svg>
            </div>
            <div class="text-2xl font-bold {{ $textColor }} tabular-nums">{{ number_format($count) }}</div>
            <div class="text-xs {{ $textColor }} opacity-60 mt-0.5">{{ $label }}</div>
        </div>
        @endforeach
    </div>

    {{-- Tabs --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm mb-6">
        <div class="flex border-b border-gray-200">
            <button wire:click="setActiveTab('history')"
                class="px-5 py-3 text-sm font-medium transition-colors {{ $activeTab === 'history' ? 'text-green-700 border-b-2 border-green-700 bg-green-50/50' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    History
                </span>
            </button>
            <button wire:click="setActiveTab('templates')"
                class="px-5 py-3 text-sm font-medium transition-colors {{ $activeTab === 'templates' ? 'text-green-700 border-b-2 border-green-700 bg-green-50/50' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                    Templates
                    <span class="bg-gray-100 text-gray-600 text-xs px-1.5 py-0.5 rounded">{{ $this->templates->count() }}</span>
                </span>
            </button>
            <button wire:click="setActiveTab('bulk')"
                class="px-5 py-3 text-sm font-medium transition-colors {{ $activeTab === 'bulk' ? 'text-green-700 border-b-2 border-green-700 bg-green-50/50' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Bulk SMS
                </span>
            </button>
        </div>

        {{-- Tab Content --}}
        <div class="p-4">
            {{-- History Tab --}}
            @if($activeTab === 'history')
            <div>
                {{-- Toolbar --}}
                <div class="flex flex-wrap gap-2 sm:gap-3 mb-5">
                    <div class="w-[calc(50%-4px)] sm:w-auto">
                        <label for="sms-direction" class="sr-only">Filter by direction</label>
                        <select wire:model.live="filterDirection" id="sms-direction"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                            <option value="">All Directions</option>
                            <option value="inbound">Inbound</option>
                            <option value="outbound">Outbound</option>
                        </select>
                    </div>
                    <div class="w-[calc(50%-4px)] sm:w-auto">
                        <label for="sms-status" class="sr-only">Filter by delivery status</label>
                        <select wire:model.live="filterStatus" id="sms-status"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="delivered">Delivered</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <button wire:click="openCompose"
                        class="ml-auto bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Compose SMS
                    </button>
                </div>

                {{-- Messages Table --}}
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="w-full text-sm min-w-[600px]">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Direction</th>
                                    <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">From</th>
                                    <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">To</th>
                                    <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Message</th>
                                    <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th scope="col" class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Sent At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($messages as $msg)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $msg->direction === 'inbound' ? 'bg-green-100 text-green-700' : 'bg-teal-100 text-teal-700' }}">
                                            {{ ucfirst($msg->direction) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $msg->from_phone }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $msg->to_phone }}</td>
                                    <td class="px-4 py-3 text-gray-700 max-w-xs text-xs">
                                        <span>{{ Str::limit($msg->body, 80) }}</span>
                                        @if(strlen($msg->body) > 80)
                                            <span class="text-gray-400 text-xs">...</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @php $deliveryColors = [
                                            'delivered' => 'bg-green-100 text-green-700',
                                            'failed'    => 'bg-red-100 text-red-700',
                                            'pending'   => 'bg-yellow-100 text-yellow-700',
                                        ]; @endphp
                                        <span class="px-1.5 py-0.5 rounded text-xs font-medium {{ $deliveryColors[$msg->delivery_status] ?? 'bg-gray-100 text-gray-600' }}">
                                            {{ ucfirst($msg->delivery_status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">{{ $msg->created_at->format('d M y H:i') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                    <p class="text-gray-400 text-sm">No SMS messages yet</p>
                                    <p class="text-xs text-gray-300 mt-1">Messages will appear as they are sent or received</p>
                                </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile: card view --}}
                    <div class="sm:hidden divide-y divide-gray-100">
                        @forelse($messages as $msg)
                        <div class="p-4">
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $msg->direction === 'inbound' ? 'bg-green-100 text-green-700' : 'bg-teal-100 text-teal-700' }}">
                                        {{ ucfirst($msg->direction) }}
                                    </span>
                                    @php $deliveryColors = [
                                        'delivered' => 'bg-green-100 text-green-700',
                                        'failed'    => 'bg-red-100 text-red-700',
                                        'pending'   => 'bg-yellow-100 text-yellow-700',
                                    ]; @endphp
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium {{ $deliveryColors[$msg->delivery_status] ?? '' }}">
                                        {{ ucfirst($msg->delivery_status) }}
                                    </span>
                                </div>
                                <span class="text-[10px] text-gray-400">{{ $msg->created_at->format('d M H:i') }}</span>
                            </div>
                            <div class="text-xs text-gray-500 font-mono mb-1">
                                {{ $msg->from_phone }} → {{ $msg->to_phone }}
                            </div>
                            <p class="text-xs text-gray-700">{{ Str::limit($msg->body, 120) }}</p>
                        </div>
                        @empty
                        <div class="px-4 py-16 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            <p class="text-gray-400 text-sm">No SMS messages yet</p>
                        </div>
                        @endforelse
                    </div>

                    <div class="px-4 py-3 border-t border-gray-100 text-xs">{{ $messages->links() }}</div>
                </div>
            </div>
            @endif

            {{-- Templates Tab --}}
            @if($activeTab === 'templates')
            <div>
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-gray-600">Manage SMS templates for quick message composition.</p>
                    <button wire:click="openTemplateModal"
                        class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        New Template
                    </button>
                </div>

                {{-- Templates Grid --}}
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($this->templates as $template)
                    <div class="border border-gray-200 rounded-lg p-4 {{ $template->is_active ? 'bg-white' : 'bg-gray-50 opacity-75' }}">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h4 class="font-medium text-gray-800 text-sm">{{ $template->name }}</h4>
                                <span class="inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 mt-1">
                                    {{ $this->templateCategories[$template->category] ?? $template->category }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                @if($template->is_system)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-purple-100 text-purple-700">System</span>
                                @endif
                                @if(!$template->is_active)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-200 text-gray-600">Inactive</span>
                                @endif
                            </div>
                        </div>

                        <p class="text-xs text-gray-600 mb-3 line-clamp-3">{{ $template->message }}</p>

                        @if($template->placeholders)
                        <div class="flex flex-wrap gap-1 mb-3">
                            @foreach($template->placeholders as $ph)
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 font-mono">{{'{'}}{{ $ph }}{{'}'}}</span>
                            @endforeach
                        </div>
                        @endif

                        <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                            <span class="text-[10px] text-gray-400">
                                {{ $template->character_count }} chars · {{ $template->sms_parts }} SMS
                            </span>
                            <div class="flex items-center gap-1">
                                <button wire:click="toggleTemplateActive({{ $template->id }})" title="{{ $template->is_active ? 'Deactivate' : 'Activate' }}"
                                    class="p-1.5 rounded hover:bg-gray-100 transition {{ $template->is_active ? 'text-green-600' : 'text-gray-400' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($template->is_active)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        @endif
                                    </svg>
                                </button>
                                @unless($template->is_system)
                                <button wire:click="openTemplateModal({{ $template->id }})" title="Edit"
                                    class="p-1.5 rounded hover:bg-gray-100 text-gray-500 hover:text-blue-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="confirmDeleteTemplate({{ $template->id }})" title="Delete"
                                    class="p-1.5 rounded hover:bg-gray-100 text-gray-500 hover:text-red-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endunless
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full py-12 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                        <p class="text-gray-400 text-sm">No templates yet</p>
                        <button wire:click="openTemplateModal" class="mt-2 text-green-700 hover:text-green-800 text-sm font-medium">
                            Create your first template
                        </button>
                    </div>
                    @endforelse
                </div>
            </div>
            @endif

            {{-- Bulk SMS Tab --}}
            @if($activeTab === 'bulk')
            <div x-data="{ charCount: @entangle('bulkMessage').length || 0, showTemplates: false }"
                 x-init="$watch('$wire.bulkMessage', val => charCount = val?.length || 0)">

                @if($bulkSuccess)
                <div role="status" class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-4 mb-4 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Successfully sent {{ $bulkSentCount }} messages!
                </div>
                @endif

                @if($bulkError)
                <div role="alert" class="bg-amber-50 border border-amber-200 text-amber-700 rounded-lg p-4 mb-4 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    {{ $bulkError }}
                </div>
                @endif

                <div class="grid lg:grid-cols-2 gap-6">
                    {{-- Filters --}}
                    <div>
                        <h4 class="font-medium text-gray-800 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filter Recipients
                        </h4>

                        <div class="space-y-3">
                            <div>
                                <label for="bulk-role" class="block text-xs text-gray-600 mb-1">Role</label>
                                <select wire:model.live="bulkFilterRole" id="bulk-role"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">All Roles</option>
                                    <option value="ward_agent">Ward Agents</option>
                                    <option value="polling_unit_agent">Polling Unit Agents</option>
                                    <option value="supervisor">Supervisors</option>
                                    <option value="state_admin">State Admins</option>
                                </select>
                            </div>

                            <div>
                                <label for="bulk-lga" class="block text-xs text-gray-600 mb-1">LGA</label>
                                <select wire:model.live="bulkFilterLgaId" id="bulk-lga"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">All LGAs</option>
                                    @foreach($this->lgas as $lga)
                                        <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if($bulkFilterLgaId)
                            <div>
                                <label for="bulk-ward" class="block text-xs text-gray-600 mb-1">Ward</label>
                                <select wire:model.live="bulkFilterWardId" id="bulk-ward"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">All Wards</option>
                                    @foreach($this->wards as $ward)
                                        <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>

                        {{-- Recipients List --}}
                        <div class="mt-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-600">
                                    Recipients: <strong class="text-gray-800">{{ $this->bulkRecipients->count() }}</strong>
                                </span>
                                <div class="flex items-center gap-2">
                                    <button wire:click="selectAllRecipients" class="text-xs text-green-700 hover:text-green-800 font-medium">
                                        Select All
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <button wire:click="clearAllRecipients" class="text-xs text-gray-500 hover:text-gray-700">
                                        Clear
                                    </button>
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-lg max-h-64 overflow-y-auto">
                                @forelse($this->bulkRecipients as $user)
                                <label class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0">
                                    <input type="checkbox" wire:model="bulkSelectedUsers" value="{{ $user->id }}"
                                        class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm text-gray-800 truncate">{{ $user->full_name ?? $user->name }}</div>
                                        <div class="text-[10px] text-gray-500 flex items-center gap-2">
                                            <span class="font-mono">{{ $user->phone }}</span>
                                            <span class="px-1 py-0.5 rounded bg-gray-100 text-gray-600">{{ str_replace('_', ' ', $user->role) }}</span>
                                        </div>
                                    </div>
                                </label>
                                @empty
                                <div class="px-4 py-8 text-center text-gray-400 text-sm">
                                    No users match the current filters
                                </div>
                                @endforelse
                            </div>

                            @if(count($bulkSelectedUsers) > 0)
                            <p class="text-xs text-green-700 mt-2">
                                {{ count($bulkSelectedUsers) }} recipient(s) selected
                            </p>
                            @endif
                            @error('bulkSelectedUsers') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Message --}}
                    <div>
                        <h4 class="font-medium text-gray-800 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            Compose Message
                        </h4>

                        {{-- Template Selector --}}
                        <div class="mb-3">
                            <button type="button" @click="showTemplates = !showTemplates"
                                class="text-xs text-green-700 hover:text-green-800 font-medium flex items-center gap-1 mb-2">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                                <span x-text="showTemplates ? 'Hide Templates' : 'Use Template'"></span>
                            </button>

                            <div x-show="showTemplates" x-collapse class="mb-3">
                                <div class="grid grid-cols-2 gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    @foreach($this->activeTemplates as $template)
                                    <button type="button" wire:click="applyBulkTemplate({{ $template->id }})"
                                        class="text-left p-2 rounded-lg border text-xs transition
                                            {{ $bulkTemplateId === $template->id
                                                ? 'bg-green-100 border-green-300 text-green-800'
                                                : 'bg-white border-gray-200 hover:border-green-300 hover:bg-green-50 text-gray-700' }}">
                                        <div class="font-medium truncate">{{ $template->name }}</div>
                                        <div class="text-[10px] text-gray-400 mt-0.5 truncate">{{ Str::limit($template->message, 40) }}</div>
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <textarea wire:model="bulkMessage" rows="6" maxlength="320"
                            x-on:input="charCount = $event.target.value.length"
                            placeholder="Type your message or select a template..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm resize-none focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>

                        <div class="flex items-center justify-between mt-1 mb-3">
                            <p class="text-xs" :class="charCount > 160 ? 'text-amber-500 font-medium' : 'text-gray-400'">
                                <span x-text="charCount">0</span>/160
                            </p>
                            <p class="text-[10px] text-gray-400" x-show="charCount > 0">
                                <span x-text="charCount <= 160 ? '1 SMS' : Math.ceil(charCount / 153) + ' SMS parts'"></span>
                                <span x-show="charCount > 160" class="text-amber-500">(concatenated)</span>
                            </p>
                        </div>
                        @error('bulkMessage') <p class="text-red-500 text-xs mb-3">{{ $message }}</p> @enderror

                        {{-- Placeholder hints --}}
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                            <p class="text-xs text-blue-800 font-medium mb-1">Available Placeholders:</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach(['{name}', '{full_name}', '{role}', '{lga}', '{ward}', '{phone}'] as $ph)
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-mono">{{ $ph }}</span>
                                @endforeach
                            </div>
                            <p class="text-[10px] text-blue-600 mt-1">Placeholders are replaced with each recipient's data.</p>
                        </div>

                        <div class="flex gap-3">
                            <button wire:click="sendBulkSms" wire:loading.attr="disabled"
                                class="flex-1 bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg text-sm transition disabled:opacity-60 shadow-sm flex items-center justify-center gap-2">
                                <span wire:loading.remove wire:target="sendBulkSms" class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    Send to {{ count($bulkSelectedUsers) }} Recipients
                                </span>
                                <span wire:loading wire:target="sendBulkSms" class="flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Sending...
                                </span>
                            </button>
                            <button wire:click="resetBulkForm"
                                class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Compose Modal --}}
    @if($showCompose)
    <div role="dialog" aria-modal="true" aria-labelledby="compose-modal-title"
        x-data="{ charCount: @entangle('message').length || 0, showTemplates: false }"
        x-init="$nextTick(() => {
            charCount = $wire.message?.length || 0;
            $watch('$wire.message', val => charCount = val?.length || 0);
        })"
        @keydown.escape.window="$wire.closeCompose()"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-5">
                <h3 id="compose-modal-title" class="font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </span>
                    Compose SMS
                </h3>
                <button wire:click="closeCompose" aria-label="Close dialog"
                    class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            @if($sendSuccess)
            <div role="status" class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-4 mb-4 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                SMS sent successfully (sandbox — logged to database).
            </div>
            @endif

            @if($sendError)
            <div role="alert" class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-4 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $sendError }}
            </div>
            @endif

            <form wire:submit="send" class="space-y-4" novalidate>
                {{-- Recipient Selection --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm text-gray-600 font-medium">
                            Recipient <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <button type="button" wire:click="toggleManualEntry"
                            class="text-xs text-green-700 hover:text-green-800 font-medium flex items-center gap-1">
                            @if($manualEntry)
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                Search Users
                            @else
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                Enter Number
                            @endif
                        </button>
                    </div>

                    @if($manualEntry)
                        <input wire:model="toPhone" type="tel" inputmode="numeric"
                            placeholder="08012345678" aria-required="true"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                    @else
                        <div class="relative" @click.outside="$wire.showUserDropdown = false">
                            @if($selectedUserId)
                                @php $selectedUser = \App\Models\User::with(['lga', 'ward'])->find($selectedUserId); @endphp
                                <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-green-200 rounded-full flex items-center justify-center text-green-700 text-xs font-bold">
                                            {{ strtoupper(substr($selectedUser->name ?? 'U', 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-800">{{ $selectedUser->full_name ?? $selectedUser->name }}</div>
                                            <div class="text-xs text-gray-500 font-mono">{{ $selectedUser->phone }}</div>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="clearSelectedUser"
                                        class="text-gray-400 hover:text-red-500 p-1 hover:bg-white rounded transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @else
                                <div class="relative">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    <input wire:model.live.debounce.300ms="userSearch" type="text"
                                        placeholder="Search by name, phone, role, LGA..."
                                        @focus="$wire.showUserDropdown = $wire.userSearch.length >= 2"
                                        class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                                </div>

                                @if($showUserDropdown && $this->searchResults->count() > 0)
                                <div class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    @foreach($this->searchResults as $user)
                                    <button type="button" wire:click="selectUser({{ $user->id }})"
                                        class="w-full text-left px-3 py-2.5 hover:bg-gray-50 flex items-center gap-3 border-b border-gray-100 last:border-0 transition">
                                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-gray-600 text-xs font-bold shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-800 truncate">{{ $user->full_name ?? $user->name }}</div>
                                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                                <span class="font-mono">{{ $user->phone }}</span>
                                                <span class="text-gray-300">|</span>
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium
                                                    {{ $user->role === 'super_admin' ? 'bg-purple-100 text-purple-700' : '' }}
                                                    {{ $user->role === 'state_admin' ? 'bg-blue-100 text-blue-700' : '' }}
                                                    {{ $user->role === 'supervisor' ? 'bg-orange-100 text-orange-700' : '' }}
                                                    {{ $user->role === 'ward_agent' ? 'bg-green-100 text-green-700' : '' }}
                                                    {{ $user->role === 'polling_unit_agent' ? 'bg-teal-100 text-teal-700' : '' }}
                                                ">{{ str_replace('_', ' ', ucfirst($user->role)) }}</span>
                                            </div>
                                            @if($user->lga || $user->ward)
                                            <div class="text-[10px] text-gray-400 truncate">
                                                {{ $user->lga?->name }}{{ $user->ward ? ' → ' . $user->ward->name : '' }}
                                            </div>
                                            @endif
                                        </div>
                                    </button>
                                    @endforeach
                                </div>
                                @elseif($showUserDropdown && strlen($userSearch) >= 2)
                                <div class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-gray-500 text-sm">
                                    No users found matching "{{ $userSearch }}"
                                </div>
                                @endif
                            @endif
                        </div>
                    @endif
                    @error('toPhone') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>

                {{-- Message Templates --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="compose-message" class="block text-sm text-gray-600 font-medium">
                            Message <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <button type="button" @click="showTemplates = !showTemplates"
                            class="text-xs text-green-700 hover:text-green-800 font-medium flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                            <span x-text="showTemplates ? 'Hide Templates' : 'Use Template'"></span>
                        </button>
                    </div>

                    <div x-show="showTemplates" x-collapse class="mb-3">
                        <div class="grid grid-cols-2 gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            @foreach($this->activeTemplates as $template)
                            <button type="button" wire:click="applyTemplate({{ $template->id }})"
                                class="text-left p-2 rounded-lg border text-xs transition
                                    {{ $selectedTemplateId === $template->id
                                        ? 'bg-green-100 border-green-300 text-green-800'
                                        : 'bg-white border-gray-200 hover:border-green-300 hover:bg-green-50 text-gray-700' }}">
                                <div class="font-medium truncate">{{ $template->name }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 truncate">{{ Str::limit($template->message, 40) }}</div>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <textarea wire:model="message" id="compose-message" rows="4" maxlength="320"
                        x-on:input="charCount = $event.target.value.length"
                        aria-required="true" aria-describedby="char-counter"
                        placeholder="Type your message or select a template above..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm resize-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition"></textarea>
                    <div class="flex items-center justify-between mt-1">
                        <p id="char-counter" class="text-xs"
                            :class="charCount > 160 ? 'text-amber-500 font-medium' : 'text-gray-400'"
                            aria-live="polite">
                            <span x-text="charCount">0</span>/160
                        </p>
                        <p class="text-[10px] text-gray-400" x-show="charCount > 0">
                            <span x-text="charCount <= 160 ? '1 SMS' : Math.ceil(charCount / 153) + ' SMS parts'"></span>
                            <span x-show="charCount > 160" class="text-amber-500">(concatenated)</span>
                        </p>
                    </div>
                    @error('message') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3">
                    <button type="submit" wire:loading.attr="disabled"
                        class="flex-1 bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg text-sm transition disabled:opacity-60 shadow-sm flex items-center justify-center gap-2">
                        <span wire:loading.remove class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            Send SMS
                        </span>
                        <span wire:loading aria-live="polite" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Sending...
                        </span>
                    </button>
                    <button type="button" wire:click="closeCompose"
                        class="flex-1 border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm hover:bg-gray-50 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Template Modal --}}
    @if($showTemplateModal)
    <div role="dialog" aria-modal="true" aria-labelledby="template-modal-title"
        x-data="{ charCount: @entangle('templateMessage').length || 0 }"
        x-init="$watch('$wire.templateMessage', val => charCount = val?.length || 0)"
        @keydown.escape.window="$wire.closeTemplateModal()"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-5">
                <h3 id="template-modal-title" class="font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                    </span>
                    {{ $editingTemplateId ? 'Edit Template' : 'New Template' }}
                </h3>
                <button wire:click="closeTemplateModal" aria-label="Close dialog"
                    class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="saveTemplate" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="template-name" class="block text-sm text-gray-600 font-medium mb-1">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="templateName" type="text" id="template-name"
                            placeholder="e.g. Report Reminder"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent" />
                        @error('templateName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="template-slug" class="block text-sm text-gray-600 font-medium mb-1">
                            Slug <span class="text-gray-400 text-xs">(optional)</span>
                        </label>
                        <input wire:model="templateSlug" type="text" id="template-slug"
                            placeholder="auto-generated"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent" />
                        @error('templateSlug') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="template-category" class="block text-sm text-gray-600 font-medium mb-1">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="templateCategory" id="template-category"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        @foreach($this->templateCategories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('templateCategory') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="template-message" class="block text-sm text-gray-600 font-medium mb-1">
                        Message <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="templateMessage" id="template-message" rows="4" maxlength="320"
                        x-on:input="charCount = $event.target.value.length"
                        placeholder="JESR: Your message here with {placeholders}..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm resize-none focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
                    <div class="flex items-center justify-between mt-1">
                        <p class="text-xs" :class="charCount > 160 ? 'text-amber-500 font-medium' : 'text-gray-400'">
                            <span x-text="charCount">0</span>/160
                        </p>
                        <p class="text-[10px] text-gray-400" x-show="charCount > 0">
                            <span x-text="charCount <= 160 ? '1 SMS' : Math.ceil(charCount / 153) + ' SMS parts'"></span>
                        </p>
                    </div>
                    @error('templateMessage') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="template-placeholders" class="block text-sm text-gray-600 font-medium mb-1">
                        Placeholders <span class="text-gray-400 text-xs">(comma-separated)</span>
                    </label>
                    <input wire:model="templatePlaceholders" type="text" id="template-placeholders"
                        placeholder="e.g. name, ward, OTP"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent" />
                    <p class="text-[10px] text-gray-400 mt-1">Use {{'{'}}placeholder{{'}'}} in message</p>
                    @error('templatePlaceholders') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="template-description" class="block text-sm text-gray-600 font-medium mb-1">
                        Description <span class="text-gray-400 text-xs">(optional)</span>
                    </label>
                    <textarea wire:model="templateDescription" id="template-description" rows="2"
                        placeholder="When this template should be used..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm resize-none focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
                    @error('templateDescription') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input wire:model="templateIsActive" type="checkbox" id="template-active"
                        class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                    <label for="template-active" class="text-sm text-gray-700">Active</label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="flex-1 bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg text-sm transition disabled:opacity-60 shadow-sm">
                        {{ $editingTemplateId ? 'Update Template' : 'Create Template' }}
                    </button>
                    <button type="button" wire:click="closeTemplateModal"
                        class="flex-1 border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm hover:bg-gray-50 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteConfirm)
    <div role="dialog" aria-modal="true"
        @keydown.escape.window="$wire.cancelDelete()"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <div class="text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">Delete Template?</h3>
                <p class="text-sm text-gray-600 mb-6">This action cannot be undone.</p>
                <div class="flex gap-3">
                    <button wire:click="deleteTemplate"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                        Delete
                    </button>
                    <button wire:click="cancelDelete"
                        class="flex-1 border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm hover:bg-gray-50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
