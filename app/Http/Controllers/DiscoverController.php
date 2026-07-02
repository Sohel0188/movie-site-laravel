<?php

namespace App\Http\Controllers;

use App\Services\StreamVault\BrowseService;
use App\Services\StreamVault\CatalogService;
use App\Services\StreamVault\CountryBrowseService;
use App\Services\StreamVault\KeywordBrowseService;
use App\Services\StreamVault\LanguageBrowseService;
use App\Services\StreamVault\PeopleService;
use App\Services\StreamVault\StatsService;
use App\Services\StreamVault\VidApiService;
use App\Support\Countries;
use App\Support\GenreFilter;
use App\Support\Languages;
use App\Support\StreamVaultRoutes;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscoverController extends Controller
{
    public function __construct(
        private CatalogService $catalog,
        private BrowseService $browse,
        private VidApiService $vidApi,
        private StatsService $stats,
        private CountryBrowseService $countryBrowse,
        private LanguageBrowseService $languageBrowse,
        private KeywordBrowseService $keywordBrowse,
        private PeopleService $people,
    ) {}

    public function home(Request $request): View
    {
        return $this->discover($request, [
            'title' => 'Latest Movies',
            'fetch' => fn ($page, $opts) => $this->catalog->fetchFilteredMovies($page, $opts),
            'basePath' => route('home'),
            'mediaType' => 'movies',
        ]);
    }

    public function movies(Request $request): View
    {
        $library = $this->stats->fetch();

        return $this->discover($request, [
            'title' => 'All Movies',
            'fetch' => fn ($page, $opts) => $this->catalog->fetchFilteredMovies($page, $opts),
            'basePath' => route('movies'),
            'mediaType' => 'movies',
            'filterable' => true,
            'toolbarIcon' => 'movie',
            'toolbarMediaType' => 'movie',
            'libraryCount' => $library['content_library']['movies'] ?? 0,
        ]);
    }

    public function tvShows(Request $request): View
    {
        $library = $this->stats->fetch();

        return $this->discover($request, [
            'title' => 'All TV Shows',
            'fetch' => fn ($page, $opts) => $this->catalog->fetchFilteredTVShows($page, $opts),
            'basePath' => route('tv-shows'),
            'mediaType' => 'tv',
            'filterable' => true,
            'toolbarIcon' => 'tv',
            'toolbarMediaType' => 'tv',
            'libraryCount' => $library['content_library']['tv_shows'] ?? 0,
        ]);
    }

    public function episodes(Request $request): View
    {
        return $this->discover($request, [
            'title' => 'Latest Episodes',
            'fetch' => fn ($page) => $this->vidApi->fetchEpisodes($page),
            'basePath' => route('episodes'),
            'mediaType' => 'episodes',
        ]);
    }

    public function browse(Request $request): View
    {
        return $this->discover($request, [
            'title' => 'Browse All',
            'fetch' => fn ($page, $opts) => $this->browse->queryBrowse($page, $opts),
            'basePath' => route('browse'),
            'mediaType' => 'browse',
            'typedBrowse' => true,
        ]);
    }

    public function mostViewed(Request $request): View
    {
        return $this->discover($request, [
            'title' => 'Most Viewed',
            'fetch' => fn ($page, $opts) => $this->catalog->fetchFilteredMovies($page, array_merge($opts, ['sort' => 'popular'])),
            'basePath' => route('most-viewed'),
            'mediaType' => 'movies',
            'fixedSort' => 'popular',
        ]);
    }

    public function topTv(Request $request): View
    {
        return $this->discover($request, [
            'title' => 'Top TV',
            'fetch' => fn ($page, $opts) => $this->catalog->fetchFilteredTVShows($page, array_merge($opts, ['sort' => 'rating'])),
            'basePath' => route('top-tv'),
            'mediaType' => 'tv',
            'fixedSort' => 'rating',
        ]);
    }

    public function category(Request $request, string $slug): View
    {
        $genre = GenreFilter::slugToGenre($slug);
        if (! $genre) {
            return view('discover.not-found', [
                'message' => 'Category not found.',
                'link' => route('browse'),
                'linkText' => 'Browse all →',
            ]);
        }

        return $this->discover($request, [
            'title' => $genre,
            'fetch' => fn ($page, $opts) => $this->browse->queryBrowse($page, array_merge($opts, ['genre' => $genre])),
            'basePath' => route('category', $slug),
            'mediaType' => 'browse',
            'typedBrowse' => true,
            'fixedGenre' => $genre,
        ]);
    }

    public function year(Request $request, string $year): View
    {
        return $this->discover($request, [
            'title' => $year,
            'fetch' => fn ($page, $opts) => $this->browse->queryBrowse($page, array_merge($opts, ['year' => $year])),
            'basePath' => route('year', $year),
            'mediaType' => 'browse',
            'typedBrowse' => true,
            'fixedYear' => $year,
        ]);
    }

    public function country(Request $request, string $code): View
    {
        if (! Countries::get($code)) {
            return view('discover.not-found', [
                'message' => 'Country not found.',
                'link' => route('browse'),
                'linkText' => 'Browse all →',
            ]);
        }

        return $this->discover($request, [
            'title' => Countries::label($code),
            'subtitle' => strtoupper($code),
            'fetch' => fn ($page) => $this->countryBrowse->query($code, $page),
            'basePath' => route('country', $code),
            'mediaType' => 'browse',
            'typedBrowse' => true,
        ]);
    }

    public function language(Request $request, string $code): View
    {
        if (! Languages::get($code)) {
            return view('discover.not-found', [
                'message' => 'Language not found.',
                'link' => route('browse'),
                'linkText' => 'Browse all →',
            ]);
        }

        return $this->discover($request, [
            'title' => Languages::label($code),
            'subtitle' => strtoupper($code),
            'fetch' => fn ($page) => $this->languageBrowse->query($code, $page),
            'basePath' => route('language', $code),
            'mediaType' => 'browse',
            'typedBrowse' => true,
        ]);
    }

    public function keyword(Request $request, string $id): View
    {
        $page = max(1, (int) $request->query('page', 1));
        $error = null;
        $data = ['items' => [], 'total' => 0, 'total_pages' => 1];
        $title = 'Keyword';

        try {
            $data = $this->keywordBrowse->query($id, $page);
            $title = $data['keyword_name'] ?? $title;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $items = array_map(fn ($item) => array_merge($item, ['_type' => ($item['type'] ?? '') === 'tv' ? 'tv' : 'movies']), $data['items'] ?? []);

        return view('discover.index', [
            'title' => $title,
            'subtitle' => null,
            'items' => $items,
            'mediaType' => 'browse',
            'page' => $page,
            'totalPages' => $data['total_pages'] ?? 1,
            'total' => $data['total'] ?? 0,
            'error' => $error,
            'basePath' => route('keyword', $id),
            'genre' => null,
            'year' => null,
            'sort' => null,
            'filterable' => false,
            'toolbarIcon' => null,
            'toolbarMediaType' => null,
            'libraryCount' => 0,
            'browseUrl' => fn (array $q = []) => \App\Support\StreamVaultRoutes::browseUrl(route('keyword', $id), $q),
        ]);
    }

    public function people(Request $request): View
    {
        $page = max(1, (int) $request->query('page', 1));

        try {
            $data = $this->people->query($page);
        } catch (\Throwable $e) {
            $data = [
                'page' => $page,
                'per_page' => config('streamvault.people_per_page'),
                'total' => 0,
                'total_pages' => 1,
                'items' => [],
                'error' => $e->getMessage(),
            ];
        }

        return view('people.index', [
            'people' => $data['items'] ?? [],
            'page' => $data['page'],
            'totalPages' => $data['total_pages'],
            'total' => $data['total'],
            'libraryTotal' => $data['library_total'] ?? 0,
            'error' => $data['error'] ?? null,
            'basePath' => route('people'),
        ]);
    }

    private function discover(Request $request, array $config): View
    {
        $filterable = $config['filterable'] ?? false;
        $page = max(1, (int) $request->query('page', 1));
        $genre = $config['fixedGenre'] ?? ($filterable ? $request->query('genre') : ($request->query('genre') ?: null));
        $year = $config['fixedYear'] ?? ($filterable ? $request->query('year') : ($request->query('year') ?: null));
        $sort = $config['fixedSort'] ?? ($filterable ? $request->query('sort') : ($request->query('sort') ?: null));

        $opts = array_filter([
            'genre' => $genre,
            'year' => $year,
            'sort' => $sort,
        ]);

        $error = null;
        $data = ['items' => [], 'total' => 0, 'total_pages' => 1, 'page' => $page];

        try {
            $data = ($config['fetch'])($page, $opts);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $items = $data['items'] ?? [];
        $typedBrowse = $config['typedBrowse'] ?? false;
        $mediaType = $config['mediaType'];

        $items = array_map(function ($item) use ($typedBrowse, $mediaType) {
            if ($typedBrowse) {
                $item['_type'] = ($item['type'] ?? '') === 'tv' ? 'tv' : 'movies';
            } else {
                $item['_type'] = $mediaType;
            }

            return $item;
        }, $items);

        $title = $config['title'];
        if (! empty($config['dynamicTitle']) && ! empty($data['keyword_name'])) {
            $title = $data['keyword_name'];
        }

        if ($filterable) {
            $parts = array_filter([$genre, $year]);
            if ($parts) {
                $title = implode(' · ', $parts);
            }
        }

        return view('discover.index', [
            'title' => $title,
            'subtitle' => $config['subtitle'] ?? null,
            'items' => $items,
            'mediaType' => $mediaType,
            'page' => $page,
            'totalPages' => $data['total_pages'] ?? 1,
            'total' => $data['total'] ?? 0,
            'error' => $error,
            'basePath' => $config['basePath'],
            'genre' => $genre,
            'year' => $year,
            'sort' => $sort,
            'filterable' => $filterable,
            'toolbarIcon' => $config['toolbarIcon'] ?? null,
            'toolbarMediaType' => $config['toolbarMediaType'] ?? null,
            'libraryCount' => $config['libraryCount'] ?? 0,
            'browseUrl' => fn (array $q = []) => StreamVaultRoutes::browseUrl($config['basePath'], array_merge([
                'genre' => $genre,
                'year' => $year,
                'sort' => $sort,
            ], $q)),
        ]);
    }
}
