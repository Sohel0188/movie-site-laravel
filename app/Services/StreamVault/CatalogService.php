<?php

namespace App\Services\StreamVault;

class CatalogService
{
    public function __construct(
        private VidApiService $vidApi,
        private BrowseService $browse,
    ) {}

    public function fetchFilteredMovies(int $page, array $opts = []): array
    {
        $genre = $opts['genre'] ?? null;
        $year = $opts['year'] ?? null;
        $sort = $opts['sort'] ?? null;

        if ($genre || $year || in_array($sort, ['popular', 'rating'], true)) {
            return $this->browse->queryBrowse($page, array_merge($opts, ['type' => 'movie']));
        }

        return $this->vidApi->fetchMovies($page);
    }

    public function fetchFilteredTVShows(int $page, array $opts = []): array
    {
        $genre = $opts['genre'] ?? null;
        $year = $opts['year'] ?? null;
        $sort = $opts['sort'] ?? null;

        if ($genre || $year || in_array($sort, ['popular', 'rating'], true)) {
            return $this->browse->queryBrowse($page, array_merge($opts, ['type' => 'tv']));
        }

        return $this->vidApi->fetchTVShows($page);
    }
}
