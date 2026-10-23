<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.admin', function ($view) {
            try {
                if (Schema::hasTable('donatur') && Schema::hasTable('users')) {
                    $pendingDonors = \App\Models\Donatur::where('jenis', 'tetap')
                        ->whereHas('user', fn ($q) => $q->where('account_status', 'menunggu'))
                        ->count();
                } else {
                    $pendingDonors = 0;
                }

                if (Schema::hasTable('donasi')) {
                    $todayDonations = \App\Models\Donasi::where('status', 'Berhasil')
                        ->where(function ($q) {
                            $q->whereDate('dibayar_pada', today())
                                ->orWhere(fn ($sq) => $sq->whereNull('dibayar_pada')->whereDate('created_at', today()));
                        })
                        ->count();
                } else {
                    $todayDonations = 0;
                }

                if (Schema::hasTable('bantuan')) {
                    $pendingAssistance = \App\Models\Bantuan::where('status', 'Diajukan')->count();
                } else {
                    $pendingAssistance = 0;
                }

                $view->with([
                    'badgePendingDonors' => $pendingDonors,
                    'badgeTodayDonations' => $todayDonations,
                    'badgePendingAssistance' => $pendingAssistance,
                ]);
            } catch (\Throwable $e) {
                $view->with([
                    'badgePendingDonors' => 0,
                    'badgeTodayDonations' => 0,
                    'badgePendingAssistance' => 0,
                ]);
            }
        });
    }
}
