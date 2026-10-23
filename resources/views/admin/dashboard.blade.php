@extends('layouts.admin')
@section('title', 'Ringkasan hari ini')
@section('content')
    <p class="mb-6">
        Selamat datang, {{ auth()->user()->name }}. Berikut perkembangan pengelolaan panti.</p>
    @if ($followUp = \App\Models\Setting::read('tindak_lanjut'))
        <section class="card mb-6">
            <h2>Perlu dilengkapi</h2>
            <p class="prose-text">{{ $followUp }}</p><a class="text-link"
                href="{{ route('admin.settings') }}#profil">Perbarui catatan tindak lanjut</a>
        </section>
    @endif
    @if (!empty($pendingDonors) && $pendingDonors > 0)
        <div class="notice mb-4 bg-amber-50 border-amber-300 text-amber-950 flex justify-between items-center">
            <div>
                Terdapat <strong>{{ $pendingDonors }}</strong> pendaftaran donatur tetap baru yang menunggu verifikasi dan persetujuan pengurus.
            </div>
            <a class="text-link font-semibold text-sm underline" href="{{ route('admin.resource.index', 'donatur') }}?jenis=tetap">Lihat pendaftar</a>
        </div>
    @endif
    @if ($unknownFees)
        <div class="notice">{{ $unknownFees }} donasi berhasil belum memiliki informasi biaya. Total bersih hanya menghitung
            donasi manual dan transaksi online yang biayanya sudah tercatat.</div>
    @endif
    <div class="stats-grid">
        @foreach ($stats as $label => $value)
            <div class="card stat-card">
                <p>{{ $label }}</p><strong>{{ $value }}</strong>
            </div>
        @endforeach
    </div>
    <div class="dashboard-grid mt-6">
        <section class="card">
            <h2>Donasi bersih bulanan</h2>
            <p class="help-text">Enam bulan terakhir · biaya simulasi mengikuti konfigurasi sandbox</p>
            <div class="chart-wrap"><canvas id="donation-chart" data-chart="{{ $chart->toJson() }}"
                    aria-label="Grafik donasi bulanan" role="img"></canvas></div>
        </section>
        <section class="card">
            <h2>Bantuan menunggu konfirmasi</h2>
            @forelse($pendingAssistance as $v)
                <a class="detail-row" href="{{ route('admin.transaction.show', ['bantuan', $v->id]) }}">
                    <div><strong>{{ $v->nama }}</strong><small class="block">{{ $v->created_at->format('d/m/Y') }} ·
                            {{ $v->items()->count() }} jenis barang</small></div><span><svg
                            class="w-4 h-4 text-emerald-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg></span>
                </a>
            @empty

                <p class="mt-5">Tidak ada pengajuan bantuan yang menunggu.</p>
            @endforelse
        </section>
    </div>
@endsection
