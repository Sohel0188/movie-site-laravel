<?php

namespace App\Services\StreamVault;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TmdbService
{
    private const BASE = 'https://api.themoviedb.org/3';
    private const IMG = 'https://image.tmdb.org/t/p/w500';
    private const BACKDROP = 'https://image.tmdb.org/t/p/original';

    public function hasCredentials(): bool
    {
        return (bool) (config('streamvault.tmdb_access_token') || config('streamvault.tmdb_api_key'));
    }

    public function profileUrl(?string $path): ?string
    {
        return $path ? self::IMG.$path : null;
    }

    public function posterUrl(?string $path): ?string
    {
        return $path ? self::IMG.$path : null;
    }

    public function backdropUrl(?string $path): ?string
    {
        return $path ? self::BACKDROP.$path : null;
    }

    public function fetch(string $path): array
    {
        if (! $this->hasCredentials()) {
            throw new \RuntimeException('TMDB credentials are not configured');
        }

        $url = $this->withApiKey($path);
        $headers = $this->authHeaders();

        $response = Http::withHeaders($headers)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("TMDB {$response->status()}: {$path}");
        }

        return $response->json();
    }

    public function fetchCredits(string $type, string $tmdbId): array
    {
        try {
            $data = $this->fetch("/{$type}/{$tmdbId}/credits");
            $byId = [];
            foreach (array_merge($data['cast'] ?? [], $data['crew'] ?? []) as $member) {
                if (! empty($member['id']) && ! isset($byId[$member['id']])) {
                    $byId[$member['id']] = $member;
                }
            }

            return array_values($byId);
        } catch (\Throwable) {
            return [];
        }
    }

    public function fetchPopularPeople(int $page): array
    {
        $data = $this->fetch("/person/popular?page={$page}");
        $perPage = config('streamvault.people_per_page');

        $items = array_map(fn ($p) => [
            'tmdb_id' => (string) $p['id'],
            'name' => $p['name'],
            'profile_url' => $this->profileUrl($p['profile_path'] ?? null),
            'department' => $p['known_for_department'] ?? 'Acting',
            'popularity' => (string) ($p['popularity'] ?? 0),
            'known_for' => collect($p['known_for'] ?? [])
                ->take(2)
                ->map(fn ($k) => $k['title'] ?? $k['name'] ?? null)
                ->filter()
                ->implode(', '),
        ], $data['results'] ?? []);

        return [
            'page' => $data['page'],
            'per_page' => $perPage,
            'total' => $data['total_results'],
            'total_pages' => $data['total_pages'],
            'items' => $items,
        ];
    }

    public function fetchPersonDetails(string $id): array
    {
        $p = $this->fetch("/person/{$id}");

        return [
            'tmdb_id' => (string) $p['id'],
            'name' => $p['name'],
            'profile_url' => $this->profileUrl($p['profile_path'] ?? null),
            'department' => $p['known_for_department'] ?? 'Acting',
            'popularity' => (string) ($p['popularity'] ?? 0),
            'biography' => $p['biography'] ?? '',
            'birthday' => $p['birthday'] ?? null,
            'place_of_birth' => $p['place_of_birth'] ?? null,
        ];
    }

    public function fetchPersonMovieCredits(string $id, VidApiService $vidApi): array
    {
        $data = $this->fetch("/person/{$id}/movie_credits");
        $cast = $this->sortCreditsNewestFirst($data['cast'] ?? []);
        $seen = [];
        $items = [];

        foreach ($cast as $credit) {
            $key = (string) $credit['id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $tmdbId = (string) $credit['id'];
            $items[] = [
                'tmdb_id' => $tmdbId,
                'imdb_id' => '',
                'title' => $credit['title'] ?? 'Untitled',
                'year' => $this->creditYear($credit['release_date'] ?? null),
                'poster_url' => $this->posterUrl($credit['poster_path'] ?? null) ?? '',
                'rating' => isset($credit['vote_average']) ? number_format($credit['vote_average'], 1) : '',
                'genre' => '',
                'popularity' => (string) ($credit['popularity'] ?? 0),
                'type' => 'movie',
                'embed_url' => $vidApi->movieEmbedUrl($tmdbId),
                'character' => $credit['character'] ?? null,
            ];
        }

        return $items;
    }

    public function fetchPersonTvCredits(string $id, VidApiService $vidApi): array
    {
        $data = $this->fetch("/person/{$id}/tv_credits");
        $cast = $this->sortCreditsNewestFirst($data['cast'] ?? []);
        $seen = [];
        $items = [];

        foreach ($cast as $credit) {
            $key = (string) $credit['id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $tmdbId = (string) $credit['id'];
            $items[] = [
                'tmdb_id' => $tmdbId,
                'imdb_id' => '',
                'title' => $credit['name'] ?? 'Untitled',
                'year' => $this->creditYear($credit['first_air_date'] ?? null),
                'poster_url' => $this->posterUrl($credit['poster_path'] ?? null) ?? '',
                'rating' => isset($credit['vote_average']) ? number_format($credit['vote_average'], 1) : '',
                'genre' => '',
                'popularity' => (string) ($credit['popularity'] ?? 0),
                'type' => 'tv',
                'embed_url' => $vidApi->tvEmbedUrl($tmdbId, 1, 1),
                'character' => $credit['character'] ?? null,
            ];
        }

        return $items;
    }

    public function fetchPersonWithFilmography(string $id, VidApiService $vidApi): array
    {
        $person = $this->fetchPersonDetails($id);
        $movies = $this->fetchPersonMovieCredits($id, $vidApi);
        $tvShows = $this->fetchPersonTvCredits($id, $vidApi);

        return array_merge($person, [
            'movie_count' => count($movies),
            'tv_count' => count($tvShows),
            'movies' => $movies,
            'tv_shows' => $tvShows,
        ]);
    }

    private function withApiKey(string $path): string
    {
        $key = config('streamvault.tmdb_api_key');
        if (! config('streamvault.tmdb_access_token') && $key) {
            $sep = str_contains($path, '?') ? '&' : '?';

            return self::BASE.$path.$sep.'api_key='.$key;
        }

        return self::BASE.$path;
    }

    private function authHeaders(): array
    {
        $token = config('streamvault.tmdb_access_token');
        if ($token) {
            return ['Authorization' => 'Bearer '.$token];
        }

        return [];
    }

    private function creditYear(?string $date): string
    {
        if (! $date || strlen($date) < 4) {
            return '';
        }

        return substr($date, 0, 4);
    }

    private function sortCreditsNewestFirst(array $items): array
    {
        usort($items, function ($a, $b) {
            $da = $a['release_date'] ?? $a['first_air_date'] ?? '';
            $db = $b['release_date'] ?? $b['first_air_date'] ?? '';

            return strcmp($db, $da);
        });

        return $items;
    }
}
