@php
    use App\Support\GenreFilter;

    $genres = $toolbarMediaType ? GenreFilter::genresForMediaType($toolbarMediaType) : GenreFilter::GENRES;
    $years = GenreFilter::buildYearList(36);
    $sortOptions = [
        '' => 'Latest Added',
        'popular' => 'Most Popular',
        'rating' => 'Top Rated',
    ];
    $titleCount = $total > 0 ? $total : $libraryCount;
@endphp

<div class="mb-5 border-b border-white/10 pb-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <div class="flex items-center gap-3">
            <span class="w-[3px] h-5 bg-accent rounded-full"></span>
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    @if ($toolbarIcon === 'movie')
                        <span class="text-accent">🎬</span>
                    @else
                        <span class="text-accent">📺</span>
                    @endif
                    {{ $title }}
                </h1>
                @if ($titleCount > 0)
                    <p class="text-muted text-sm mt-0.5">{{ number_format($titleCount) }} titles</p>
                @endif
            </div>
        </div>
    </div>

    <form method="GET" action="{{ $basePath }}" class="space-y-3">
        <div class="flex flex-wrap gap-2 items-center">
            <span class="text-xs text-muted uppercase tracking-wide mr-1">Sort</span>
            @foreach ($sortOptions as $value => $label)
                <button type="submit" name="sort" value="{{ $value }}"
                        class="rounded-full px-3 py-1.5 text-xs sm:text-sm border transition-colors
                        {{ ($sort ?? '') === $value ? 'bg-accent/15 text-accent border-accent/35' : 'bg-bg-3 text-muted border-white/5 hover:text-white' }}">
                    {{ $label }}
                </button>
            @endforeach
            @if ($genre)<input type="hidden" name="genre" value="{{ $genre }}">@endif
            @if ($year)<input type="hidden" name="year" value="{{ $year }}">@endif
        </div>

        <div class="flex flex-wrap gap-2 items-center">
            <span class="text-xs text-muted uppercase tracking-wide mr-1">Genre</span>
            <button type="submit" class="rounded-full px-3 py-1.5 text-xs border {{ ! $genre ? 'bg-accent/15 text-accent border-accent/35' : 'bg-bg-3 text-muted border-white/5 hover:text-white' }}">All</button>
            @foreach ($genres as $g)
                <button type="submit" name="genre" value="{{ $g }}"
                        class="rounded-full px-3 py-1.5 text-xs border {{ $genre === $g ? 'bg-accent/15 text-accent border-accent/35' : 'bg-bg-3 text-muted border-white/5 hover:text-white' }}">
                    {{ $g }}
                </button>
            @endforeach
            @if ($sort)<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($year)<input type="hidden" name="year" value="{{ $year }}">@endif
        </div>

        <div class="flex flex-wrap gap-2 items-center max-h-24 overflow-y-auto">
            <span class="text-xs text-muted uppercase tracking-wide mr-1">Year</span>
            <button type="submit" class="rounded-full px-3 py-1.5 text-xs border min-w-[3.25rem] {{ ! $year ? 'bg-accent/15 text-accent border-accent/35' : 'bg-bg-3 text-muted border-white/5 hover:text-white' }}">All</button>
            @foreach ($years as $y)
                <button type="submit" name="year" value="{{ $y }}"
                        class="rounded-full px-3 py-1.5 text-xs border min-w-[3.25rem] text-center {{ $year === $y ? 'bg-accent/15 text-accent border-accent/35' : 'bg-bg-3 text-muted border-white/5 hover:text-white' }}">
                    {{ $y }}
                </button>
            @endforeach
            @if ($genre)<input type="hidden" name="genre" value="{{ $genre }}">@endif
            @if ($sort)<input type="hidden" name="sort" value="{{ $sort }}">@endif
        </div>
    </form>
</div>
