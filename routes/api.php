<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CitiesController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\StudentsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
});

// students routes
Route::group(
    [
        'middleware' => 'jwtmiddleware',
        'prefix' => 'students'
    ],
    function ($router) {
        Route::get('/', [StudentsController::class, 'index']);
        Route::get('/{id}', [StudentsController::class, 'show']);
        Route::post('/', [StudentsController::class, 'store']);
        Route::put('/', [StudentsController::class, 'update']);
    }
);
Route::group(
    [
        'middleware' => 'jwtmiddleware',
        'prefix' => 'cities'
    ],
    function ($router) {
        Route::get('/lookup', [CitiesController::class, 'lookup']);
    }
);

Route::group(
    [
        'middleware' => 'jwtmiddleware',
        'prefix' => 'provinces'
    ],
    function ($router) {
        Route::get('/lookup', [ProvinceController::class, 'lookup']);
    }
);
