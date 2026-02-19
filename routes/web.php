<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Route::livewire('/login', 'pages::auth.login')->name('login');

Route::get('/login', [AuthController::class, 'auth'])->name('auth');
Route::post('/login', [AuthController::class, 'login'])->name('login');


Route::middleware('auth')->group(function () {

    Route::livewire('/dashboard', 'pages::dashboard')
        ->name('dashboard');

    Route::livewire('/pegawai', 'pages::pegawai')
        ->name('pegawai');


    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
});
