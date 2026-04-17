<?php

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\CutiSaldoController;
use App\Http\Controllers\API\V1\CutiHistoryController;
use App\Http\Controllers\API\V1\CutiApiController;
use App\Http\Controllers\API\V1\IzinApiController;
use App\Http\Controllers\API\V1\ApiSkpdController;
use App\Http\Controllers\API\V1\DeviceController;

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Route::post('/device/store', [DeviceController::class, 'store']);
    // Route::middleware('auth:sanctum')->prefix('cuti')->group(function () {
    //     Route::get('/riwayat/{id_pegawai}', [CutiApiController::class, 'riwayat']);
    // });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('cuti')->group(function () {
            Route::post('/entry', [CutiApiController::class, 'store']);
            Route::get('/entry/show', [CutiApiController::class, 'entries']);
            Route::get('/entry/show/{id_pegawai}/up', [CutiApiController::class, 'entriesUp']);
            Route::get('/entry/show/all', [CutiApiController::class, 'entriesAll']);
            Route::get('/jenis', [CutiApiController::class, 'jenis']);
            Route::get('/riwayat/{id_pegawai}', [CutiApiController::class, 'riwayat']);
            Route::get('/saldo/{id_pegawai}', [CutiApiController::class, 'saldo']);
            Route::put('/verifikasi', [CutiApiController::class, 'verifikasi']);
        });

        Route::prefix('izin')->group(function () {
            Route::get('/riwayat/{id_pegawai}', [IzinApiController::class, 'riwayat']);
        });


        // Route::get('/datauser', [AuthController::class, 'dataUser']);
        Route::get('/user/data', [AuthController::class, 'data']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('saldo-cuti', [CutiSaldoController::class, 'me']);
        Route::get('saldo-cuti/{user}', [CutiSaldoController::class, 'byUser']);

        Route::get('/history', [CutiHistoryController::class, 'index']);
        Route::get('/history/{cuti}', [CutiHistoryController::class, 'show']);

        // Route::get('/cuti', [CutiApiController::class, 'index']);
        // Route::get('/cuti/{cuti}', [CutiApiController::class, 'show']);

        // Route::post('/cuti', [CutiApiController::class, 'store']);
        // Route::put('/cuti/{cuti}', [CutiApiController::class, 'update']);
        // Route::patch('/cuti/{cuti}', [CutiApiController::class, 'update']);
        // Route::post('/cuti/{cuti}/submit', [CutiApiController::class, 'submit']);

        // Route::post('/cuti/{cuti}/atasan/decide', [CutiApiController::class, 'decideAtasan']);
        // Route::post('/cuti/{cuti}/kepala/decide', [CutiApiController::class, 'decideKepala']);
    });
});
