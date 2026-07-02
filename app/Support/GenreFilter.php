<?php

namespace App\Support;

class GenreFilter
{
    public const GENRES = [
        'Action', 'Action & Adventure', 'Adventure', 'Animation', 'Comedy', 'Crime',
        'Documentary', 'Drama', 'Family', 'Fantasy', 'History', 'Horror', 'Kids',
        'Music', 'Mystery', 'News', 'Reality', 'Romance', 'Sci-Fi & Fantasy',
        'Science Fiction', 'Soap', 'Talk', 'Thriller', 'TV Movie', 'War',
        'War & Politics', 'Western',
    ];

    public const GENRE_TMDB = [
        'Action' => ['movie' => 28, 'tv' => 10759],
        'Action & Adventure' => ['tv' => 10759, 'movie' => 28],
        'Adventure' => ['movie' => 12, 'tv' => 10759],
        'Animation' => ['movie' => 16, 'tv' => 16],
        'Comedy' => ['movie' => 35, 'tv' => 35],
        'Crime' => ['movie' => 80, 'tv' => 80],
        'Documentary' => ['movie' => 99, 'tv' => 99],
        'Drama' => ['movie' => 18, 'tv' => 18],
        'Family' => ['movie' => 10751, 'tv' => 10751],
        'Fantasy' => ['movie' => 14, 'tv' => 10765],
        'History' => ['movie' => 36, 'tv' => 18],
        'Horror' => ['movie' => 27, 'tv' => 9648],
        'Kids' => ['tv' => 10762, 'movie' => 10751],
        'Music' => ['movie' => 10402, 'tv' => 35],
        'Mystery' => ['movie' => 9648, 'tv' => 9648],
        'News' => ['tv' => 10763],
        'Reality' => ['tv' => 10764],
        'Romance' => ['movie' => 10749, 'tv' => 18],
        'Sci-Fi & Fantasy' => ['tv' => 10765, 'movie' => 878],
        'Science Fiction' => ['movie' => 878, 'tv' => 10765],
        'Soap' => ['tv' => 10766],
        'Talk' => ['tv' => 10767],
        'Thriller' => ['movie' => 53, 'tv' => 9648],
        'TV Movie' => ['movie' => 10770],
        'War' => ['movie' => 10752, 'tv' => 10768],
        'War & Politics' => ['tv' => 10768, 'movie' => 10752],
        'Western' => ['movie' => 37, 'tv' => 37],
    ];

    public static function genreToSlug(string $genre): string
    {
        $slug = strtolower($genre);
        $slug = str_replace('&', 'and', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }

    public static function slugToGenre(string $slug): ?string
    {
        foreach (self::GENRES as $genre) {
            if (self::genreToSlug($genre) === $slug) {
                return $genre;
            }
        }

        return null;
    }

    public static function genresForMediaType(string $type): array
    {
        return array_values(array_filter(self::GENRES, function ($g) use ($type) {
            return $type === 'movie'
                ? isset(self::GENRE_TMDB[$g]['movie'])
                : isset(self::GENRE_TMDB[$g]['tv']);
        }));
    }

    public static function genreTmdbId(?string $genre, string $type): ?int
    {
        if (! $genre) {
            return null;
        }

        return self::GENRE_TMDB[$genre][$type] ?? null;
    }

    public static function buildYearList(int $count = 30): array
    {
        $current = (int) date('Y');

        return array_map(fn ($i) => (string) ($current - $i), range(0, $count - 1));
    }

    public static function matchesGenre(string $genreStr, string $genre): bool
    {
        $target = strtolower($genre);
        $parts = array_map('trim', explode(',', strtolower($genreStr)));

        return in_array($target, $parts, true);
    }

    public static function matchesYear(string $year, string $filterYear): bool
    {
        return $year === $filterYear;
    }

    public static function sortByPopularity(array $items): array
    {
        usort($items, fn ($a, $b) => (float) ($b['popularity'] ?? 0) <=> (float) ($a['popularity'] ?? 0));

        return $items;
    }

    public static function sortByRating(array $items): array
    {
        usort($items, fn ($a, $b) => (float) ($b['rating'] ?? 0) <=> (float) ($a['rating'] ?? 0));

        return $items;
    }
}
