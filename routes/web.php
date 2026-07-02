<?php

use App\Http\Controllers\Api\StreamVaultApiController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DiscoverController::class, 'home'])->name('home');
Route::get('/movies', [DiscoverController::class, 'movies'])->name('movies');
Route::get('/tv-shows', [DiscoverController::class, 'tvShows'])->name('tv-shows');
Route::get('/episodes', [DiscoverController::class, 'episodes'])->name('episodes');
Route::get('/browse', [DiscoverController::class, 'browse'])->name('browse');
Route::get('/most-viewed', [DiscoverController::class, 'mostViewed'])->name('most-viewed');
Route::get('/top-tv', [DiscoverController::class, 'topTv'])->name('top-tv');
Route::get('/people', [DiscoverController::class, 'people'])->name('people');
Route::get('/search', [SearchController::class, 'index'])->name('search');

Route::get('/category/{slug}', [DiscoverController::class, 'category'])->name('category');
Route::get('/year/{year}', [DiscoverController::class, 'year'])->name('year');
Route::get('/country/{code}', [DiscoverController::class, 'country'])->name('country');
Route::get('/language/{code}', [DiscoverController::class, 'language'])->name('language');
Route::get('/keyword/{id}', [DiscoverController::class, 'keyword'])->name('keyword');

Route::get('/movie/{id}', [MediaController::class, 'showMovie'])->name('movie.show');
Route::get('/tv/{id}', [MediaController::class, 'showTv'])->name('tv.show');
Route::get('/person/{id}', [PersonController::class, 'show'])->name('person.show');
