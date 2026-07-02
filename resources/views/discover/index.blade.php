@extends('layouts.app')

@section('title', $title . ' — StreamVault')

@section('content')
<section class="mt-6 sm:mt-8">
    @if ($filterable && $toolbarIcon)
        @include('components.browse-toolbar')
    @else
        <div class="flex items-center justify-between mb-5 border-b border-white/10 pb-5">
            <div class="flex items-center gap-3">
                <span class="w-[3px] h-5 bg-accent rounded-full"></span>
                <div>
                    <h1 class="text-2xl font-bold">{{ $title }}</h1>
                    @if ($subtitle)
                        <p class="text-muted text-sm mt-0.5">{{ $subtitle }}</p>
                    @elseif ($total > 0 && ($genre || $year))
                        <p class="text-muted text-sm mt-0.5">{{ number_format($total) }} titles</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if ($error)
        <div class="text-center py-10 text-red-400 text-sm">{{ $error }}</div>
    @endif

    @if (count($items) === 0 && ! $error)
        <div class="text-center py-16 text-muted">
            <p class="text-4xl mb-3">🎬</p>
            <p>No titles found.</p>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3.5">
            @foreach ($items as $item)
                @include('components.media-card', ['item' => $item, 'mediaType' => $mediaType])
            @endforeach
        </div>

        @include('components.pagination', [
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'browseUrl' => $browseUrl,
        ])
    @endif
</section>
@endsection
