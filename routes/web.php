<?php

use App\Http\Controllers\AuthController;
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

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});
