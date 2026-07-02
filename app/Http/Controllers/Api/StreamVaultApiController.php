<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SearchController;
use App\Services\StreamVault\BrowseService;
use App\Services\StreamVault\CatalogService;
use App\Services\StreamVault\CountryBrowseService;
use App\Services\StreamVault\KeywordBrowseService;
use App\Services\StreamVault\LanguageBrowseService;
use App\Services\StreamVault\MediaDetailService;
use App\Services\StreamVault\PeopleService;
use App\Services\StreamVault\SearchService;
use App\Services\StreamVault\StatsService;
use App\Services\StreamVault\StreamVaultCache;
use App\Services\StreamVault\TmdbService;
use App\Services\StreamVault\VidApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreamVaultApiController extends Controller
{
    public function __construct(
        private CatalogService $catalog,
        private BrowseService $browse,
        private VidApiService $vidApi,
        private StatsService $stats,
        private SearchService $search,
        private MediaDetailService $mediaDetails,
        private PeopleService $people,
        private CountryBrowseService $countryBrowse,
        private LanguageBrowseService $languageBrowse,
        private KeywordBrowseService $keywordBrowse,
        private TmdbService $tmdb,
        private StreamVaultCache $cache,
    ) {}

    public function movies(Request $request): JsonResponse
    {
        return $this->cachedPaginated('movies:v4', $request, fn ($page, $opts) => $this->catalog->fetchFilteredMovies($page, $opts));
    }

    public function tvShows(Request $request): JsonResponse
    {
        return $this->cachedPaginated('tvshows:v4', $request, fn ($page, $opts) => $this->catalog->fetchFilteredTVShows($page, $opts));
    }

    public function episodes(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $cacheKey = "episodes:v4:page:{$page}";

        return $this->cached($cacheKey, 'page', fn () => $this->vidApi->fetchEpisodes($page));
    }

    public function browse(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $genre = $request->query('genre');
        $year = $request->query('year');
        $sort = $request->query('sort');
        $type = $request->query('type', 'all');
        $cacheKey = "browse:v4:p:{$page}:g:{$genre}:y:{$year}:s:{$sort}:t:{$type}";

        return $this->cached($cacheKey, 'page', fn () => $this->browse->queryBrowse($page, [
            'genre' => $genre,
            'year' => $year,
            'sort' => $sort,
            'type' => $type,
        ]));
    }

    public function stats(): JsonResponse
    {
        $cached = $this->cache->get('stats:library');
        if ($cached) {
            return response()->json($cached, 200, ['X-Cache' => 'HIT']);
        }

        $data = $this->stats->fetch();

        return response()->json($data, 200, ['X-Cache' => 'MISS']);
    }

    public function search(Request $request): JsonResponse
    {
        return app(SearchController::class)->api($request);
    }

    public function media(string $type, string $id): JsonResponse
    {
        $mediaType = $type === 'tv' ? 'tv' : 'movie';
        if (! $this->tmdb->hasCredentials()) {
            return response()->json(['error' => 'TMDB credentials are not configured'], 503);
        }

        $cacheKey = "media:v2:{$mediaType}:{$id}";
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return response()->json($cached, 200, ['X-Cache' => 'HIT']);
        }

        try {
            $detail = $this->mediaDetails->fetch($mediaType, $id);
            $this->cache->set($cacheKey, $detail, $this->cache->ttl('tmdb'));

            return response()->json($detail, 200, ['X-Cache' => 'MISS']);
        } catch (\Throwable) {
            return response()->json(['error' => 'Media not found'], 404);
        }
    }

    public function people(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $cacheKey = "people:v1:page:{$page}";

        return $this->cached($cacheKey, 'page', fn () => $this->people->query($page));
    }

    public function person(string $id): JsonResponse
    {
        $cacheKey = "person:v1:{$id}";

        return $this->cached($cacheKey, 'tmdb', function () use ($id) {
            if (! $this->tmdb->hasCredentials()) {
                throw new \RuntimeException('TMDB not configured');
            }

            return $this->tmdb->fetchPersonWithFilmography($id, $this->vidApi);
        });
    }

    public function country(string $code, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $type = $request->query('type', 'all');
        $cacheKey = "country:v1:{$code}:p:{$page}:t:{$type}";

        return $this->cached($cacheKey, 'tmdb', fn () => $this->countryBrowse->query($code, $page, $type));
    }

    public function language(string $code, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $type = $request->query('type', 'all');
        $cacheKey = "language:v1:{$code}:p:{$page}:t:{$type}";

        return $this->cached($cacheKey, 'tmdb', fn () => $this->languageBrowse->query($code, $page, $type));
    }

    public function keyword(string $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $cacheKey = "keyword:v1:{$id}:p:{$page}";

        return $this->cached($cacheKey, 'tmdb', fn () => $this->keywordBrowse->query($id, $page));
    }

    private function cachedPaginated(string $prefix, Request $request, callable $fetch): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $genre = $request->query('genre', '');
        $year = $request->query('year', '');
        $sort = $request->query('sort', '');
        $cacheKey = "{$prefix}:page:{$page}:g:{$genre}:y:{$year}:s:{$sort}";

        return $this->cached($cacheKey, 'page', fn () => $fetch($page, array_filter([
            'genre' => $genre ?: null,
            'year' => $year ?: null,
            'sort' => $sort ?: null,
        ])));
    }

    private function cached(string $cacheKey, string $ttlType, callable $fetch): JsonResponse
    {
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return response()->json($cached, 200, ['X-Cache' => 'HIT']);
        }

        try {
            $data = $fetch();
            $this->cache->set($cacheKey, $data, $this->cache->ttl($ttlType));

            return response()->json($data, 200, ['X-Cache' => 'MISS']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }
}
