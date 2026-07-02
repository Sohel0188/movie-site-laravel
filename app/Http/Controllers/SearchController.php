<?php

namespace App\Http\Controllers;

use App\Services\StreamVault\SearchService;
use App\Services\StreamVault\StreamVaultCache;
use App\Services\StreamVault\TmdbService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $search,
        private TmdbService $tmdb,
        private StreamVaultCache $cache,
    ) {}

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $results = [];
        $error = null;

        if ($query && $this->tmdb->hasCredentials()) {
            try {
                $cacheKey = 'search:'.strtolower($query);
                $cached = $this->cache->get($cacheKey);
                if ($cached) {
                    $results = $cached;
                } else {
                    $results = $this->search->query($query);
                    $this->cache->set($cacheKey, $results, $this->cache->ttl('search'));
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        } elseif ($query) {
            $error = 'TMDB credentials are not configured';
        }

        return view('search.index', [
            'query' => $query,
            'results' => $results,
            'error' => $error,
        ]);
    }

    public function api(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (! $q) {
            return response()->json(['items' => []]);
        }

        if (! $this->tmdb->hasCredentials()) {
            return response()->json(['error' => 'TMDB not configured'], 503);
        }

        $cacheKey = 'search:'.strtolower($q);
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return response()->json(['items' => $cached], 200, ['X-Cache' => 'HIT']);
        }

        try {
            $items = $this->search->query($q);
            $this->cache->set($cacheKey, $items, $this->cache->ttl('search'));

            return response()->json(['items' => $items], 200, ['X-Cache' => 'MISS']);
        } catch (\Throwable) {
            return response()->json(['error' => 'Search failed'], 502);
        }
    }
}
