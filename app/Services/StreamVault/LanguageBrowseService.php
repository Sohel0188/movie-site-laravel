<?php

namespace App\Services\StreamVault;

class LanguageBrowseService
{
    public function __construct(
        private TmdbService $tmdb,
        private VidApiService $vidApi,
        private TmdbPaginationService $pagination,
    ) {}

    public function query(string $langCode, int $page, string $mediaType = 'all'): array
    {
        if (! $this->tmdb->hasCredentials()) {
            throw new \RuntimeException('TMDB credentials are not configured');
        }

        $code = strtolower($langCode);
        $perPage = config('streamvault.per_page');

        if ($mediaType === 'movie') {
            $result = $this->pagination->paginateDiscover(
                $page,
                $perPage,
                fn ($p) => $this->tmdb->fetch("/discover/movie?with_original_language={$code}&sort_by=popularity.desc&page={$p}"),
                fn ($r) => $this->mapItem($r, 'movie'),
            );

            return array_merge($result, ['language_code' => $code]);
        }

        if ($mediaType === 'tv') {
            $result = $this->pagination->paginateDiscover(
                $page,
                $perPage,
                fn ($p) => $this->tmdb->fetch("/discover/tv?with_original_language={$code}&sort_by=popularity.desc&page={$p}"),
                fn ($r) => $this->mapItem($r, 'tv'),
            );

            return array_merge($result, ['language_code' => $code]);
        }

        $movieMeta = $this->tmdb->fetch("/discover/movie?with_original_language={$code}&sort_by=popularity.desc&page=1");
        $tvMeta = $this->tmdb->fetch("/discover/tv?with_original_language={$code}&sort_by=popularity.desc&page=1");

        $result = $this->pagination->paginateMerged(
            $page,
            $perPage,
            function ($p) use ($code) {
                try {
                    $movies = $this->tmdb->fetch("/discover/movie?with_original_language={$code}&sort_by=popularity.desc&page={$p}");
                } catch (\Throwable) {
                    $movies = ['results' => []];
                }
                try {
                    $tv = $this->tmdb->fetch("/discover/tv?with_original_language={$code}&sort_by=popularity.desc&page={$p}");
                } catch (\Throwable) {
                    $tv = ['results' => []];
                }
                $items = array_merge(
                    array_map(fn ($r) => $this->mapItem($r, 'movie'), $movies['results'] ?? []),
                    array_map(fn ($r) => $this->mapItem($r, 'tv'), $tv['results'] ?? []),
                );
                usort($items, fn ($a, $b) => (float) $b['popularity'] <=> (float) $a['popularity']);

                return $items;
            },
            ($movieMeta['total_results'] ?? 0) + ($tvMeta['total_results'] ?? 0),
            max($movieMeta['total_pages'] ?? 1, $tvMeta['total_pages'] ?? 1),
        );

        return array_merge($result, ['language_code' => $code]);
    }

    private function mapItem(array $item, string $type): array
    {
        $tmdbId = (string) $item['id'];

        return [
            'tmdb_id' => $tmdbId,
            'imdb_id' => '',
            'title' => $item['title'] ?? $item['name'] ?? 'Untitled',
            'year' => substr($item['release_date'] ?? $item['first_air_date'] ?? '', 0, 4),
            'poster_url' => $this->tmdb->posterUrl($item['poster_path'] ?? null) ?? '',
            'rating' => isset($item['vote_average']) ? number_format($item['vote_average'], 1) : '',
            'genre' => '',
            'popularity' => (string) ($item['popularity'] ?? 0),
            'type' => $type,
            'embed_url' => $type === 'movie'
                ? $this->vidApi->movieEmbedUrl($tmdbId)
                : $this->vidApi->tvEmbedUrl($tmdbId, 1, 1),
        ];
    }
}
