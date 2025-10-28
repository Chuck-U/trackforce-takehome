<?php

use App\Http\Controllers\Api\Provider1EmployeeController;
use App\Http\Controllers\Api\Provider2EmployeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Provider 1 Endpoints
// Note: Add ->middleware('provider.auth') to routes to enable OAuth token validation
Route::prefix('provider1')->group(function () {
    Route::post('/employees', [Provider1EmployeeController::class, 'store']);
});

// Provider 2 Endpoints
// Note: Add ->middleware('provider.auth') to routes to enable OAuth token validation
Route::prefix('provider2')->group(function () {
    Route::post('/employees', [Provider2EmployeeController::class, 'store']);
});

