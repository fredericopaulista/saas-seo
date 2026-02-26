<?php

use Illuminate\Support\Facades\Route;

// Serve the Vue SPA and let Vue Router handle the paths
// The regex below excludes any request starting with "api/" so 
// the Laravel API can correctly throw 401s or 404s JSON responses.
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '^(?!api/).*$');
