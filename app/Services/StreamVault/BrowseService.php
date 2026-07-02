<?php

namespace App\Services\StreamVault;

use App\Support\GenreFilter;
use Illuminate\Support\Facades\Storage;

class BrowseService
{
    private const BATCH = 12;
    private const BATCH_DELAY_MS = 120000; // microseconds in usleep

    public function __construct(
        private VidApiService $vidApi,
        private StreamVaultCache $cache,
    ) {}

    public function queryBrowse(int $page, array $opts = []): array
    {
        $perPage = config('streamvault.per_page');
        $genre = $opts['genre'] ?? null;
        $year = $opts['year'] ?? null;
        $sort = $opts['sort'] ?? null;
        $type = $opts['type'] ?? 'all';

        if (! $genre && ! $year && ! $sort) {
            $mv = $this->vidApi->fetchMovies($page);
            $tv = $this->vidApi->fetchTVShows($page);
            $half = (int) ceil($perPage / 2);
            $items = array_merge(
                array_map(fn ($i) => $this->toItem($i, 'movie'), array_slice($mv['items'] ?? [], 0, $half)),
                array_map(fn ($i) => $this->toItem($i, 'tv'), array_slice($tv['items'] ?? [], 0, $perPage - $half)),
            );

            return [
                'page' => $page,
                'per_page' => $perPage,
                'total' => ($mv['total'] ?? 0) + ($tv['total'] ?? 0),
                'total_pages' => max($mv['total_pages'] ?? 1, $tv['total_pages'] ?? 1),
                'items' => $items,
            ];
        }

        $items = $this->getOrBuildFilterList($genre, $year);

        if ($type === 'movie') {
            $items = array_values(array_filter($items, fn ($i) => $i['type'] === 'movie'));
        } elseif ($type === 'tv') {
            $items = array_values(array_filter($items, fn ($i) => $i['type'] === 'tv'));
        }

        if ($sort === 'popular') {
            $items = GenreFilter::sortByPopularity($items);
        } elseif ($sort === 'rating') {
            $items = GenreFilter::sortByRating($items);
        }

        $total = count($items);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $start = ($page - 1) * $perPage;

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'items' => array_slice($items, $start, $perPage),
        ];
    }

    private function toItem(array $item, string $type): array
    {
        return array_merge($item, ['type' => $type]);
    }

    private function cacheKey(?string $genre, ?string $year): string
    {
        if ($genre) {
            return 'genre-'.GenreFilter::genreToSlug($genre);
        }
        if ($year) {
            return "year-{$year}";
        }

        return 'all';
    }

    private function passesFilter(array $item, ?string $genre, ?string $year): bool
    {
        if ($genre && ! GenreFilter::matchesGenre($item['genre'] ?? '', $genre)) {
            return false;
        }
        if ($year && ! GenreFilter::matchesYear($item['year'] ?? '', $year)) {
            return false;
        }

        return true;
    }

    private function getOrBuildFilterList(?string $genre, ?string $year): array
    {
        $key = $this->cacheKey($genre, $year);
        $shared = $this->cache->get("vidapi:filter:{$key}");
        if (($shared['complete'] ?? false) && ! empty($shared['items'])) {
            return $shared['items'];
        }

        $disk = $this->loadDiskCache($key);
        if (($disk['complete'] ?? false) && ! empty($disk['items'])) {
            return $disk['items'];
        }

        $items = $disk['items'] ?? [];
        $built = $this->scanPages($genre, $year);
        $this->saveCache($key, [
            'builtAt' => now()->toIso8601String(),
            'complete' => true,
            'items' => $built,
        ]);

        return $built;
    }

    private function scanPages(?string $genre, ?string $year): array
    {
        $items = [];
        $mv1 = $this->vidApi->fetchMovies(1);
        $tv1 = $this->vidApi->fetchTVShows(1);

        foreach ($mv1['items'] ?? [] as $item) {
            if ($this->passesFilter($item, $genre, $year)) {
                $items[] = $this->toItem($item, 'movie');
            }
        }
        foreach ($tv1['items'] ?? [] as $item) {
            if ($this->passesFilter($item, $genre, $year)) {
                $items[] = $this->toItem($item, 'tv');
            }
        }

        $maxPages = max($mv1['total_pages'] ?? 1, $tv1['total_pages'] ?? 1);

        for ($start = 2; $start <= $maxPages; $start += self::BATCH) {
            $pages = range($start, min($start + self::BATCH - 1, $maxPages));
            foreach ($pages as $p) {
                try {
                    if ($p <= ($mv1['total_pages'] ?? 0)) {
                        $mv = $this->vidApi->fetchMovies($p);
                        foreach ($mv['items'] ?? [] as $item) {
                            if ($this->passesFilter($item, $genre, $year)) {
                                $items[] = $this->toItem($item, 'movie');
                            }
                        }
                    }
                    if ($p <= ($tv1['total_pages'] ?? 0)) {
                        $tv = $this->vidApi->fetchTVShows($p);
                        foreach ($tv['items'] ?? [] as $item) {
                            if ($this->passesFilter($item, $genre, $year)) {
                                $items[] = $this->toItem($item, 'tv');
                            }
                        }
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
            usleep(self::BATCH_DELAY_MS);
        }

        return $items;
    }

    private function loadDiskCache(string $key): ?array
    {
        $path = "streamvault/filters/{$key}.json";
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        return json_decode(Storage::disk('local')->get($path), true);
    }

    private function saveCache(string $key, array $data): void
    {
        Storage::disk('local')->put(
            "streamvault/filters/{$key}.json",
            json_encode($data),
        );
        $this->cache->set("vidapi:filter:{$key}", $data, $this->cache->ttl('stats'));
    }
}
