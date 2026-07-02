@php
    use App\Support\Countries;
    use App\Support\GenreFilter;
    use App\Support\Languages;

    $pathname = request()->path() === '/' ? '/' : '/'.request()->path();
    $isMediaPage = (bool) preg_match('#^/(movie|tv)/#', $pathname);
    $isCategory = str_starts_with($pathname, '/category/');
    $isYear = str_starts_with($pathname, '/year/');
    $isLanguage = str_starts_with($pathname, '/language/');
    $isCountry = str_starts_with($pathname, '/country/');
    $currentGenreSlug = $isCategory ? explode('/', trim($pathname, '/'))[1] ?? null : null;
    $currentYear = $isYear ? explode('/', trim($pathname, '/'))[1] ?? null : null;
    $currentLanguage = $isLanguage ? explode('/', trim($pathname, '/'))[1] ?? null : null;
    $currentCountry = $isCountry ? strtoupper(explode('/', trim($pathname, '/'))[1] ?? '') : null;
    $years = GenreFilter::buildYearList(35);
    $moreActive = in_array($pathname, ['/most-viewed', '/top-tv', '/people'], true);
    $navBg = $isMediaPage ? 'bg-black/95 backdrop-blur-md border-b border-white/10' : 'bg-black/80 backdrop-blur-md border-b border-white/5';
@endphp

<header class="fixed top-0 left-0 right-0 z-50 {{ $navBg }}" x-data="navbar()">
    <div class="h-14 max-w-[1400px] mx-auto px-4 sm:px-6 flex items-center w-full overflow-visible">
        <a href="{{ route('home') }}" class="font-display text-base sm:text-lg xl:text-xl tracking-widest text-white shrink-0 mr-3">STREAMVAULT</a>

        <nav class="hidden xl:flex items-center flex-1 min-w-0 justify-end gap-0.5 overflow-visible">
            <a href="{{ route('movies') }}" class="nav-link {{ request()->routeIs('movies') ? 'nav-link-active' : '' }}">Movies</a>
            <a href="{{ route('tv-shows') }}" class="nav-link {{ request()->routeIs('tv-shows') ? 'nav-link-active' : '' }}">TV Shows</a>
            <a href="{{ route('browse') }}" class="nav-link {{ request()->routeIs('browse') ? 'nav-link-active' : '' }}">Browse</a>

            <div class="relative shrink-0" x-on:click.outside="if (openMenu === 'genres') openMenu = null">
                <button type="button" x-on:click.stop="toggleMenu('genres')" class="nav-link flex items-center gap-1 {{ $isCategory ? 'nav-link-active' : '' }}">
                    Genres <span class="text-xs" x-text="openMenu === 'genres' ? '▲' : '▼'"></span>
                </button>
                <div x-show="openMenu === 'genres'" x-cloak x-transition.opacity class="nav-dropdown">
                    @foreach (GenreFilter::GENRES as $genre)
                        @php $slug = GenreFilter::genreToSlug($genre); @endphp
                        <a href="{{ route('category', $slug) }}" class="nav-dropdown-link">{{ $genre }}</a>
                    @endforeach
                </div>
            </div>

            <div class="relative shrink-0" x-on:click.outside="if (openMenu === 'year') openMenu = null">
                <button type="button" x-on:click.stop="toggleMenu('year')" class="nav-link flex items-center gap-1 {{ $isYear ? 'nav-link-active' : '' }}">
                    Year <span class="text-xs" x-text="openMenu === 'year' ? '▲' : '▼'"></span>
                </button>
                <div x-show="openMenu === 'year'" x-cloak x-transition.opacity class="nav-dropdown">
                    @foreach ($years as $y)
                        <a href="{{ route('year', $y) }}" class="nav-dropdown-link">{{ $y }}</a>
                    @endforeach
                </div>
            </div>

            <div class="relative shrink-0" x-on:click.outside="if (openMenu === 'language') openMenu = null">
                <button type="button" x-on:click.stop="toggleMenu('language')" class="nav-link flex items-center gap-1 {{ $isLanguage ? 'nav-link-active' : '' }}">
                    Language <span class="text-xs" x-text="openMenu === 'language' ? '▲' : '▼'"></span>
                </button>
                <div x-show="openMenu === 'language'" x-cloak x-transition.opacity class="nav-dropdown">
                    @foreach (Languages::LIST as $lang)
                        <a href="{{ route('language', $lang['code']) }}" class="nav-dropdown-link">{{ $lang['label'] }}</a>
                    @endforeach
                </div>
            </div>

            <div class="relative shrink-0" x-on:click.outside="if (openMenu === 'country') openMenu = null">
                <button type="button" x-on:click.stop="toggleMenu('country')" class="nav-link flex items-center gap-1 {{ $isCountry ? 'nav-link-active' : '' }}">
                    Country <span class="text-xs" x-text="openMenu === 'country' ? '▲' : '▼'"></span>
                </button>
                <div x-show="openMenu === 'country'" x-cloak x-transition.opacity class="nav-dropdown">
                    @foreach (Countries::LIST as $country)
                        <a href="{{ route('country', $country['code']) }}" class="nav-dropdown-link">{{ $country['label'] }}</a>
                    @endforeach
                </div>
            </div>

            <div class="relative shrink-0" x-on:click.outside="if (openMenu === 'more') openMenu = null">
                <button type="button" x-on:click.stop="toggleMenu('more')" class="nav-link flex items-center gap-1 {{ $moreActive ? 'nav-link-active' : '' }}">
                    More <span class="text-xs" x-text="openMenu === 'more' ? '▲' : '▼'"></span>
                </button>
                <div x-show="openMenu === 'more'" x-cloak x-transition.opacity class="nav-dropdown right-0 left-auto">
                    <a href="{{ route('most-viewed') }}" class="nav-dropdown-link">Most Viewed</a>
                    <a href="{{ route('top-tv') }}" class="nav-dropdown-link">Top TV</a>
                    <a href="{{ route('people') }}" class="nav-dropdown-link">People</a>
                </div>
            </div>

            <div class="ml-1 shrink-0">
                <form action="{{ route('search') }}" method="GET" class="flex items-center gap-1.5" x-show="searchOpen" x-cloak>
                    <input type="text" name="q" x-ref="searchInput" placeholder="Search…" class="w-28 sm:w-40 xl:w-52 px-3 py-1.5 bg-white/10 border border-white/15 rounded-lg text-[13px] text-white placeholder-white/40 outline-none focus:border-white/30">
                    <button type="button" x-on:click="searchOpen = false" class="text-white/50 hover:text-white text-xs px-1">✕</button>
                </form>
                <button type="button" x-on:click="openSearch()" x-show="!searchOpen" class="p-2 text-white/70 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </button>
            </div>
        </nav>

        <div class="flex xl:hidden items-center ml-auto gap-0.5">
            <form action="{{ route('search') }}" method="GET" class="flex items-center gap-1.5" x-show="searchOpen" x-cloak>
                <input type="text" name="q" placeholder="Search…" class="w-28 px-3 py-1.5 bg-white/10 border border-white/15 rounded-lg text-[13px] text-white placeholder-white/40 outline-none">
            </form>
            <button type="button" x-on:click="openSearch()" x-show="!searchOpen" class="p-2 text-white/70 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </button>
            <button type="button" x-on:click="mobileOpen = !mobileOpen" class="p-2 text-white/80 hover:text-white">
                <svg x-show="!mobileOpen" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                <svg x-show="mobileOpen" x-cloak xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
    </div>

    <div x-show="mobileOpen" x-cloak class="fixed inset-0 top-14 z-40 xl:hidden" x-on:click.self="mobileOpen = false">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
        <div class="relative h-[calc(100vh-3.5rem)] w-full max-w-xs ml-auto bg-[#0d0d12] border-l border-white/10 overflow-y-auto shadow-2xl">
            <a href="{{ route('movies') }}" class="mobile-nav-link" x-on:click="mobileOpen = false">Movies</a>
            <a href="{{ route('tv-shows') }}" class="mobile-nav-link" x-on:click="mobileOpen = false">TV Shows</a>
            <a href="{{ route('browse') }}" class="mobile-nav-link" x-on:click="mobileOpen = false">Browse</a>

            @foreach (['genres' => 'Genres', 'year' => 'Year', 'language' => 'Language', 'country' => 'Country', 'more' => 'More'] as $key => $label)
                <div class="border-b border-white/5">
                    <button type="button" x-on:click="mobileSection = mobileSection === '{{ $key }}' ? null : '{{ $key }}'" class="w-full flex items-center justify-between px-4 py-3.5 text-[15px] text-white/75">
                        {{ $label }}
                        <span x-text="mobileSection === '{{ $key }}' ? '▲' : '▼'"></span>
                    </button>
                    <div x-show="mobileSection === '{{ $key }}'" x-cloak class="max-h-[45vh] overflow-y-auto bg-black/40 pb-2">
                        @if ($key === 'genres')
                            @foreach (GenreFilter::GENRES as $genre)
                                <a href="{{ route('category', GenreFilter::genreToSlug($genre)) }}" class="mobile-sub-link" x-on:click="mobileOpen = false">{{ $genre }}</a>
                            @endforeach
                        @elseif ($key === 'year')
                            @foreach ($years as $y)
                                <a href="{{ route('year', $y) }}" class="mobile-sub-link" x-on:click="mobileOpen = false">{{ $y }}</a>
                            @endforeach
                        @elseif ($key === 'language')
                            @foreach (Languages::LIST as $lang)
                                <a href="{{ route('language', $lang['code']) }}" class="mobile-sub-link" x-on:click="mobileOpen = false">{{ $lang['label'] }}</a>
                            @endforeach
                        @elseif ($key === 'country')
                            @foreach (Countries::LIST as $country)
                                <a href="{{ route('country', $country['code']) }}" class="mobile-sub-link" x-on:click="mobileOpen = false">{{ $country['label'] }}</a>
                            @endforeach
                        @else
                            <a href="{{ route('most-viewed') }}" class="mobile-sub-link" x-on:click="mobileOpen = false">Most Viewed</a>
                            <a href="{{ route('top-tv') }}" class="mobile-sub-link" x-on:click="mobileOpen = false">Top TV</a>
                            <a href="{{ route('people') }}" class="mobile-sub-link" x-on:click="mobileOpen = false">People</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</header>
