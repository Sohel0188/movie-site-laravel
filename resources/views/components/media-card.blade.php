@php
    use App\Support\StreamVaultRoutes;

    $type = $item['_type'] ?? $mediaType;
    $isEp = $type === 'episodes';
    $isTV = $type === 'tv' || ($item['type'] ?? '') === 'tv';
    $title = $isEp ? ($item['show_title'] ?? '') : ($item['title'] ?? '');
    $subTitle = $isEp ? ($item['episode_title'] ?? null) : null;
    $year = $isEp ? substr($item['air_date'] ?? '', 0, 4) : ($item['year'] ?? '');
    $rating = $item['rating'] ?? '';
    $posterUrl = $isEp ? null : ($item['poster_url'] ?? null);
    $href = StreamVaultRoutes::mediaHref($item, $type);
    $badgeText = $isEp
        ? 'S'.str_pad($item['season_number'] ?? '1', 2, '0', STR_PAD_LEFT).'E'.str_pad($item['episode_number'] ?? '1', 2, '0', STR_PAD_LEFT)
        : ($isTV ? 'TV' : 'MOVIE');
    $badgeColor = $isEp ? 'bg-blue-500 text-white' : ($isTV ? 'bg-purple-500 text-white' : 'bg-accent text-black');
@endphp

<a href="{{ $href }}" class="group relative bg-surface border border-border rounded-xl overflow-hidden cursor-pointer transition-all duration-200 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/60 hover:border-accent {{ $isEp ? 'aspect-video' : '' }}">
    <div class="relative overflow-hidden {{ $isEp ? 'h-full' : 'aspect-[2/3]' }}">
        @if ($posterUrl)
            <img src="{{ $posterUrl }}" alt="{{ $title }}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" loading="lazy">
        @else
            <div class="w-full h-full bg-bg-3 flex items-center justify-center text-4xl">🎬</div>
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center">
            <div class="w-11 h-11 rounded-full bg-accent flex items-center justify-center -mt-6 shadow-lg shadow-accent/40">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="black"><polygon points="5,3 19,12 5,21"/></svg>
            </div>
        </div>

        <span class="absolute top-2 right-2 text-[10px] font-bold px-1.5 py-0.5 rounded {{ $badgeColor }}">{{ $badgeText }}</span>

        @if ($isEp && $subTitle)
            <span class="absolute bottom-2 left-2 text-[10px] text-white/80 bg-black/60 px-1.5 py-0.5 rounded max-w-[90%] truncate">{{ $subTitle }}</span>
        @endif
    </div>

    <div class="p-2.5">
        <p class="text-[12px] font-semibold text-white truncate">{{ $isEp ? ($subTitle ?: $title) : $title }}</p>
        <div class="flex items-center justify-between mt-1">
            <span class="text-[10px] text-muted">{{ $year }}</span>
            @if ($rating)
                <span class="text-[10px] text-yellow-400 font-semibold">⭐ {{ $rating }}</span>
            @endif
        </div>
    </div>
</a>
