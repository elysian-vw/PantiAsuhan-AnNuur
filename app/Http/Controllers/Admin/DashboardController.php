<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bantuan;
use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\Kebutuhan;
use App\Models\Kegiatan;
use App\Models\Kunjungan;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $paid = Donasi::where('status', 'Berhasil');
        $stats = [
            'Donasi bersih tercatat' => 'Rp'.number_format((clone $paid)->whereNotNull('biaya')->sum(\DB::raw('nominal - biaya')), 0, ',', '.'),
            'Total donatur' => Donatur::count(),
            'Donasi bersih bulan ini' => 'Rp'.number_format((clone $paid)->whereNotNull('biaya')->whereBetween('dibayar_pada', [now()->startOfMonth(), now()->endOfMonth()])->sum(\DB::raw('nominal - biaya')), 0, ',', '.'),
            'Bantuan diterima' => Bantuan::where('status', 'Diterima')->count(),
            'Kebutuhan aktif' => Kebutuhan::where('status', '!=', 'Terpenuhi')->count(),
            'Kunjungan hari ini' => Kunjungan::whereDate('tanggal', today())->whereIn('status', ['Disetujui', 'Hadir', 'Selesai'])->count(),
            'Pengajuan menunggu' => Kunjungan::where('status', 'Menunggu')->count(),
            'Jumlah kegiatan' => Kegiatan::count(),
        ];
        $chart = collect(range(5, 0))->map(function ($i) {
            $date = now()->startOfMonth()->subMonths($i);

            return ['label' => $date->translatedFormat('M Y'), 'value' => (float) Donasi::where('status', 'Berhasil')->whereNotNull('biaya')->whereBetween('dibayar_pada', [$date, $date->copy()->endOfMonth()])->sum(\DB::raw('nominal - biaya'))];
        });

        return view('admin.dashboard', ['stats' => $stats, 'chart' => $chart,
            'unknownFees' => (clone $paid)->whereNull('biaya')->count(),
            'visits' => Kunjungan::where('status', 'Menunggu')->latest()->take(5)->get()]);
    }
}
