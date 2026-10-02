@props(['paginator'])

@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col gap-2">
    {{-- Results summary --}}
    <p class="text-[11px] text-gray-500 text-center">
        Showing
        <span class="font-medium text-gray-700">{{ $paginator->firstItem() }}</span>
        to
        <span class="font-medium text-gray-700">{{ $paginator->lastItem() }}</span>
        of
        <span class="font-medium text-gray-700">{{ number_format($paginator->total()) }}</span>
        results
    </p>

    {{-- Page buttons --}}
    <div class="flex items-center justify-center gap-1">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="w-7 h-7 flex items-center justify-center rounded text-gray-300 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </span>
        @else
            <button wire:click="previousPage" wire:loading.attr="disabled" class="w-7 h-7 flex items-center justify-center rounded text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
        @endif

        {{-- Page Numbers --}}
        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();

            // Calculate visible page range (show max 5 pages)
            $start = max(1, $currentPage - 2);
            $end = min($lastPage, $currentPage + 2);

            // Adjust if we're near the start or end
            if ($currentPage <= 3) {
                $end = min($lastPage, 5);
            }
            if ($currentPage >= $lastPage - 2) {
                $start = max(1, $lastPage - 4);
            }
        @endphp

        @for ($page = $start; $page <= $end; $page++)
            @if ($page == $currentPage)
                <span class="w-7 h-7 flex items-center justify-center rounded bg-green-600 text-white text-xs font-semibold">
                    {{ $page }}
                </span>
            @else
                <button wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled"
                    class="w-7 h-7 flex items-center justify-center rounded text-gray-600 hover:bg-gray-100 text-xs font-medium transition">
                    {{ $page }}
                </button>
            @endif
        @endfor

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <button wire:click="nextPage" wire:loading.attr="disabled" class="w-7 h-7 flex items-center justify-center rounded text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        @else
            <span class="w-7 h-7 flex items-center justify-center rounded text-gray-300 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </span>
        @endif
    </div>
</nav>
@endif
