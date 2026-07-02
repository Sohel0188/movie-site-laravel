<?php

namespace App\Services\StreamVault;

class SearchService
{
    public function __construct(
        private TmdbService $tmdb,
        private PlayableService $playable,
    ) {}

    public function query(string $q): array
    {
        if (! $this->tmdb->hasCredentials()) {
            throw new \RuntimeException('TMDB credentials are not configured');
        }

        return $this->playable->fillPlayablePage(1, config('streamvault.per_page'), function ($page) use ($q) {
            return $this->fetchSearchPage($q, $page);
        });
    }

    private function fetchSearchPage(string $query, int $page): array
    {
        $encoded = urlencode(trim($query));
        $data = $this->tmdb->fetch("/search/multi?query={$encoded}&page={$page}&include_adult=false");
        $items = [];

        foreach ($data['results'] ?? [] as $hit) {
            if (! in_array($hit['media_type'] ?? '', ['movie', 'tv'], true)) {
                continue;
            }
            $type = $hit['media_type'];
            $items[] = [
                'tmdb_id' => (string) $hit['id'],
                'imdb_id' => '',
                'title' => $hit['title'] ?? $hit['name'] ?? 'Untitled',
                'year' => substr($hit['release_date'] ?? $hit['first_air_date'] ?? '', 0, 4),
                'poster_url' => $this->tmdb->posterUrl($hit['poster_path'] ?? null) ?? '',
                'rating' => isset($hit['vote_average']) ? number_format($hit['vote_average'], 1) : '',
                'genre' => '',
                'popularity' => '0',
                'type' => $type,
                '_type' => $type === 'tv' ? 'tv' : 'movies',
            ];
        }

        return $items;
    }
}
