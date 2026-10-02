<!DOCTYPE html>
<html lang="en" class="h-full bg-green-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JESR — Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="h-full flex items-center justify-center">
    {{-- Toast notifications for guest pages --}}
    <div
        x-data="{
            toasts: [],
            add(msg, type = 'info') {
                const id = Date.now();
                this.toasts.push({ id, msg, type });
                setTimeout(() => this.remove(id), 5000);
            },
            remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
        }"
        @notify.window="add($event.detail.message ?? $event.detail[0]?.message ?? '', $event.detail.type ?? 'info')"
        class="fixed top-4 right-4 z-[200] flex flex-col gap-2 max-w-sm w-full pointer-events-none"
        aria-live="polite"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0 translate-x-8"
                class="flex items-start gap-3 bg-gray-800 text-white px-4 py-3 rounded-xl shadow-lg text-sm pointer-events-auto"
            >
                <span class="flex-1" x-text="toast.msg"></span>
                <button @click="remove(toast.id)" aria-label="Dismiss" class="text-white/60 hover:text-white shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

    {{ $slot }}
    @livewireScripts
</body>
</html>
