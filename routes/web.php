<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DatatablesController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'auth'])->name('auth');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth')->group(function () {

    Route::livewire('/dashboard', 'pages::dashboard')
        ->name('dashboard');

    Route::livewire('/pegawai', 'pages::pegawai')
        ->name('pegawai');

    Route::prefix('setting')->group(function () {
        Route::livewire('/akses', 'pages::setting.akses')
            ->name('setting.akses');
        Route::livewire('/cuti', 'pages::setting.cuti')
            ->name('setting.cuti');
        Route::livewire('/menu', 'pages::setting.menu')
            ->name('setting.menu');
        Route::livewire('/role', 'pages::setting.role')
            ->name('setting.role');
        Route::livewire('/user', 'pages::setting.user')
            ->name('setting.user');
    });
    Route::get('/pegawai/data', [DatatablesController::class, 'pegawai'])
        ->name('pegawai.data');
    Route::get('/setting/menu/data', [DatatablesController::class, 'menu'])
        ->name('setting.menu.data');
    Route::get('/setting/user/data', [DatatablesController::class, 'user'])
        ->name('setting.user.data');
    Route::get('/setting/role/data', [DatatablesController::class, 'role'])
        ->name('setting.role.data');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});
