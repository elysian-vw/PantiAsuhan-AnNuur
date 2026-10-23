<?php

use App\Http\Controllers\Admin\CashflowController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DonorAccountController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ManualDonationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::view('/pembayaran/mobile/kembali', 'public.mobile-payment-return')->name('mobile.payment.return');
Route::get('/admin/login', fn () => view('auth.login'))->middleware('guest')->name('login');
Route::post('/admin/login', [AuthController::class, 'login'])
    ->middleware('guest')
    ->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
Route::get('/{kind}', [PublicController::class, 'form'])
    ->whereIn('kind', ['donasi', 'bantuan'])
    ->name('submission.form');
Route::post('/{kind}', [SubmissionController::class, 'store'])
    ->whereIn('kind', ['donasi', 'bantuan'])
    ->middleware('throttle:10,1')
    ->name('submission.store');
Route::get('/riwayat/{kind}/{token}', [SubmissionController::class, 'tracking'])
    ->where('token', '[a-zA-Z0-9]{64}')
    ->name('tracking');
Route::post('/riwayat/donasi/{token}/bayar', [SubmissionController::class, 'pay'])
    ->middleware('throttle:5,1')
    ->name('payment');
Route::post('/webhooks/midtrans', [SubmissionController::class, 'webhook'])
    ->middleware('throttle:60,1')
    ->name('midtrans.webhook');
Route::get('/{page}/{id}', [PublicController::class, 'article'])
    ->whereIn('page', ['kegiatan', 'berita'])
    ->whereNumber('id')
    ->name('article');
Route::get('/{page}', [PublicController::class, 'page'])
    ->whereIn('page', ['profil', 'kontak', 'kegiatan', 'berita', 'galeri', 'kebutuhan'])
    ->name('page');

require __DIR__.'/donor.php';

Route::middleware(['auth', 'account.role:pengurus'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/donatur/{donor}/akun', [DonorAccountController::class, 'show'])->name('donor.account');
        Route::post('/donatur/{donor}/setujui', [DonorAccountController::class, 'approve'])->name('donor.approve');
        Route::post('/donatur/{donor}/undangan', [DonorAccountController::class, 'invite'])->middleware('throttle:5,1')->name('donor.invite');
        Route::post('/donatur/{donor}/akses', [DonorAccountController::class, 'access'])->name('donor.access');
        Route::post('/donatur/{donor}/riwayat/{donation}', [DonorAccountController::class, 'verifyDonation'])->name('donor.verify');
        // Donasi Manual
        Route::get('/donasi-manual', [ManualDonationController::class, 'create'])->name('donation.manual.create');
        Route::post('/donasi-manual', [ManualDonationController::class, 'store'])->name('donation.manual.store');

        // Pengeluaran Dana
        Route::get('/pengeluaran', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/pengeluaran/tambah', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/pengeluaran', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/pengeluaran/{id}', [ExpenseController::class, 'show'])->whereNumber('id')->name('expenses.show');
        Route::get('/pengeluaran/{id}/edit', [ExpenseController::class, 'edit'])->whereNumber('id')->name('expenses.edit');
        Route::put('/pengeluaran/{id}', [ExpenseController::class, 'update'])->whereNumber('id')->name('expenses.update');
        Route::post('/pengeluaran/{id}/post', [ExpenseController::class, 'post'])->whereNumber('id')->name('expenses.post');
        Route::post('/pengeluaran/{id}/cancel', [ExpenseController::class, 'cancel'])->whereNumber('id')->name('expenses.cancel');
        Route::delete('/pengeluaran/{id}', [ExpenseController::class, 'destroy'])->whereNumber('id')->name('expenses.destroy');
        Route::get('/pengeluaran/{id}/bukti', [ExpenseController::class, 'downloadBukti'])->whereNumber('id')->name('expenses.download');

        // Arus Kas & Saldo
        Route::get('/arus-kas', [CashflowController::class, 'index'])->name('cashflow.index');
        Route::post('/arus-kas/saldo-pembukaan', [CashflowController::class, 'saveOpeningBalance'])->name('cashflow.opening');

        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/pengaturan', [SettingsController::class, 'index'])->name('settings');
        Route::put('/pengaturan', [SettingsController::class, 'save'])->name('settings.save');
        Route::get('/laporan/{kind}', [ReportController::class, 'index'])->name('reports');
        Route::get('/transaksi/{kind}', [TransactionController::class, 'index'])->name('transaction.index');
        Route::get('/transaksi/{kind}/{id}', [TransactionController::class, 'show'])
            ->whereNumber('id')
            ->name('transaction.show');
        Route::get('/donasi-manual/{donation}/bukti', [TransactionController::class, 'downloadManualProof'])->name('donation.manual.proof');
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
