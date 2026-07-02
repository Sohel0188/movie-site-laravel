<?php

namespace App\Services\StreamVault;

class StatsService
{
    private const FALLBACK = [
        'content_library' => [
            'movies' => 91735,
            'tv_shows' => 19810,
            'episodes' => 478000,
            'people' => 308139,
            'collections' => 0,
        ],
        'cached' => false,
    ];

    public function __construct(
        private VidApiService $vidApi,
        private StreamVaultCache $cache,
    ) {}

    public function fetch(): array
    {
        $cacheKey = 'stats:library';
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $data = $this->vidApi->fetchStats();
            $data['generated_at'] = now()->toIso8601String();
            $this->cache->set($cacheKey, $data, $this->cache->ttl('stats'));

            return $data;
        } catch (\Throwable) {
            $fallback = array_merge(self::FALLBACK, [
                'generated_at' => now()->toIso8601String(),
            ]);
            $this->cache->set($cacheKey, $fallback, $this->cache->ttl('stats'));

            return $fallback;
        }
    }
}
