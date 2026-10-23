@extends('layouts.admin')
@section('title', 'Arus Kas & Transparansi Dana')
@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Arus Kas & Transparansi Dana</h1>
            <p class="text-sm text-gray-600">Rekapitulasi penerimaan donasi, pengeluaran dibukukan, dan posisi saldo kas panti.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.reports', 'arus-kas') }}" class="button outline small">Unduh Laporan Arus Kas</a>
            <a href="{{ route('admin.donation.manual.create') }}" class="button outline small">+ Donasi Manual</a>
            <a href="{{ route('admin.expenses.create') }}" class="button small">+ Pengeluaran</a>
        </div>
    </div>

    {{-- Filter Periode --}}
    <form method="get" class="card mb-6">
        <div class="report-filters">
            <x-field name="awal" label="Tanggal awal" type="date" :value="$awal" />
            <x-field name="akhir" label="Tanggal akhir" type="date" :value="$akhir" />
            <x-field name="bulan" label="Bulan (opsional)" type="number" min="1" max="12" :value="request('bulan')" />
            <x-field name="tahun" label="Tahun (opsional)" type="number" min="2000" max="2200" :value="request('tahun')" />
        </div>
        <div class="flex items-center gap-3">
            <button class="button small">Hitung Arus Kas</button>
            @if(request()->anyFilled(['awal', 'akhir', 'bulan', 'tahun']))
                <a href="{{ route('admin.cashflow.index') }}" class="button ghost small text-gray-500">Reset ke Semua Periode</a>
            @endif
        </div>
    </form>

    {{-- Ringkasan 4 Kartu Arus Kas --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card bg-gray-50/80 border-gray-200">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-600">[1] Saldo Awal</span>
            <p class="mt-2 text-2xl font-bold text-gray-900">Rp{{ number_format($summary['saldo_awal'], 0, ',', '.') }}</p>
            <p class="text-xs text-gray-500 mt-1">
                @if($summary['start_date'])
                    Posisi per {{ \Carbon\Carbon::parse($summary['start_date'])->format('d/m/Y') }}
                @else
                    Awal pembukuan disahkan
                @endif
            </p>
        </div>

        <div class="card bg-emerald-50/60 border-emerald-200">
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-800">[2] Total Penerimaan (+)</span>
            <p class="mt-2 text-2xl font-bold text-emerald-900">Rp{{ number_format($summary['penerimaan']['total'], 0, ',', '.') }}</p>
            <p class="text-xs text-emerald-700 mt-1">
                {{ $summary['penerimaan']['online_count'] + $summary['penerimaan']['manual_cash_count'] + $summary['penerimaan']['manual_transfer_count'] }} transaksi donasi sah
            </p>
        </div>

        <div class="card bg-amber-50/60 border-amber-200">
            <span class="text-xs font-semibold uppercase tracking-wider text-amber-800">[3] Total Pengeluaran (-)</span>
            <p class="mt-2 text-2xl font-bold text-amber-900">Rp{{ number_format($summary['pengeluaran']['total'], 0, ',', '.') }}</p>
            <p class="text-xs text-amber-700 mt-1">
                {{ $summary['pengeluaran']['count'] }} pengeluaran dibukukan
            </p>
        </div>

        <div class="card bg-emerald-100/70 border-emerald-300">
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-900">[4] Saldo Akhir Kas (=)</span>
            <p class="mt-2 text-2xl font-black text-emerald-950">Rp{{ number_format($summary['saldo_akhir'], 0, ',', '.') }}</p>
            <p class="text-xs text-emerald-800 mt-1 font-medium">Saldo Awal + Masuk - Keluar</p>
        </div>
    </div>

    {{-- Detail Rincian Aliran Dana --}}
    <div class="dashboard-grid">
        <section class="card">
            <h2 class="text-lg font-bold mb-4">Rincian Pos Penerimaan Dana</h2>
            <div class="detail-row">
                <div>
                    <strong>Donasi Online Bersih (Midtrans)</strong>
                    <small class="block text-gray-500">{{ $summary['penerimaan']['online_count'] }} transaksi pembayaran berhasil</small>
                </div>
                <span class="font-semibold text-gray-900">Rp{{ number_format($summary['penerimaan']['online_total'], 0, ',', '.') }}</span>
            </div>
            <div class="detail-row">
                <div>
                    <strong>Donasi Manual: Kas Tunai</strong>
                    <small class="block text-gray-500">{{ $summary['penerimaan']['manual_cash_count'] }} penerimaan tunai kantor panti</small>
                </div>
                <span class="font-semibold text-gray-900">Rp{{ number_format($summary['penerimaan']['manual_cash_total'], 0, ',', '.') }}</span>
            </div>
            <div class="detail-row">
                <div>
                    <strong>Donasi Manual: Transfer Bank Langsung</strong>
                    <small class="block text-gray-500">{{ $summary['penerimaan']['manual_transfer_count'] }} transfer langsung rekening panti</small>
                </div>
                <span class="font-semibold text-gray-900">Rp{{ number_format($summary['penerimaan']['manual_transfer_total'], 0, ',', '.') }}</span>
            </div>
            <div class="detail-row bg-emerald-50/50 p-3 rounded font-bold">
                <span>Total Penerimaan Bersih</span>
                <span class="text-emerald-900">Rp{{ number_format($summary['penerimaan']['total'], 0, ',', '.') }}</span>
            </div>

            <p class="help-text mt-3">Biaya gateway Rp{{ number_format($summary['penerimaan']['biaya_gateway_total'], 0, ',', '.') }} sudah dipotong dari penerimaan online. Jangan catat biaya yang sama sebagai pengeluaran lagi.</p>
            @if($summary['unknown_fee_count'] > 0)
                <p class="help-text mt-3 text-amber-700">
                    Catatan: {{ $summary['unknown_fee_count'] }} transaksi online (termasuk sebelum periode ini) belum memiliki biaya gateway. Transaksi tersebut belum masuk penerimaan bersih dan saldo. Saldo masih sementara sampai biaya dilengkapi.
                </p>
            @endif
        </section>

        <section class="card">
            <h2 class="text-lg font-bold mb-4">Rincian Pengeluaran per Kategori</h2>
            @forelse($summary['pengeluaran']['by_category'] as $cat)
                <div class="detail-row">
                    <div>
                        <strong>{{ $cat['nama'] }}</strong>
                        <small class="block text-gray-500">{{ $cat['count'] }} transaksi dibukukan</small>
                    </div>
                    <span class="font-semibold text-gray-900">Rp{{ number_format($cat['total'], 0, ',', '.') }}</span>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Belum ada pengeluaran dibukukan pada periode ini.</p>
            @endforelse
            <div class="detail-row bg-amber-50/50 p-3 rounded font-bold mt-2">
                <span>Total Pengeluaran Dibukukan</span>
                <span class="text-amber-900">Rp{{ number_format($summary['pengeluaran']['total'], 0, ',', '.') }}</span>
            </div>
        </section>
    </div>

    {{-- Pengaturan Saldo Pembukaan --}}
    <div class="card mt-6" x-data="{ openForm: false }">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold">Saldo Pembukaan Buku Kas</h2>
                <p class="text-xs text-gray-600">
                    @if($opening)
                        Telah disahkan sebesar <strong>Rp{{ number_format($opening->nominal, 0, ',', '.') }}</strong> per tanggal <strong>{{ $opening->tanggal_mulai->format('d/m/Y') }}</strong>
                        @if($opening->ratifier) oleh {{ $opening->ratifier->name }} @endif.
                    @else
                        Belum ada saldo pembukaan disahkan (diasumsikan Rp 0 per tanggal transaksi pertama).
                    @endif
                </p>
            </div>
            <button type="button" @click="openForm = !openForm" class="button outline small">
                <span x-text="openForm ? 'Tutup Form' : 'Atur / Perbarui Saldo Awal'"></span>
            </button>
        </div>

        <div x-show="openForm" x-cloak class="mt-5 pt-4 border-t border-gray-200">
            <form method="post" action="{{ route('admin.cashflow.opening') }}" class="max-w-xl">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-field name="tanggal_mulai" label="Tanggal Mulai Pembukuan" type="date" :value="old('tanggal_mulai', $opening?->tanggal_mulai?->format('Y-m-d') ?? date('Y-01-01'))" required />
                    <x-field name="nominal" label="Nominal Saldo Awal (Rp)" type="number" min="0" step="1" :value="old('nominal', $opening?->nominal ?? 0)" required />
                </div>
                <x-field name="keterangan" label="Keterangan Pengesahan Saldo Awal" type="text" :value="old('keterangan', $opening?->keterangan)" placeholder="Contoh: Sisa kas riil buku kas tahun sebelumnya..." />
                <button type="submit" class="button small mt-2">Simpan & Sahkan Saldo Pembukaan</button>
            </form>
        </div>
    </div>
@endsection
