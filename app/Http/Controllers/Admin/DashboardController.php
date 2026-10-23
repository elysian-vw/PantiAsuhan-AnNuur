<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bantuan;
use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\Kebutuhan;
use App\Models\Kegiatan;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $paid = Donasi::where('status', 'Berhasil');
        $stats = [
            'Donasi bersih tercatat' => 'Rp'.
                number_format((clone $paid)->sum(\DB::raw(Donasi::NET_SQL)), 0, ',', '.'),
            'Total donatur' => Donatur::count(),
            'Donasi bersih bulan ini' => 'Rp'.
                number_format(
                    (clone $paid)
                        ->whereBetween('dibayar_pada', [now()->startOfMonth(), now()->endOfMonth()])
                        ->sum(\DB::raw(Donasi::NET_SQL)),
                    0,
                    ',',
                    '.',
                ),
            'Bantuan diterima' => Bantuan::where('status', 'Diterima')->count(),
            'Kebutuhan aktif' => Kebutuhan::where('status', '!=', 'Terpenuhi')->count(),
            'Bantuan menunggu konfirmasi' => Bantuan::where('status', 'Diajukan')->count(),
            'Jumlah kegiatan' => Kegiatan::count(),
        ];
        $chart = collect(range(5, 0))->map(function ($i) {
            $date = now()->startOfMonth()->subMonths($i);

            return [
                'label' => $date->translatedFormat('M Y'),
                'value' => (float) Donasi::where('status', 'Berhasil')
                    ->whereBetween('dibayar_pada', [$date, $date->copy()->endOfMonth()])
                    ->sum(\DB::raw(Donasi::NET_SQL)),
            ];
        });

        return view('admin.dashboard', [
            'stats' => $stats,
            'chart' => $chart,
            'unknownFees' => (clone $paid)->online()->whereNull('biaya')->count(),
            'pendingAssistance' => Bantuan::where('status', 'Diajukan')->latest()->take(5)->get(),
            'pendingDonors' => Donatur::where('jenis', 'tetap')->whereHas('user', fn ($q) => $q->where('account_status', 'menunggu'))->count(),
        ]);
    }
}
