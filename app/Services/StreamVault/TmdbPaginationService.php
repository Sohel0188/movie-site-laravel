<?php

namespace App\Services\StreamVault;

class TmdbPaginationService
{
    private const DEFAULT_TMDB_PAGE_SIZE = 20;

    public function paginateDiscover(
        int $userPage,
        int $perPage,
        callable $fetchPage,
        callable $mapResult,
    ): array {
        $meta = $fetchPage(1);
        $pageSize = count($meta['results'] ?? []) > 0
            ? count($meta['results'])
            : self::DEFAULT_TMDB_PAGE_SIZE;
        $total = $meta['total_results'];
        $totalPages = max(1, (int) ceil($total / $perPage));
        $safePage = min(max(1, $userPage), $totalPages);

        $start = ($safePage - 1) * $perPage;
        $firstTmdb = (int) floor($start / $pageSize) + 1;
        $lastTmdb = (int) floor(($start + $perPage - 1) / $pageSize) + 1;

        $pool = [];
        for ($p = $firstTmdb; $p <= $lastTmdb; $p++) {
            $data = $p === 1 ? $meta : $fetchPage($p);
            $pool = array_merge($pool, $data['results'] ?? []);
        }

        $baseOffset = ($firstTmdb - 1) * $pageSize;
        $sliceStart = $start - $baseOffset;
        $items = array_map(
            $mapResult,
            array_slice($pool, $sliceStart, $perPage),
        );

        return [
            'page' => $safePage,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'items' => $items,
        ];
    }

    public function paginateMerged(
        int $userPage,
        int $perPage,
        callable $fetchMergedPage,
        int $totalResults,
        int $maxTmdbPages,
    ): array {
        $totalPages = max(1, (int) ceil($totalResults / $perPage));
        $safePage = min(max(1, $userPage), $totalPages);
        $skip = ($safePage - 1) * $perPage;
        $results = [];
        $skipped = 0;

        for ($p = 1; $p <= $maxTmdbPages && count($results) < $perPage; $p++) {
            $batch = $fetchMergedPage($p);
            if (empty($batch)) {
                break;
            }
            foreach ($batch as $item) {
                if ($skipped < $skip) {
                    $skipped++;
                    continue;
                }
                $results[] = $item;
                if (count($results) >= $perPage) {
                    break;
                }
            }
        }

        return [
            'page' => $safePage,
            'per_page' => $perPage,
            'total' => $totalResults,
            'total_pages' => $totalPages,
            'items' => $results,
        ];
    }
}
