<?php

namespace App\Services\StreamVault;

use Illuminate\Support\Facades\Http;

class VidApiService
{
    public function movieEmbedUrl(string $id, array $opts = []): string
    {
        $params = ['primaryColor' => config('streamvault.player_color')];
        if (! empty($opts['autoplay'])) {
            $params['autoplay'] = '1';
        }
        if (! empty($opts['resumeAt'])) {
            $params['resumeAt'] = (string) $opts['resumeAt'];
        }

        return config('streamvault.embed_base').'/movie/'.$id.'?'.http_build_query($params);
    }

    public function tvEmbedUrl(string $id, int $season, int $episode, array $opts = []): string
    {
        $params = ['primaryColor' => config('streamvault.player_color')];
        if (! empty($opts['autoplay'])) {
            $params['autoplay'] = '1';
        }
        if (! empty($opts['resumeAt'])) {
            $params['resumeAt'] = (string) $opts['resumeAt'];
        }

        return config('streamvault.embed_base')."/tv/{$id}/{$season}/{$episode}?".http_build_query($params);
    }

    public function fetchMovies(int $page): array
    {
        return $this->apiFetch("/movies/latest/page-{$page}.json");
    }

    public function fetchTVShows(int $page): array
    {
        return $this->apiFetch("/tvshows/latest/page-{$page}.json");
    }

    public function fetchEpisodes(int $page): array
    {
        return $this->apiFetch("/episodes/latest/page-{$page}.json");
    }

    public function fetchStats(): array
    {
        return $this->apiFetch('/imdb/api/?action=stats', 2500);
    }

    private function apiFetch(string $path, int $timeoutMs = 5000): array
    {
        $response = Http::timeout((int) ceil($timeoutMs / 1000))
            ->get(config('streamvault.vidapi_base').$path);

        if (! $response->successful()) {
            throw new \RuntimeException("VidAPI {$response->status()}: {$path}");
        }

        return $response->json();
    }
}
