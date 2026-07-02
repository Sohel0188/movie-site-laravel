<?php

namespace App\Services\StreamVault;

class KeywordBrowseService
{
    public function __construct(
        private TmdbService $tmdb,
        private TmdbPaginationService $pagination,
    ) {}

    public function query(string $id, int $page): array
    {
        if (! $this->tmdb->hasCredentials()) {
            throw new \RuntimeException('TMDB credentials are not configured');
        }

        $perPage = config('streamvault.per_page');
        $keyword = $this->tmdb->fetch("/keyword/{$id}");
        $movieMeta = $this->keywordMovies($id, 1);
        $tvMeta = $this->keywordTv($id, 1);

        $result = $this->pagination->paginateMerged(
            $page,
            $perPage,
            function ($p) use ($id) {
                $movies = $this->keywordMovies($id, $p);
                $tv = $this->keywordTv($id, $p);

                return array_merge(
                    array_map(fn ($r) => $this->mapResult($r, 'movie'), $movies['results'] ?? []),
                    array_map(fn ($r) => $this->mapResult($r, 'tv'), $tv['results'] ?? []),
                );
            },
            ($movieMeta['total_results'] ?? 0) + ($tvMeta['total_results'] ?? 0),
            max($movieMeta['total_pages'] ?? 1, $tvMeta['total_pages'] ?? 1),
        );

        return array_merge($result, ['keyword_name' => $keyword['name'] ?? '']);
    }

    private function keywordMovies(string $id, int $page): array
    {
        try {
            return $this->tmdb->fetch("/keyword/{$id}/movies?page={$page}");
        } catch (\Throwable) {
            return ['total_pages' => 0, 'total_results' => 0, 'results' => []];
        }
    }

    private function keywordTv(string $id, int $page): array
    {
        try {
            return $this->tmdb->fetch("/discover/tv?with_keywords={$id}&page={$page}");
        } catch (\Throwable) {
            return ['total_pages' => 0, 'total_results' => 0, 'results' => []];
        }
    }

    private function mapResult(array $item, string $type): array
    {
        return [
            'tmdb_id' => (string) $item['id'],
            'title' => $item['title'] ?? $item['name'] ?? 'Untitled',
            'year' => substr($item['release_date'] ?? $item['first_air_date'] ?? '', 0, 4),
            'poster_url' => $this->tmdb->posterUrl($item['poster_path'] ?? null),
            'rating' => isset($item['vote_average']) ? number_format($item['vote_average'], 1) : '',
            'type' => $type,
        ];
    }
}
