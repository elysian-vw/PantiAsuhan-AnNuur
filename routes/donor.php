<?php

use App\Http\Controllers\DonorAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('donatur')->name('donor.')->group(function () {
    Route::get('/login', [DonorAuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [DonorAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
    Route::get('/lupa-password', [DonorAuthController::class, 'forgotForm'])->name('password.request');
    Route::post('/lupa-password', [DonorAuthController::class, 'sendReset'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [DonorAuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [DonorAuthController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
    Route::get('/akun', [DonorAuthController::class, 'account'])->middleware(['auth', 'account.role:donatur'])->name('account');
});
