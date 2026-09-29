<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/admin/login', fn () => view('auth.login'))->middleware('guest')->name('login');
Route::post('/admin/login', [AuthController::class, 'login'])
    ->middleware('guest')
    ->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
Route::get('/{kind}', [PublicController::class, 'form'])
    ->whereIn('kind', ['donasi', 'bantuan', 'kunjungan'])
    ->name('submission.form');
Route::post('/{kind}', [SubmissionController::class, 'store'])
    ->whereIn('kind', ['donasi', 'bantuan', 'kunjungan'])
    ->middleware('throttle:10,1')
    ->name('submission.store');
Route::get('/riwayat/{kind}/{token}', [SubmissionController::class, 'tracking'])
    ->where('token', '[a-zA-Z0-9]{64}')
    ->name('tracking');
Route::post('/riwayat/donasi/{token}/bayar', [SubmissionController::class, 'pay'])
    ->middleware('throttle:5,1')
    ->name('payment');
Route::post('/webhooks/midtrans', [SubmissionController::class, 'webhook'])->name('midtrans.webhook');
Route::get('/{page}/{id}', [PublicController::class, 'article'])
    ->whereIn('page', ['kegiatan', 'berita'])
    ->whereNumber('id')
    ->name('article');
Route::get('/{page}', [PublicController::class, 'page'])
    ->whereIn('page', ['profil', 'kontak', 'kegiatan', 'berita', 'galeri', 'kebutuhan'])
    ->name('page');

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/pengaturan', [SettingsController::class, 'index'])->name('settings');
        Route::put('/pengaturan', [SettingsController::class, 'save'])->name('settings.save');
        Route::get('/kalender', [TransactionController::class, 'calendar'])->name('calendar');
        Route::get('/tamu-langsung', [TransactionController::class, 'walkInForm'])->name('walkin');
        Route::post('/tamu-langsung', [TransactionController::class, 'walkIn'])
            ->defaults('kind', 'kunjungan')
            ->name('walkin.store');
        Route::get('/laporan/{kind}', [ReportController::class, 'index'])->name('reports');
        Route::get('/transaksi/{kind}', [TransactionController::class, 'index'])->name('transaction.index');
        Route::get('/transaksi/{kind}/{id}', [TransactionController::class, 'show'])
            ->whereNumber('id')
            ->name('transaction.show');
        Route::put('/transaksi/{kind}/{id}', [TransactionController::class, 'update'])
            ->whereNumber('id')
            ->name('transaction.update');
        Route::get('/data/{resource}', [ResourceController::class, 'index'])->name('resource.index');
        Route::get('/data/{resource}/tambah', [ResourceController::class, 'form'])->name('resource.create');
        Route::post('/data/{resource}', [ResourceController::class, 'save'])->name('resource.store');
        Route::get('/data/{resource}/{id}/edit', [ResourceController::class, 'form'])
            ->whereNumber('id')
            ->name('resource.edit');
        Route::put('/data/{resource}/{id}', [ResourceController::class, 'save'])
            ->whereNumber('id')
            ->name('resource.update');
        Route::delete('/data/{resource}/{id}', [ResourceController::class, 'destroy'])
            ->whereNumber('id')
            ->name('resource.destroy');
    });
