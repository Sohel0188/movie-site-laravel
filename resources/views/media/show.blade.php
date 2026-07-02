@php
    use App\Support\StreamVaultRoutes;

    $metaParts = array_filter([
        $detail['year'],
        $detail['runtime'],
        ...array_slice($detail['genres'] ?? [], 0, 3),
    ]);
@endphp

@extends('layouts.app')

@section('title', $detail['title'] . ' — StreamVault')

@section('content')
<div class="relative w-full min-w-0 overflow-x-hidden" x-data="playerModal(@js([
    'embedUrl' => $embedUrl,
    'title' => $detail['title'],
    'meta' => implode(' · ', $metaParts),
    'type' => $playerType,
    'tmdbId' => $detail['tmdb_id'],
    'imdbId' => $detail['imdb_id'],
]))">
    <section class="relative min-h-[56vh] sm:min-h-[64vh] flex flex-col justify-end overflow-hidden rounded-2xl border border-white/5">
        @if ($detail['backdrop_url'])
            <img src="{{ $detail['backdrop_url'] }}" alt="" class="absolute inset-0 w-full h-full object-cover object-center">
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/70 to-black/30"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/30 to-transparent"></div>

        <div class="relative z-10 px-4 sm:px-8 pb-8 sm:pb-10 pt-6 max-w-3xl">
            <button type="button" onclick="history.back()" class="inline-flex items-center gap-1.5 text-sm text-white/70 hover:text-white mb-4 transition-colors">← Back</button>

            <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight tracking-wide">{{ strtoupper($detail['title']) }}</h1>
            <p class="text-sm text-white/75 mt-3">{{ implode(' · ', $metaParts) }}</p>

            @if ($detail['overview'])
                <p class="text-sm text-white/80 mt-4 leading-relaxed line-clamp-2 max-w-2xl">{{ $detail['overview'] }}</p>
            @endif

            <div class="flex items-center gap-3 mt-6 flex-wrap">
                <button type="button" @click="open = true" class="inline-flex items-center justify-center gap-2 bg-white text-black font-semibold rounded-lg px-8 py-3 min-w-[200px] hover:bg-white/90 transition-colors">
                    ▶ Play
                </button>
                <button type="button" onclick="document.getElementById('similar')?.scrollIntoView({behavior:'smooth'})" class="inline-flex items-center gap-2 bg-white/15 backdrop-blur text-white text-sm font-medium rounded-lg px-4 py-2.5 hover:bg-white/25 transition-colors">
                    Similars
                </button>
            </div>
        </div>
    </section>

    @if (count($detail['cast'] ?? []) > 0)
        <section class="py-8 border-t border-white/5">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-[3px] h-5 bg-accent rounded-full"></span>
                <h2 class="text-lg font-bold">Actors</h2>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach ($detail['cast'] as $actor)
                    <a href="{{ route('person.show', $actor['tmdb_id']) }}" class="bg-surface border border-border rounded-xl overflow-hidden hover:border-accent/50 transition-colors">
                        <div class="aspect-[2/3] bg-bg-3 relative">
                            @if ($actor['profile_url'])
                                <img src="{{ $actor['profile_url'] }}" alt="{{ $actor['name'] }}" class="w-full h-full object-cover" loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-muted text-2xl">👤</div>
                            @endif
                        </div>
                        <div class="p-2.5">
                            <p class="text-xs font-semibold truncate">{{ $actor['name'] }}</p>
                            <p class="text-[10px] text-muted truncate">{{ $actor['role'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if (count($detail['keywords'] ?? []) > 0)
        <section class="pb-8">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-[3px] h-5 bg-accent rounded-full"></span>
                <h2 class="text-lg font-bold">Keywords</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($detail['keywords'] as $keyword)
                    <a href="{{ route('keyword', $keyword['id']) }}" class="text-xs text-white/80 bg-surface border border-border rounded-full px-3 py-1.5 hover:border-accent hover:text-accent transition-colors">{{ $keyword['name'] }}</a>
                @endforeach
            </div>
        </section>
    @endif

    @if (count($detail['similar'] ?? []) > 0)
        <section id="similar" class="pb-12">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-[3px] h-5 bg-accent rounded-full"></span>
                <h2 class="text-lg font-bold">You may like</h2>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach ($detail['similar'] as $item)
                    <a href="{{ StreamVaultRoutes::mediaHref($item, $item['type']) }}" class="group bg-surface border border-border rounded-xl overflow-hidden hover:border-accent/50 transition-colors">
                        <div class="aspect-video relative bg-bg-3">
                            @if ($item['backdrop_url'] || $item['poster_url'])
                                <img src="{{ $item['backdrop_url'] ?? $item['poster_url'] }}" alt="{{ $item['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                            @endif
                        </div>
                        <div class="p-2.5">
                            <p class="text-xs font-semibold truncate">{{ $item['title'] }}</p>
                            <p class="text-[10px] text-muted mt-0.5">{{ $item['year'] }} {{ $item['type'] === 'tv' ? 'TV Show' : 'Movie' }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @include('components.player-modal')
</div>
@endsection
