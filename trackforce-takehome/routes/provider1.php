<?php

use App\Http\Controllers\Api\Provider1EmployeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Provider 1 API Routes
|--------------------------------------------------------------------------
|
| Routes for Provider 1 employee data endpoints.
| Note: Add ->middleware('provider.auth') to routes to enable OAuth token validation
|
*/

Route::prefix('provider1')->middleware(env('APP_ENV') === 'local' ? 'provider.auth' : null)->group(function () {
    Route::post('/employees', [Provider1EmployeeController::class, 'store'])->middleware('check.escape');
});

