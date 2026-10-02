<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'JESR' }} — Jigawa Election Situation Room</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
    <style>
        [x-cloak] { display: none !important; }
        /* Page transition */
        .page-enter { animation: fadeSlideIn 0.2s ease-out; }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(4px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        /* Loading bar */
        .loading-bar { background: linear-gradient(90deg, transparent, #16a34a 50%, transparent); animation: loadSlide 1s ease-in-out infinite; }
        @keyframes loadSlide { from { transform: translateX(-100%); } to { transform: translateX(100%); } }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">

{{-- Global loading bar — shows during Livewire navigation --}}
<div wire:loading.delay.longer class="fixed top-0 left-0 right-0 z-[300] h-0.5 overflow-hidden bg-green-100">
    <div class="loading-bar h-full w-1/2"></div>
</div>

{{-- Toast notification container — P1: color-coded variants with icons --}}
<div
    x-data="{
        toasts: [],
        add(msg, type = 'info') {
            const id = Date.now();
            this.toasts.push({ id, msg, type });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
        colors: {
            success: 'bg-green-600',
            error:   'bg-red-600',
            warning: 'bg-amber-600',
            info:    'bg-gray-800',
        },
        icons: {
            success: `<svg class='w-5 h-5 shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7'/></svg>`,
            error:   `<svg class='w-5 h-5 shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'/></svg>`,
            warning: `<svg class='w-5 h-5 shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'/></svg>`,
            info:    `<svg class='w-5 h-5 shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'/></svg>`,
        }
    }"
    @notify.window="add(
        $event.detail.message ?? $event.detail[0]?.message ?? '',
        $event.detail.type ?? $event.detail[0]?.type ?? 'success'
    )"
    class="fixed top-4 right-4 z-[200] flex flex-col gap-2 max-w-sm w-full pointer-events-none"
    aria-live="polite"
    aria-label="Notifications"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-8"
            :class="colors[toast.type] || colors.info"
            class="flex items-start gap-3 text-white px-4 py-3 rounded-xl shadow-lg text-sm pointer-events-auto"
        >
            <span x-html="icons[toast.type] || icons.info" aria-hidden="true"></span>
            <span class="flex-1" x-text="toast.msg"></span>
            <button @click="remove(toast.id)" aria-label="Dismiss notification" class="text-white/60 hover:text-white shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>

<div class="flex h-full">
    {{-- Mobile overlay backdrop --}}
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 lg:hidden"
        x-cloak
        aria-hidden="true"
    ></div>

    {{-- Sidebar --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-green-950 text-white flex flex-col flex-shrink-0 transition-transform duration-200 ease-in-out"
        aria-label="Main navigation"
    >
        <div class="px-6 py-5 border-b border-green-800/60 flex items-center justify-between">
            <a href="{{ auth()->user()?->isFieldAgent() ? route('agent.home') : route('dashboard') }}" class="group">
                <div class="text-xs font-bold text-green-300 uppercase tracking-wider mb-1">Jigawa State</div>
                <h1 class="text-lg font-bold text-white leading-tight group-hover:text-green-200 transition">Election Situation Room</h1>
                <div class="text-xs text-green-500 mt-1">JESR v1.0</div>
            </a>
            <button @click="sidebarOpen = false" class="lg:hidden text-green-400 hover:text-white p-1 rounded-lg hover:bg-green-800 transition" aria-label="Close navigation">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        @auth
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" aria-label="Site navigation">
            @php $isActive = fn(string $pattern) => request()->routeIs($pattern); @endphp

            @php
            $navItems = [
                ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'roles' => ['all']],
            ];
            @endphp

            @if(auth()->user()->isFieldAgent())
            {{-- Field agents: show My Reports home instead of state-wide dashboard --}}
            <a href="{{ route('agent.home') }}" wire:navigate
               @if($isActive('agent.home')) aria-current="page" @endif
               class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('agent.home') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 {{ $isActive('agent.home') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                My Reports
            </a>
            @else
            <a href="{{ route('dashboard') }}" wire:navigate
               @if($isActive('dashboard')) aria-current="page" @endif
               class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('dashboard') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 {{ $isActive('dashboard') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            @endif

            @if(!auth()->user()->isFieldAgent())
            <a href="{{ route('supervisor.inbox') }}" wire:navigate
               @if($isActive('supervisor.*')) aria-current="page" @endif
               class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('supervisor.*') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 {{ $isActive('supervisor.*') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                Report Inbox
            </a>

            <a href="{{ route('admin.pu-agents') }}" wire:navigate
               @if($isActive('admin.pu-agents*')) aria-current="page" @endif
               class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('admin.pu-agents*') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 {{ $isActive('admin.pu-agents*') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                </svg>
                PU Agent Register
            </a>
            @endif

            <a href="{{ route('reports.create') }}" wire:navigate
               @if($isActive('reports.*')) aria-current="page" @endif
               class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('reports.*') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                <svg class="w-5 h-5 {{ $isActive('reports.*') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Election Report
            </a>

            @if(auth()->user()->role === 'super_admin')
            <div class="pt-4 mt-4 border-t border-green-800/50">
                <div class="px-3 text-[10px] font-semibold text-green-500 uppercase tracking-wider mb-2">Administration</div>
                <a href="{{ route('admin.users') }}" wire:navigate
                   @if($isActive('admin.users')) aria-current="page" @endif
                   class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('admin.users') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ $isActive('admin.users') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    User Management
                </a>
                <a href="{{ route('admin.roles') }}" wire:navigate
                   @if($isActive('admin.roles')) aria-current="page" @endif
                   class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('admin.roles') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ $isActive('admin.roles') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Roles &amp; Permissions
                </a>
                <a href="{{ route('admin.audit') }}" wire:navigate
                   @if($isActive('admin.audit')) aria-current="page" @endif
                   class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('admin.audit') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ $isActive('admin.audit') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Audit Log
                </a>
                <a href="{{ route('admin.sms') }}" wire:navigate
                   @if($isActive('admin.sms')) aria-current="page" @endif
                   class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('admin.sms') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ $isActive('admin.sms') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    SMS Management
                </a>

                <div class="pt-4 mt-4 border-t border-green-800/50">
                    <div class="px-3 text-[10px] font-semibold text-green-500 uppercase tracking-wider mb-2">Configuration</div>
                    <a href="{{ route('admin.lgas') }}" wire:navigate
                       @if($isActive('admin.lgas')) aria-current="page" @endif
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('admin.lgas') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ $isActive('admin.lgas') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        LGA Management
                    </a>
                    <a href="{{ route('admin.senatorial-districts') }}" wire:navigate
                       @if($isActive('admin.senatorial-districts')) aria-current="page" @endif
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('admin.senatorial-districts') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ $isActive('admin.senatorial-districts') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Senatorial Districts
                    </a>
                    <a href="{{ route('admin.wards') }}" wire:navigate
                       @if($isActive('admin.wards')) aria-current="page" @endif
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('admin.wards') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ $isActive('admin.wards') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Ward Management
                    </a>
                    <a href="{{ route('admin.polling-stations') }}" wire:navigate
                       @if($isActive('admin.polling-stations')) aria-current="page" @endif
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('admin.polling-stations') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ $isActive('admin.polling-stations') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        Polling Stations
                    </a>
                    <a href="{{ route('admin.parties') }}" wire:navigate
                       @if($isActive('admin.parties')) aria-current="page" @endif
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('admin.parties') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ $isActive('admin.parties') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                        Parties
                    </a>
                    <a href="{{ route('admin.candidates') }}" wire:navigate
                       @if($isActive('admin.candidates')) aria-current="page" @endif
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $isActive('admin.candidates') ? 'bg-green-700/80 text-white shadow-sm' : 'text-green-200 hover:bg-green-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 {{ $isActive('admin.candidates') ? '' : 'opacity-70 group-hover:opacity-100' }} transition" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Candidates
                    </a>
                </div>
            </div>
            @endif
        </nav>

        {{-- User profile footer --}}
        <div class="px-4 py-4 border-t border-green-800/50">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-green-600 to-green-700 rounded-full flex items-center justify-center text-xs font-bold shadow-sm" aria-hidden="true">
                    {{ strtoupper(substr(auth()->user()->full_name ?? 'U', 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-white truncate">{{ auth()->user()->full_name }}</div>
                    <div class="text-[11px] text-green-400">{{ ucwords(str_replace('_', ' ', auth()->user()->role)) }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-green-400 hover:text-white hover:bg-green-800 p-1.5 rounded-lg transition" aria-label="Sign out" title="Sign out">
                        <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
        @endauth
    </aside>

    {{-- Main content --}}
    <main class="flex-1 flex flex-col min-h-0 overflow-auto lg:ml-0">
        {{-- Top header bar --}}
        <header class="bg-white border-b border-gray-200 px-4 lg:px-6 py-3 flex items-center justify-between flex-shrink-0 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button
                    @click="sidebarOpen = true"
                    class="lg:hidden text-gray-500 hover:text-gray-700 p-1.5 rounded-lg hover:bg-gray-100 transition"
                    aria-label="Open navigation menu"
                    :aria-expanded="sidebarOpen.toString()"
                >
                    <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                {{-- Breadcrumb --}}
                <nav class="flex items-center gap-1.5 text-sm" aria-label="Breadcrumb">
                    <a href="{{ auth()->user()?->isFieldAgent() ? route('agent.home') : route('dashboard') }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition hidden sm:inline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    </a>
                    <svg class="w-4 h-4 text-gray-300 hidden sm:inline" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/></svg>
                    <h2 class="text-base font-semibold text-gray-900">{{ $title ?? $header ?? 'Dashboard' }}</h2>
                </nav>
            </div>

            <div class="flex items-center gap-3">
                @auth
                {{-- Notification bell --}}
                @php
                    $unreadNotifs = \App\Models\JesrNotification::where('recipient_user_id', auth()->id())->where('is_read', false)->count();
                @endphp
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition" aria-label="Notifications">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if($unreadNotifs > 0)
                        <span class="absolute -top-0.5 -right-0.5 w-4.5 h-4.5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center min-w-[18px] px-1">
                            {{ $unreadNotifs > 9 ? '9+' : $unreadNotifs }}
                        </span>
                        @endif
                    </button>
                </div>
                @endauth
                <div class="text-xs text-gray-400 hidden md:flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ now()->format('D, d M Y H:i') }}
                </div>
            </div>
        </header>

        {{-- Page content with transition --}}
        <div class="flex-1 p-4 lg:p-6 page-enter">
            {{ $slot }}
        </div>
    </main>
</div>

@livewireScripts
@stack('scripts')
</body>
</html>
