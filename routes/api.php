<?php

use App\Enums\Provider;
use App\Http\Controllers\Api\EmployeeController;
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

/*
|--------------------------------------------------------------------------
| Employee Routes
|--------------------------------------------------------------------------
|
| Here is where you can register employee routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
*/


// 
Route::prefix('{provider}')
    ->where(['provider' => Provider::routeConstraint()])
    ->middleware(env('APP_ENV') === 'production' ? 'provider.auth' : null) // Enable OAuth authentication in production
    ->group(function () {
        Route::get('/employees/{employee_id}', [EmployeeController::class, 'showEmployeeById']);
        Route::post('/employees', [EmployeeController::class, 'addEmployeeById'])->middleware('check.escape');
        Route::put('/employees/{employee_id}', [EmployeeController::class, 'updateEmployeeById'])->middleware('check.escape');
    });
