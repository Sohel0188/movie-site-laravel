# StreamVault (Laravel)

Laravel port of the **StreamVault** streaming site — movies, TV shows, episodes, browse filters, search, and people pages powered by [VidAPI](https://vidapi.ru) and [TMDB](https://www.themoviedb.org).

Converted from the Next.js app at `E:\sohel\movie-site\streamvault\streamvault`.

## Requirements

- PHP 8.3+
- Composer
- Node.js 18+
- Laragon (or any PHP web server)

## Setup

```bash
cd e:\laragon\www\movie_site\movie-site
composer install
cp .env.example .env   # or copy manually on Windows
php artisan key:generate
npm install
npm run build
```

### Environment

Add to `.env`:

```env
APP_NAME=StreamVault
APP_URL=http://movie-site.test

# TMDB (required for search, people, movie/TV detail pages)
TMDB_API_KEY=your_key
TMDB_ACCESS_TOKEN=your_read_token

# Optional — uses Laravel cache (database/redis/file)
CACHE_STORE=database
```

Run migrations for cache/session if using database cache:

```bash
php artisan migrate
```

## Development

```bash
composer dev
```

Or separately:

```bash
php artisan serve
npm run dev
```

## Routes

| URL | Description |
|-----|-------------|
| `/` | Latest movies |
| `/movies`, `/tv-shows`, `/episodes` | Catalog listings |
| `/browse`, `/category/{slug}`, `/year/{year}` | Filtered browse |
| `/country/{code}`, `/language/{code}` | TMDB discover |
| `/movie/{id}`, `/tv/{id}` | Detail + player |
| `/search?q=` | Search |
| `/people`, `/person/{id}` | Cast & crew |
| `/api/*` | JSON API (same as Next.js routes) |

## Notes

- VidAPI embeds may require your domain in their **Allowed Sites** list.
- Copy `public/playable-index.json` from the Next.js project to speed up search/playable filtering.
- Genre/year filtered browse may take time on first load while the catalog index builds (cached under `storage/app/streamvault/`).
