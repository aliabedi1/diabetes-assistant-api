<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Glucose\GlucoseLogController;
use App\Http\Controllers\Api\V1\Injection\MedicalLogController;
use Illuminate\Support\Facades\Route;

Route::group([
    'as'     => 'auth.',
    'prefix' => 'auth',
], function ($router) {
    $router->post('/register', [AuthController::class, 'register'])->name('register');
    $router->post('/login', [AuthController::class, 'login'])->name('login');
});


Route::group([
    'middleware' => [
        'auth:sanctum'
    ]
], function ($router) {
    Route::group([
        'as'     => 'auth.',
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
    });

    $router->group(['as' => 'medical.', 'prefix' => 'medical'], function ($router) {
        $router->group(['as' => 'logs.', 'prefix' => 'logs'], function ($router) {
            $router->get('/', [MedicalLogController::class, 'index'])->name('index');
            $router->post('/', [MedicalLogController::class, 'store'])->name('store');
        });
    });
});
