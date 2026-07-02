<?php

namespace App\Services\StreamVault;

use Illuminate\Support\Facades\Storage;

class PlayableService
{
    private const BATCH = 16;
    private const PAGES_PER_TICK = 24;
    private const DEFAULT_MAX_TMDB_PAGES = 500;

    private ?array $memoryState = null;

    public function __construct(
        private VidApiService $vidApi,
        private StreamVaultCache $cache,
    ) {}

    public function fillPlayablePage(int $userPage, int $perPage, callable $fetchPage, int $maxTmdbPages = 80): array
    {
        $state = $this->getPlayableIndex();
        if (empty($state['tmdb'])) {
            return [];
        }

        $skip = max(0, ($userPage - 1) * $perPage);
        $results = [];
        $skipped = 0;
        $tmdbPage = 1;

        while (count($results) < $perPage && $tmdbPage <= $maxTmdbPages) {
            $batch = $fetchPage($tmdbPage);
            if (empty($batch)) {
                break;
            }
            foreach ($batch as $item) {
                if (! $this->isPlayableInState($state, $item['tmdb_id'] ?? null, $item['imdb_id'] ?? null)) {
                    continue;
                }
                if ($skipped < $skip) {
                    $skipped++;
                    continue;
                }
                $results[] = $item;
                if (count($results) >= $perPage) {
                    break;
                }
            }
            $tmdbPage++;
        }

        return $results;
    }

    public function getPlayableIndex(): array
    {
        if ($this->memoryState) {
            return $this->memoryState;
        }

        $publicPath = public_path('playable-index.json');
        if (file_exists($publicPath)) {
            $data = json_decode(file_get_contents($publicPath), true);
            if (! empty($data['tmdbIds'])) {
                $this->memoryState = $this->stateFromSnapshot($data);

                return $this->memoryState;
            }
        }

        $diskPath = 'streamvault/playable-index.json';
        if (Storage::disk('local')->exists($diskPath)) {
            $data = json_decode(Storage::disk('local')->get($diskPath), true);
            if (! empty($data['tmdbIds'])) {
                $this->memoryState = $this->stateFromSnapshot($data);

                return $this->memoryState;
            }
        }

        $cached = $this->cache->get('playable:index');
        if (! empty($cached['tmdbIds'])) {
            $this->memoryState = $this->stateFromSnapshot($cached);

            return $this->memoryState;
        }

        $this->memoryState = [
            'tmdb' => [],
            'imdb' => [],
            'complete' => false,
            'moviePagesDone' => 0,
            'tvPagesDone' => 0,
            'movieTotalPages' => 0,
            'tvTotalPages' => 0,
        ];

        if (count($this->memoryState['tmdb']) < 3000) {
            $this->ensureIndexProgress();
        }

        return $this->memoryState;
    }

    private function isPlayableInState(array $state, ?string $tmdbId, ?string $imdbId): bool
    {
        if ($imdbId && in_array($imdbId, $state['imdb'], true)) {
            return true;
        }
        if ($tmdbId && in_array($tmdbId, $state['tmdb'], true)) {
            return true;
        }

        return false;
    }

    private function ensureIndexProgress(): void
    {
        $state = &$this->memoryState;
        if ($state['complete']) {
            return;
        }

        if ($state['movieTotalPages'] === 0 || $state['tvTotalPages'] === 0) {
            $mv1 = $this->vidApi->fetchMovies(1);
            $tv1 = $this->vidApi->fetchTVShows(1);
            $state['movieTotalPages'] = $mv1['total_pages'] ?? 0;
            $state['tvTotalPages'] = $tv1['total_pages'] ?? 0;
            if ($state['moviePagesDone'] === 0) {
                foreach ($mv1['items'] ?? [] as $item) {
                    $this->addMediaItem($state, $item);
                }
                $state['moviePagesDone'] = 1;
            }
            if ($state['tvPagesDone'] === 0) {
                foreach ($tv1['items'] ?? [] as $item) {
                    $this->addMediaItem($state, $item);
                }
                $state['tvPagesDone'] = 1;
            }
        }

        $pagesLeft = self::PAGES_PER_TICK;
        while ($pagesLeft > 0 && ! $state['complete']) {
            $movieStart = $state['moviePagesDone'] + 1;
            $tvStart = $state['tvPagesDone'] + 1;
            $movieRemaining = $state['movieTotalPages'] - $state['moviePagesDone'];
            $tvRemaining = $state['tvTotalPages'] - $state['tvPagesDone'];

            if ($movieRemaining <= 0 && $tvRemaining <= 0) {
                $state['complete'] = true;
                break;
            }

            $batchSize = min(self::BATCH, $pagesLeft, max($movieRemaining, $tvRemaining, 1));
            for ($offset = 0; $offset < $batchSize; $offset++) {
                $mp = $movieStart + $offset;
                $tp = $tvStart + $offset;
                try {
                    if ($mp <= $state['movieTotalPages']) {
                        $mv = $this->vidApi->fetchMovies($mp);
                        foreach ($mv['items'] ?? [] as $item) {
                            $this->addMediaItem($state, $item);
                        }
                        $state['moviePagesDone'] = max($state['moviePagesDone'], $mp);
                    }
                    if ($tp <= $state['tvTotalPages']) {
                        $tv = $this->vidApi->fetchTVShows($tp);
                        foreach ($tv['items'] ?? [] as $item) {
                            $this->addMediaItem($state, $item);
                        }
                        $state['tvPagesDone'] = max($state['tvPagesDone'], $tp);
                    }
                } catch (\Throwable) {
                    continue;
                }
            }

            $state['complete'] = $state['moviePagesDone'] >= $state['movieTotalPages']
                && $state['tvPagesDone'] >= $state['tvTotalPages'];
            $pagesLeft -= $batchSize;
            usleep(80000);
        }

        $this->persistState($state);
    }

    private function addMediaItem(array &$state, array $item): void
    {
        if (! empty($item['tmdb_id']) && ! in_array($item['tmdb_id'], $state['tmdb'], true)) {
            $state['tmdb'][] = $item['tmdb_id'];
        }
        if (! empty($item['imdb_id']) && ! in_array($item['imdb_id'], $state['imdb'], true)) {
            $state['imdb'][] = $item['imdb_id'];
        }
    }

    private function stateFromSnapshot(array $data): array
    {
        return [
            'tmdb' => $data['tmdbIds'] ?? [],
            'imdb' => $data['imdbIds'] ?? [],
            'complete' => $data['complete'] ?? false,
            'moviePagesDone' => $data['moviePagesDone'] ?? 0,
            'tvPagesDone' => $data['tvPagesDone'] ?? 0,
            'movieTotalPages' => $data['movieTotalPages'] ?? 0,
            'tvTotalPages' => $data['tvTotalPages'] ?? 0,
        ];
    }

    private function persistState(array $state): void
    {
        $snapshot = [
            'builtAt' => now()->toIso8601String(),
            'complete' => $state['complete'],
            'moviePagesDone' => $state['moviePagesDone'],
            'tvPagesDone' => $state['tvPagesDone'],
            'movieTotalPages' => $state['movieTotalPages'],
            'tvTotalPages' => $state['tvTotalPages'],
            'tmdbIds' => $state['tmdb'],
            'imdbIds' => $state['imdb'],
        ];
        Storage::disk('local')->put('streamvault/playable-index.json', json_encode($snapshot));
        $this->cache->set('playable:index', $snapshot, $this->cache->ttl('stats'));
    }
}
