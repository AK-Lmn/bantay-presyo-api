<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/docs');

// Docs are generated with `php artisan scribe:generate` and committed, so these
// routes work in production where Scribe (a dev dependency) is not installed.
Route::view('docs', 'scribe.index')->name('scribe');

Route::get('docs.postman', fn () => response()->file(
    storage_path('app/private/scribe/collection.json'),
    ['Content-Type' => 'application/json'],
))->name('scribe.postman');

Route::get('docs.openapi', fn () => response()->file(
    storage_path('app/private/scribe/openapi.yaml'),
    ['Content-Type' => 'application/yaml'],
))->name('scribe.openapi');
