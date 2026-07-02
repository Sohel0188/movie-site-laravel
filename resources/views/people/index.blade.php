@extends('layouts.app')

@section('title', 'People — StreamVault')

@section('content')
<section class="mt-6 sm:mt-8">
    <div class="flex items-center justify-between mb-5 border-b border-white/10 pb-5">
        <div class="flex items-center gap-3">
            <span class="w-[3px] h-5 bg-accent rounded-full"></span>
            <div>
                <h1 class="text-2xl font-bold">People</h1>
                @if ($libraryTotal > 0)
                    <p class="text-muted text-sm mt-0.5">{{ number_format($libraryTotal) }} in library</p>
                @endif
            </div>
        </div>
    </div>

    @if ($error)
        <div class="text-center py-10 text-red-400 text-sm">{{ $error }}</div>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        @foreach ($people as $person)
            <a href="{{ route('person.show', $person['tmdb_id']) }}" class="bg-surface border border-border rounded-xl overflow-hidden hover:border-accent/50 transition-colors text-center">
                <div class="aspect-[2/3] bg-bg-3 relative">
                    @if ($person['profile_url'])
                        <img src="{{ $person['profile_url'] }}" alt="{{ $person['name'] }}" class="w-full h-full object-cover" loading="lazy">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-4xl text-muted">👤</div>
                    @endif
                </div>
                <div class="p-3">
                    <p class="text-sm font-semibold truncate">{{ $person['name'] }}</p>
                    <p class="text-[11px] text-muted mt-0.5">{{ $person['department'] ?? 'Acting' }}</p>
                    @if (! empty($person['known_for']))
                        <p class="text-[10px] text-white/50 mt-1 truncate">{{ $person['known_for'] }}</p>
                    @endif
                </div>
            </a>
        @endforeach
    </div>

    @include('components.pagination', [
        'page' => $page,
        'totalPages' => $totalPages,
        'total' => $total,
        'browseUrl' => fn ($q = []) => route('people', $q),
    ])
</section>
@endsection
