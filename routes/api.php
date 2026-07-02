<?php

use App\Http\Controllers\Api\StreamVaultApiController;
use Illuminate\Support\Facades\Route;

Route::get('/movies', [StreamVaultApiController::class, 'movies']);
Route::get('/tvshows', [StreamVaultApiController::class, 'tvShows']);
Route::get('/episodes', [StreamVaultApiController::class, 'episodes']);
Route::get('/browse', [StreamVaultApiController::class, 'browse']);
Route::get('/stats', [StreamVaultApiController::class, 'stats']);
Route::get('/search', [StreamVaultApiController::class, 'search']);
Route::get('/people', [StreamVaultApiController::class, 'people']);
Route::get('/people/{id}', [StreamVaultApiController::class, 'person']);
Route::get('/media/{type}/{id}', [StreamVaultApiController::class, 'media']);
Route::get('/country/{code}', [StreamVaultApiController::class, 'country']);
Route::get('/language/{code}', [StreamVaultApiController::class, 'language']);
Route::get('/keyword/{id}', [StreamVaultApiController::class, 'keyword']);
