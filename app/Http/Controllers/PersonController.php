<?php

namespace App\Http\Controllers;

use App\Services\StreamVault\StreamVaultCache;
use App\Services\StreamVault\TmdbService;
use App\Services\StreamVault\VidApiService;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function __construct(
        private TmdbService $tmdb,
        private VidApiService $vidApi,
        private StreamVaultCache $cache,
    ) {}

    public function show(string $id): View
    {
        $cacheKey = "person:v1:{$id}";
        $person = $this->cache->get($cacheKey);

        if (! $person) {
            try {
                $person = $this->tmdb->fetchPersonWithFilmography($id, $this->vidApi);
                $this->cache->set($cacheKey, $person, $this->cache->ttl('tmdb'));
            } catch (\Throwable $e) {
                return view('person.not-found', ['error' => $e->getMessage()]);
            }
        }

        return view('person.show', ['person' => $person]);
    }
}
