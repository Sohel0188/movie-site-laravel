@php
    use App\Support\StreamVaultRoutes;
@endphp

@extends('layouts.app')

@section('title', ($person['name'] ?? 'Person') . ' — StreamVault')

@section('content')
<section class="mt-8">
    <div class="flex flex-col sm:flex-row gap-6 sm:gap-8">
        <div class="shrink-0 mx-auto sm:mx-0">
            <div class="w-40 h-40 sm:w-48 sm:h-48 rounded-2xl overflow-hidden bg-surface border border-border">
                @if ($person['profile_url'])
                    <img src="{{ $person['profile_url'] }}" alt="{{ $person['name'] }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-5xl text-muted">👤</div>
                @endif
            </div>
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-3xl font-bold">{{ $person['name'] }}</h1>
            <p class="text-muted text-sm mt-1">{{ $person['department'] ?? 'Acting' }}</p>
            @if (! empty($person['birthday']) || ! empty($person['place_of_birth']))
                <p class="text-sm text-white/70 mt-3">
                    {{ $person['birthday'] ?? '' }}
                    @if ($person['place_of_birth']) · {{ $person['place_of_birth'] }} @endif
                </p>
            @endif
            @if (! empty($person['biography']))
                <p class="text-sm text-white/80 mt-4 leading-relaxed line-clamp-6">{{ $person['biography'] }}</p>
            @endif
        </div>
    </div>

    @if (count($person['movies'] ?? []) > 0)
        <section class="mt-12">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <span class="w-[3px] h-5 bg-accent rounded-full"></span>
                    <h2 class="text-xl font-bold">Movies</h2>
                </div>
                <span class="text-muted text-sm">{{ number_format($person['movie_count'] ?? 0) }} titles</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3.5">
                @foreach ($person['movies'] as $item)
                    @include('components.media-card', ['item' => array_merge($item, ['_type' => 'movies']), 'mediaType' => 'movies'])
                @endforeach
            </div>
        </section>
    @endif

    @if (count($person['tv_shows'] ?? []) > 0)
        <section class="mt-12">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <span class="w-[3px] h-5 bg-accent rounded-full"></span>
                    <h2 class="text-xl font-bold">TV Shows</h2>
                </div>
                <span class="text-muted text-sm">{{ number_format($person['tv_count'] ?? 0) }} titles</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3.5">
                @foreach ($person['tv_shows'] as $item)
                    @include('components.media-card', ['item' => array_merge($item, ['_type' => 'tv']), 'mediaType' => 'tv'])
                @endforeach
            </div>
        </section>
    @endif
</section>
@endsection
