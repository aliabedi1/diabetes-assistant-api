<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Glucose\GlucoseLogController;
use App\Http\Controllers\Api\V1\Medical\MedicalLogController;
use App\Http\Controllers\Api\V1\Medicine\MedicineController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'auth.',
    'prefix' => 'auth',
], function ($router) {
    $router->post('/register', [AuthController::class, 'register'])->name('register');
    $router->post('/login', [AuthController::class, 'login'])->name('login');
});

Route::group([
    'middleware' => [
        'auth:sanctum',
    ],
], function ($router) {
    Route::group([
        'as' => 'auth.',
        'prefix' => 'auth',
    ], function ($router) {
        $router->get('/me', [AuthController::class, 'me'])->name('me');
        $router->post('/logout', [AuthController::class, 'logout'])->name('logout');
    });

    $router->group(['as' => 'glucose.', 'prefix' => 'glucose'], function ($router) {
        $router->group(['as' => 'logs.', 'prefix' => 'logs'], function ($router) {
            $router->get('/', [GlucoseLogController::class, 'index'])->name('index');
            $router->post('/', [GlucoseLogController::class, 'store'])->name('store');
        });
        $router->get('/chart', [GlucoseLogController::class, 'chart'])->name('chart');
    });

    $router->group(['as' => 'medical.', 'prefix' => 'medical'], function ($router) {
        $router->group(['as' => 'logs.', 'prefix' => 'logs'], function ($router) {
            $router->get('/', [MedicalLogController::class, 'index'])->name('index');
            $router->post('/', [MedicalLogController::class, 'store'])->name('store');
        });
    });

    $router->group(['as' => 'medicines.', 'prefix' => 'medicines'], function ($router) {
        $router->get('/', [MedicineController::class, 'index'])->name('index');
        $router->post('/', [MedicineController::class, 'store'])->name('store');
        // 'recent' must be before {medicine} to avoid being caught as a route param
        $router->get('/recent', [MedicineController::class, 'recent'])->name('recent');
        $router->put('/{medicine}', [MedicineController::class, 'update'])->name('update');
        $router->delete('/{medicine}', [MedicineController::class, 'destroy'])->name('destroy');
    });
});
