@if ($totalPages > 1)
@php
    function svBuildRange(int $current, int $total): array {
        if ($total <= 7) return range(1, $total);
        if ($current <= 4) return [1, 2, 3, 4, 5, '...', $total];
        if ($current >= $total - 3) return [1, '...', $total - 4, $total - 3, $total - 2, $total - 1, $total];
        return [1, '...', $current - 1, $current, $current + 1, '...', $total];
    }
    $range = svBuildRange($page, $totalPages);
@endphp

<div class="flex flex-col items-center gap-3 mt-8">
    <p class="text-xs text-muted">
        Page <span class="text-white font-medium">{{ $page }}</span> of
        <span class="text-white font-medium">{{ number_format($totalPages) }}</span>
        · <span class="text-accent font-medium">{{ number_format($total) }}</span> total
    </p>

    <div class="flex items-center gap-1.5 flex-wrap justify-center">
        @if ($page > 1)
            <a href="{{ $browseUrl(['page' => $page - 1]) }}" class="pagination-btn">‹</a>
        @else
            <span class="pagination-btn opacity-30 cursor-not-allowed">‹</span>
        @endif

        @foreach ($range as $p)
            @if ($p === '...')
                <span class="text-muted text-sm px-1">…</span>
            @elseif ($p === $page)
                <span class="pagination-btn pagination-btn-active">{{ $p }}</span>
            @else
                <a href="{{ $browseUrl(['page' => $p]) }}" class="pagination-btn">{{ $p }}</a>
            @endif
        @endforeach

        @if ($page < $totalPages)
            <a href="{{ $browseUrl(['page' => $page + 1]) }}" class="pagination-btn">›</a>
        @else
            <span class="pagination-btn opacity-30 cursor-not-allowed">›</span>
        @endif
    </div>
</div>
@endif
