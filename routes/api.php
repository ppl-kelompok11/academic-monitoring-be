<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CitiesController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\StudentsController;
use App\Http\Controllers\IrsController;
use App\Http\Controllers\KhsController;
use App\Http\Controllers\PklController;
use App\Http\Controllers\SkripsiController;
use App\Http\Controllers\LectureController;
use App\Http\Controllers\RecapController;
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
        Route::get('/academic/{id}', [StudentsController::class, 'academic']);
    }
);

// students routes
Route::group(
    [
        'middleware' => 'jwtmiddleware',
        'prefix' => 'lecture'
    ],
    function ($router) {
        Route::get('/', [LectureController::class, 'index']);
        Route::get('/{id}', [LectureController::class, 'show']);
        Route::post('/', [LectureController::class, 'store']);
        Route::put('/', [LectureController::class, 'update']);
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

Route::group(
    [
        'middleware' => 'jwtmiddleware',
        'prefix' => 'semester'
    ],
    function ($router) {
        Route::get('/lookup', [SemesterController::class, 'lookup']);
    }
);
// irs
Route::group(
    [
        'middleware' => 'jwtmiddleware',
        'prefix' => 'irs'
    ],
    function ($router) {
        Route::get('/', [IrsController::class, 'index']);
        Route::get('/{id}', [IrsController::class, 'show']);
        Route::post('/', [IrsController::class, 'store']);
        Route::put('/', [IrsController::class, 'update']);
        Route::put('/validate', [IrsController::class, 'validation']);
    }
);

// khs
Route::group(
    [
        'middleware' => 'jwtmiddleware',
        'prefix' => 'khs'
    ],
    function ($router) {
        Route::get('/', [KhsController::class, 'index']);
        Route::get('/{id}', [KhsController::class, 'show']);
        Route::post('/', [KhsController::class, 'store']);
        Route::put('/', [KhsController::class, 'update']);
        Route::put('/validate', [KhsController::class, 'validation']);
    }
);

// pkl
Route::group(
    [
        'middleware' => 'jwtmiddleware',
        'prefix' => 'pkl'
    ],
    function ($router) {
        Route::get('/', [PklController::class, 'index']);
        Route::get('/{id}', [PklController::class, 'show']);
        Route::post('/', [PklController::class, 'store']);
        Route::put('/', [PklController::class, 'update']);
        Route::put('/validate', [PklController::class, 'validation']);
    }
);

// skripsi
Route::group(
    [
        'middleware' => 'jwtmiddleware',
        'prefix' => 'skripsi'
    ],
    function ($router) {
        Route::get('/', [SkripsiController::class, 'index']);
        Route::get('/{id}', [SkripsiController::class, 'show']);
        Route::post('/', [SkripsiController::class, 'store']);
        Route::put('/', [SkripsiController::class, 'update']);
        Route::put('/validate', [SkripsiController::class, 'validation']);
    }
);

Route::group(
    [
        'prefix' => 'file'
    ],
    function ($router) {
        Route::post("/upload", [FileController::class, "upload"]);
        Route::get("/temp-file/{originalname}", [FileController::class, "getTempFile"]);
        Route::get("/{model}/{fileName}", [FileController::class, "getFile"]);
    }
);

// rekap
Route::group(
    [
        'middleware' => 'jwtmiddleware',
        'prefix' => 'recap'
    ],
    function ($router) {
        Route::get('/pkl', [RecapController::class, 'recapPkl']);
        Route::get('/skripsi', [RecapController::class, 'recapSkripsi']);
        Route::get('/status', [RecapController::class, 'recapStatus']);
    }
);
