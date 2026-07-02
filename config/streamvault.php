<?php

return [
    'vidapi_base' => env('VIDAPI_BASE_URL', 'https://vidapi.ru'),
    'embed_base' => env('EMBED_BASE_URL', 'https://vaplayer.ru/embed'),
    'player_color' => '#00e5a0',

    'tmdb_api_key' => env('TMDB_API_KEY'),
    'tmdb_access_token' => env('TMDB_ACCESS_TOKEN'),

    'enable_vidapi_catalog_scan' => env('ENABLE_VIDAPI_CATALOG_SCAN', false),
    'enable_people_index' => env('ENABLE_PEOPLE_INDEX', false),

    'per_page' => 24,
    'people_per_page' => 35,

    'cache_ttl' => [
        'stats' => 3600,
        'page' => 300,
        'search' => 120,
        'tmdb' => 1800,
    ],
];
