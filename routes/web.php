<?php

use Illuminate\Support\Facades\Route;

// Serve the Vue SPA and let Vue Router handle the paths
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '.*');
