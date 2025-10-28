<?php

use Illuminate\Support\Facades\Route;

// This is a backend API-only application
// All API routes are defined in routes/api.php

Route::get('/', function () {
    return response()->json([
        'message' => 'TrackTik Technical Test API',
        'documentation' => url('/api/documentation'),
        'version' => '1.0.0'
    ]);
});
