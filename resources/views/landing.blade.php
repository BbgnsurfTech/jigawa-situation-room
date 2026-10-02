<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JESR — Jigawa Election Situation Room</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white antialiased">

{{-- ── HEADER ──────────────────────────────────────────────────────────── --}}
<header class="bg-green-950 text-white">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            {{-- Logo --}}
            <img src="{{ asset('images/jigawa-logo.png') }}"
                 alt="Jigawa State"
                 class="h-14 w-auto drop-shadow-md"
                 onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='flex'">
            <div id="logo-fallback" style="display:none"
                 class="w-14 h-14 rounded-full bg-green-800 border-2 border-green-400 items-center justify-center text-green-300 font-bold text-lg">JS</div>
            <div class="border-l border-green-700 pl-4">
                <div class="text-xs font-semibold text-green-300 uppercase tracking-widest">Jigawa State Government</div>
                <div class="text-lg font-bold leading-tight">Election Situation Room</div>
                <div class="text-xs text-green-400">JESR — Monitoring &amp; Reporting Platform</div>
            </div>
        </div>
        <a href="{{ route('login') }}"
           class="bg-white text-green-900 hover:bg-green-50 font-semibold text-sm px-5 py-2.5 rounded-lg transition shadow-sm">
            Sign In
        </a>
    </div>
</header>

{{-- ── HERO ─────────────────────────────────────────────────────────────── --}}
<section class="relative overflow-hidden bg-gradient-to-br from-green-950 via-green-900 to-green-800 text-white">
    {{-- Decorative circles --}}
    <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-green-800 opacity-30"></div>
    <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-green-700 opacity-20"></div>

    <div class="relative max-w-7xl mx-auto px-6 py-24 md:py-32 flex flex-col md:flex-row items-center gap-16">
        {{-- Text --}}
        <div class="flex-1 text-center md:text-left">
            <div class="inline-flex items-center gap-2 bg-green-800 bg-opacity-60 text-green-300 text-xs font-medium px-4 py-1.5 rounded-full mb-6 border border-green-700">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                Election Monitoring — Jigawa State, Nigeria
            </div>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight mb-6">
                Real-Time<br>
                <span class="text-green-400">Election</span><br>
                Intelligence
            </h1>
            <p class="text-green-200 text-lg max-w-xl mb-10 leading-relaxed">
                A secure, multi-channel platform for monitoring ward-level election results
                across all <strong class="text-white">27 LGAs</strong> and
                <strong class="text-white">287 Electoral Wards</strong> in Jigawa State.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center gap-2 bg-green-500 hover:bg-green-400 text-white font-bold px-8 py-3.5 rounded-xl transition shadow-lg text-base">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Access Situation Room
                </a>
                <a href="#features"
                   class="inline-flex items-center justify-center gap-2 border border-green-600 text-green-300 hover:bg-green-800 font-semibold px-8 py-3.5 rounded-xl transition text-base">
                    Learn More
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </a>
            </div>
        </div>

        {{-- Stats block --}}
        <div class="flex-shrink-0 grid grid-cols-2 gap-4 w-full max-w-sm">
            @foreach([
                ['27', 'Local Government Areas', '🏛️'],
                ['287', 'Electoral Wards', '🗺️'],
                ['3', 'User Role Levels', '👥'],
                ['4', 'Reporting Channels', '📡'],
            ] as [$num, $label, $icon])
            <div class="bg-green-800 bg-opacity-60 backdrop-blur border border-green-700 rounded-2xl p-5 text-center">
                <div class="text-3xl mb-1">{{ $icon }}</div>
                <div class="text-3xl font-black text-white">{{ $num }}</div>
                <div class="text-xs text-green-300 mt-1 leading-tight">{{ $label }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Wave --}}
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" class="w-full h-12">
            <path d="M0 60L60 50C120 40 240 20 360 15C480 10 600 20 720 28C840 35 960 40 1080 38C1200 35 1320 25 1380 20L1440 15V60H0Z" fill="white"/>
        </svg>
    </div>
</section>

{{-- ── FEATURES ─────────────────────────────────────────────────────────── --}}
<section id="features" class="py-20 px-6 bg-white">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-14">
            <div class="text-xs font-bold text-green-700 uppercase tracking-widest mb-2">Platform Capabilities</div>
            <h2 class="text-3xl font-bold text-gray-900">Built for Election Day Operations</h2>
            <p class="text-gray-500 mt-3 max-w-xl mx-auto text-sm">Designed to meet INEC standards with tamper-proof audit trails, multi-channel reporting, and real-time escalation.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                [
                    'Real-Time Dashboard',
                    'Live KPI overview across all 27 LGAs. Auto-refreshes every 15 seconds with submissions, verification status, and security incident counts.',
                    'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                    'bg-green-50 text-green-700',
                ],
                [
                    'Multi-Channel Reporting',
                    'Ward agents can submit results via Web, Android app, iOS app, or SMS — ensuring coverage even in areas with limited internet connectivity.',
                    'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                    'bg-emerald-50 text-emerald-700',
                ],
                [
                    'Immutable Audit Trail',
                    'Every action is cryptographically logged in an append-only audit table. Nothing can be modified or deleted — full chain of custody for every report.',
                    'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'bg-yellow-50 text-yellow-700',
                ],
                [
                    'Role-Based Access Control',
                    'Three-tier hierarchy: Super Admin (state-wide), Supervisor (LGA-scoped), and Ward Agent (own ward only). Every data query is automatically scoped.',
                    'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                    'bg-purple-50 text-purple-700',
                ],
                [
                    'Escalation Engine',
                    '3-level escalation: LGA Coordinator (10 min), State Coordinator (30 min), Steering Committee (immediate). Automatic SMS + push notification cascade.',
                    'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                    'bg-red-50 text-red-700',
                ],
                [
                    'SMS Gateway Integration',
                    'Parse structured SMS reports from field agents using the JIG REPORT format. Automatic validation, acknowledgement, and database creation — no internet needed.',
                    'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                    'bg-teal-50 text-teal-700',
                ],
            ] as [$title, $desc, $path, $color])
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition p-6">
                <div class="w-11 h-11 rounded-xl {{ $color }} bg-opacity-80 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">{{ $title }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── HOW IT WORKS ─────────────────────────────────────────────────────── --}}
<section class="py-20 px-6 bg-gray-50">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-14">
            <div class="text-xs font-bold text-green-700 uppercase tracking-widest mb-2">Workflow</div>
            <h2 class="text-3xl font-bold text-gray-900">From Polling Unit to State Command</h2>
        </div>

        <div class="relative">
            {{-- Vertical line --}}
            <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-green-200 hidden md:block"></div>

            <div class="space-y-8">
                @foreach([
                    ['01', 'Ward Agent Submits Report', 'Agent at polling unit enters results (Parts A–D) via web form, mobile app, or SMS after voting closes.', 'text-green-700 bg-green-100'],
                    ['02', 'Supervisor Reviews & Verifies', 'LGA supervisor receives live notification, reviews ballot figures, security status, and either verifies or escalates the report.', 'text-yellow-700 bg-yellow-100'],
                    ['03', 'Escalation (if needed)', 'Anomalies trigger a 3-level escalation chain with automatic SMS and push notifications to LGA and State coordinators.', 'text-red-700 bg-red-100'],
                    ['04', 'State Dashboard Updates', 'Super Admin sees live aggregated results, ward coverage metrics, security incidents, and candidate tallies across all 27 LGAs.', 'text-purple-700 bg-purple-100'],
                ] as [$step, $title, $desc, $badge])
                <div class="relative flex gap-6 md:gap-10 items-start">
                    <div class="flex-shrink-0 w-16 h-16 rounded-full {{ $badge }} flex items-center justify-center font-black text-lg z-10">
                        {{ $step }}
                    </div>
                    <div class="flex-1 bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                        <h4 class="font-bold text-gray-900 mb-1">{{ $title }}</h4>
                        <p class="text-sm text-gray-500">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ── CTA BANNER ───────────────────────────────────────────────────────── --}}
<section class="py-20 px-6 bg-gradient-to-r from-green-950 to-green-800 text-white">
    <div class="max-w-3xl mx-auto text-center">
        <div class="flex justify-center mb-6">
            <img src="{{ asset('images/jigawa-logo.png') }}"
                 alt="Jigawa State"
                 class="h-20 w-auto drop-shadow-xl opacity-90"
                 onerror="this.style.display='none'">
        </div>
        <h2 class="text-3xl font-extrabold mb-4">Ready to Monitor?</h2>
        <p class="text-green-200 mb-8 text-base">
            Access the Jigawa Election Situation Room with your assigned credentials.
            Contact your LGA coordinator if you need account access.
        </p>
        <a href="{{ route('login') }}"
           class="inline-flex items-center gap-2 bg-white text-green-900 hover:bg-green-50 font-bold px-10 py-4 rounded-xl transition shadow-lg text-base">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Sign In to JESR
        </a>
    </div>
</section>

{{-- ── FOOTER ───────────────────────────────────────────────────────────── --}}
<footer class="bg-green-950 text-green-400 py-8 px-6">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/jigawa-logo.png') }}"
                 alt="Jigawa State"
                 class="h-8 w-auto opacity-70"
                 onerror="this.style.display='none'">
            <span>Jigawa State Government · Election Situation Room · JESR v1.0</span>
        </div>
        <div class="text-green-600">
            Powered by INEC — Independent National Electoral Commission, Jigawa State
        </div>
    </div>
</footer>

</body>
</html>
