<?php

namespace App\Http\Controllers;

use App\Services\StreamVault\MediaDetailService;
use App\Services\StreamVault\StreamVaultCache;
use App\Services\StreamVault\VidApiService;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function __construct(
        private MediaDetailService $mediaDetails,
        private VidApiService $vidApi,
        private StreamVaultCache $cache,
    ) {}

    public function showMovie(string $id): View
    {
        return $this->show('movie', $id);
    }

    public function showTv(string $id): View
    {
        return $this->show('tv', $id);
    }

    private function show(string $type, string $id): View
    {
        $cacheKey = "media:v2:{$type}:{$id}";
        $detail = $this->cache->get($cacheKey);

        if (! $detail) {
            try {
                $detail = $this->mediaDetails->fetch($type, $id);
                $this->cache->set($cacheKey, $detail, $this->cache->ttl('tmdb'));
            } catch (\Throwable $e) {
                return view('media.not-found', ['error' => $e->getMessage()]);
            }
        }

        $playable = $this->mediaDetails->toPlayable($detail);
        $embedUrl = $type === 'movie'
            ? $this->vidApi->movieEmbedUrl($detail['imdb_id'] ?: $detail['tmdb_id'], ['autoplay' => true])
            : $this->vidApi->tvEmbedUrl($detail['imdb_id'] ?: $detail['tmdb_id'], 1, 1, ['autoplay' => true]);

        return view('media.show', [
            'detail' => $detail,
            'playable' => $playable,
            'embedUrl' => $embedUrl,
            'playerType' => $type === 'tv' ? 'tv' : 'movies',
        ]);
    }
}
