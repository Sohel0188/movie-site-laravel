<?php

namespace App\Services\StreamVault;

class PeopleService
{
    private const LIBRARY_PEOPLE_TOTAL = 308139;

    public function __construct(private TmdbService $tmdb) {}

    public function query(int $page): array
    {
        $perPage = config('streamvault.people_per_page');
        $libraryTotal = self::LIBRARY_PEOPLE_TOTAL;

        if (! $this->tmdb->hasCredentials()) {
            return [
                'page' => 1,
                'per_page' => $perPage,
                'total' => 0,
                'total_pages' => 1,
                'items' => [],
                'library_total' => $libraryTotal,
            ];
        }

        $popular = $this->tmdb->fetchPopularPeople($page);

        return [
            'page' => $popular['page'],
            'per_page' => $perPage,
            'total' => $libraryTotal,
            'total_pages' => max(1, (int) ceil($libraryTotal / $perPage)),
            'items' => $popular['items'],
            'library_total' => $libraryTotal,
            'indexing' => false,
        ];
    }
}
