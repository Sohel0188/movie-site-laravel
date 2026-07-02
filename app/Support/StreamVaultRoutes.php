<?php

namespace App\Support;

class StreamVaultRoutes
{
    public static function browseUrl(string $basePath, array $query = []): string
    {
        $params = [];
        if (! empty($query['genre'])) {
            $params['genre'] = $query['genre'];
        }
        if (! empty($query['year'])) {
            $params['year'] = $query['year'];
        }
        if (! empty($query['sort'])) {
            $params['sort'] = $query['sort'];
        }
        if (! empty($query['page']) && (int) $query['page'] > 1) {
            $params['page'] = $query['page'];
        }

        $qs = http_build_query($params);

        return $qs ? "{$basePath}?{$qs}" : $basePath;
    }

    public static function mediaHref(array $item, ?string $type = null): string
    {
        $t = $item['_type'] ?? $item['type'] ?? $type ?? 'movies';

        if ($t === 'episodes') {
            $id = $item['show_tmdb_id'] ?? $item['show_imdb_id'] ?? null;

            return $id ? route('tv.show', $id) : route('home');
        }

        $mediaType = self::mediaTypeOf($item, $type);
        $id = $item['tmdb_id'] ?? $item['imdb_id'] ?? null;
        if (! $id) {
            return route('home');
        }

        return $mediaType === 'tv'
            ? route('tv.show', $id)
            : route('movie.show', $id);
    }

    public static function mediaTypeOf(array $item, string $fallback = 'movies'): string
    {
        $t = $item['_type'] ?? $item['type'] ?? $fallback;
        if ($t === 'episodes') {
            return 'tv';
        }

        return $t === 'tv' ? 'tv' : 'movie';
    }
}
