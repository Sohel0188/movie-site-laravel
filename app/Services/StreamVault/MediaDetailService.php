<?php

namespace App\Services\StreamVault;

use App\Support\GenreFilter;

class MediaDetailService
{
    public function __construct(
        private TmdbService $tmdb,
        private VidApiService $vidApi,
    ) {}

    public function fetch(string $type, string $rawId): array
    {
        if (! $this->tmdb->hasCredentials()) {
            throw new \RuntimeException('TMDB credentials are not configured');
        }

        $tmdbId = $this->resolveTmdbId($type, $rawId);

        if ($type === 'movie') {
            return $this->fetchMovie($tmdbId);
        }

        return $this->fetchTv($tmdbId);
    }

    public function toPlayable(array $detail): array
    {
        $base = [
            'tmdb_id' => $detail['tmdb_id'],
            'imdb_id' => $detail['imdb_id'],
            'title' => $detail['title'],
            'year' => $detail['year'],
            'poster_url' => $detail['poster_url'] ?? '',
            'rating' => $detail['rating'],
            'genre' => implode(', ', $detail['genres'] ?? []),
            'popularity' => '0',
            'embed_url' => $detail['type'] === 'movie'
                ? $this->vidApi->movieEmbedUrl($detail['imdb_id'] ?: $detail['tmdb_id'])
                : $this->vidApi->tvEmbedUrl($detail['imdb_id'] ?: $detail['tmdb_id'], 1, 1),
        ];

        if ($detail['type'] === 'tv') {
            return array_merge($base, ['type' => 'tv', 'seasons' => 5]);
        }

        return array_merge($base, ['type' => 'movie']);
    }

    private function resolveTmdbId(string $type, string $id): string
    {
        if (ctype_digit($id)) {
            return $id;
        }

        if (str_starts_with($id, 'tt')) {
            $found = $this->tmdb->fetch("/find/{$id}?external_source=imdb_id");
            $hit = $type === 'movie'
                ? ($found['movie_results'][0] ?? null)
                : ($found['tv_results'][0] ?? null);
            if ($hit['id'] ?? null) {
                return (string) $hit['id'];
            }
        }

        return $id;
    }

    private function fetchMovie(string $tmdbId): array
    {
        $movie = $this->tmdb->fetch("/movie/{$tmdbId}?append_to_response=credits");
        $similar = $this->fetchRelated('movie', $tmdbId);
        $keywords = $this->fetchKeywords('movie', $tmdbId);

        return [
            'tmdb_id' => (string) $movie['id'],
            'imdb_id' => $movie['imdb_id'] ?? '',
            'title' => $movie['title'],
            'year' => substr($movie['release_date'] ?? '', 0, 4),
            'overview' => $movie['overview'] ?? '',
            'runtime' => $this->formatRuntime($movie['runtime'] ?? null),
            'genres' => array_column($movie['genres'] ?? [], 'name'),
            'poster_url' => $this->tmdb->posterUrl($movie['poster_path'] ?? null),
            'backdrop_url' => $this->tmdb->backdropUrl($movie['backdrop_path'] ?? null)
                ?: $this->tmdb->posterUrl($movie['poster_path'] ?? null),
            'rating' => isset($movie['vote_average']) ? number_format($movie['vote_average'], 1) : '',
            'type' => 'movie',
            'cast' => $this->mapCast($movie['credits']['cast'] ?? []),
            'keywords' => $keywords,
            'similar' => $similar,
        ];
    }

    private function fetchTv(string $tmdbId): array
    {
        $show = $this->tmdb->fetch("/tv/{$tmdbId}?append_to_response=credits,external_ids");
        $similar = $this->fetchRelated('tv', $tmdbId);
        $keywords = $this->fetchKeywords('tv', $tmdbId);
        $runtime = $show['episode_run_time'][0] ?? null;

        return [
            'tmdb_id' => (string) $show['id'],
            'imdb_id' => $show['external_ids']['imdb_id'] ?? '',
            'title' => $show['name'],
            'year' => substr($show['first_air_date'] ?? '', 0, 4),
            'overview' => $show['overview'] ?? '',
            'runtime' => $this->formatRuntime($runtime),
            'genres' => array_column($show['genres'] ?? [], 'name'),
            'poster_url' => $this->tmdb->posterUrl($show['poster_path'] ?? null),
            'backdrop_url' => $this->tmdb->backdropUrl($show['backdrop_path'] ?? null)
                ?: $this->tmdb->posterUrl($show['poster_path'] ?? null),
            'rating' => isset($show['vote_average']) ? number_format($show['vote_average'], 1) : '',
            'type' => 'tv',
            'cast' => $this->mapCast($show['credits']['cast'] ?? []),
            'keywords' => $keywords,
            'similar' => $similar,
        ];
    }

    private function mapCast(array $cast): array
    {
        return array_map(fn ($m) => [
            'tmdb_id' => (string) $m['id'],
            'name' => $m['name'],
            'profile_url' => $this->tmdb->profileUrl($m['profile_path'] ?? null),
            'role' => $m['character'] ?? $m['known_for_department'] ?? 'Acting',
        ], array_slice($cast, 0, 12));
    }

    private function fetchKeywords(string $type, string $tmdbId): array
    {
        try {
            if ($type === 'movie') {
                $data = $this->tmdb->fetch("/movie/{$tmdbId}/keywords");

                return $this->mapKeywords($data['keywords'] ?? []);
            }
            $data = $this->tmdb->fetch("/tv/{$tmdbId}/keywords");

            return $this->mapKeywords($data['results'] ?? []);
        } catch (\Throwable) {
            return [];
        }
    }

    private function mapKeywords(array $items): array
    {
        $out = [];
        foreach ($items as $k) {
            if (! empty($k['id']) && ! empty($k['name'])) {
                $out[] = ['id' => (string) $k['id'], 'name' => $k['name']];
            }
        }

        return $out;
    }

    private function fetchRelated(string $type, string $tmdbId): array
    {
        $endpoint = $type === 'movie' ? 'movie' : 'tv';
        try {
            $similar = $this->tmdb->fetch("/{$endpoint}/{$tmdbId}/similar");
        } catch (\Throwable) {
            $similar = ['results' => []];
        }
        try {
            $recommendations = $this->tmdb->fetch("/{$endpoint}/{$tmdbId}/recommendations");
        } catch (\Throwable) {
            $recommendations = ['results' => []];
        }

        $merged = array_merge($similar['results'] ?? [], $recommendations['results'] ?? []);
        $items = [];
        $seen = [];

        foreach ($merged as $item) {
            $id = (string) ($item['id'] ?? '');
            if (! $item['id'] || $id === $tmdbId || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $items[] = [
                'tmdb_id' => $id,
                'title' => $item['title'] ?? $item['name'] ?? 'Untitled',
                'year' => substr($item['release_date'] ?? $item['first_air_date'] ?? '', 0, 4),
                'backdrop_url' => $this->tmdb->backdropUrl($item['backdrop_path'] ?? null)
                    ?: $this->tmdb->posterUrl($item['poster_path'] ?? null),
                'poster_url' => $this->tmdb->posterUrl($item['poster_path'] ?? null),
                'type' => ($item['media_type'] ?? $type) === 'tv' ? 'tv' : 'movie',
            ];
            if (count($items) >= 12) {
                break;
            }
        }

        return $items;
    }

    private function formatRuntime(?int $minutes): ?string
    {
        if (! $minutes || $minutes <= 0) {
            return null;
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h === 0) {
            return "{$m}m";
        }
        if ($m === 0) {
            return "{$h}h";
        }

        return "{$h}h {$m}m";
    }
}
