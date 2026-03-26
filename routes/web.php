<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'auth'])->name('auth');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth', 'read')->group(function () {

    // =Dynamic Routes=
    Route::livewire('/data/pegawai', 'pages::data.pegawai')->name('data.pegawai');
    Route::livewire('/data/skpd', 'pages::data.skpd')->name('data.skpd');

    Route::livewire('/data/entry', 'pages::data.entry')->name('data.entry');


    Route::livewire('/dashboard', 'pages::dashboard')
        ->name('home.dashboard');

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
});
Route::get('/{entry}/pdf', [PdfController::class, 'pdf'])->name('pdf');
Route::get('/{entry}/verify', [PdfController::class, 'verify'])->name('verify');
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');
