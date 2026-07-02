@php use App\Support\StreamVaultRoutes; @endphp

@extends('layouts.app')

@section('title', ($query ? "Results for \"{$query}\"" : 'Search') . ' — StreamVault')

@section('content')
<section class="mt-10">
    <div class="flex items-center gap-3 mb-5">
        <span class="w-[3px] h-5 bg-accent rounded-full"></span>
        <h1 class="text-2xl font-bold">{{ $query ? "Results for \"{$query}\"" : 'Search' }}</h1>
    </div>

    @if (! $query)
        <p class="text-muted text-sm">Use the search icon in the navigation bar.</p>
    @elseif ($error)
        <div class="text-center py-10 text-red-400 text-sm">{{ $error }}</div>
    @elseif (count($results) === 0)
        <div class="text-center py-16 text-muted">
            <p class="text-4xl mb-3">🔍</p>
            <p>No results found for "{{ $query }}"</p>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3.5">
            @foreach ($results as $item)
                <a href="{{ StreamVaultRoutes::mediaHref($item, $item['_type'] ?? null) }}" class="bg-surface border border-border rounded-xl overflow-hidden hover:-translate-y-1 hover:border-accent transition-all">
                    <div class="aspect-[2/3] relative bg-bg-3 flex items-center justify-center text-3xl">
                        @if (! empty($item['poster_url']))
                            <img src="{{ $item['poster_url'] }}" alt="" class="w-full h-full object-cover" loading="lazy">
                        @else
                            🎬
                        @endif
                    </div>
                    <div class="p-2.5">
                        <p class="text-xs font-semibold truncate">{{ $item['title'] ?? '' }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
@endsection
