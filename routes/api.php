<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DonationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReferenceController;
use App\Http\Controllers\Api\V1\TransparencyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Authentication (Public)
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');

    // References (Public)
    Route::get('/references/donation-categories', [ReferenceController::class, 'donationCategories'])->name('references.donation-categories');
    Route::get('/references/nominal-presets', [ReferenceController::class, 'nominalPresets'])->name('references.nominal-presets');

    // Transparency & Cashflow (Public for transparency, or accessible by donors)
    Route::get('/transparency/summary', [TransparencyController::class, 'summary'])->name('transparency.summary');
    Route::get('/transparency/categories', [TransparencyController::class, 'categories'])->name('transparency.categories');
    Route::get('/transparency/expenses', [TransparencyController::class, 'expenses'])->name('transparency.expenses');
    Route::get('/transparency/expenses/{id}', [TransparencyController::class, 'expenseDetail'])->whereNumber('id')->name('transparency.expense.detail');
    Route::get('/transparency/expenses/{id}/proof', [TransparencyController::class, 'downloadPublicProof'])->whereNumber('id')->name('transparency.expense.proof');

    // Authenticated Mobile Donor Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Donor Profile
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'changePassword'])->middleware('throttle:5,1')->name('profile.password');

        // Donor Personal Donations
        Route::get('/donations', [DonationController::class, 'index'])->name('donations.index');
        Route::post('/donations', [DonationController::class, 'store'])->name('donations.store');
        Route::get('/donations/summary', [DonationController::class, 'summary'])->name('donations.summary');
        Route::get('/donations/{id}', [DonationController::class, 'show'])->whereNumber('id')->name('donations.show');
        Route::post('/donations/{id}/cancel', [DonationController::class, 'cancel'])->whereNumber('id')->name('donations.cancel');

        // Donor Payment Checkout & Status
        Route::post('/donations/{id}/pay', [PaymentController::class, 'pay'])->whereNumber('id')->name('donations.pay');
        Route::get('/donations/{id}/status', [PaymentController::class, 'status'])->whereNumber('id')->name('donations.status');
    });
});
