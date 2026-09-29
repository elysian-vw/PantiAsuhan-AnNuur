@extends('layouts.admin')
@section('title','Ringkasan hari ini')
@section('content')
<p class="mb-6">Selamat datang, {{ auth()->user()->name }}. Berikut perkembangan pengelolaan panti.</p>
@if($followUp = \App\Models\Setting::read('tindak_lanjut'))<section class="card mb-6"><h2>Perlu dilengkapi</h2><p class="prose-text">{{ $followUp }}</p><a class="text-link" href="{{ route('admin.settings') }}#profil">Perbarui catatan tindak lanjut</a></section>@endif
@if($unknownFees)<div class="notice">{{ $unknownFees }} donasi berhasil belum memiliki informasi biaya. Total bersih hanya menghitung transaksi yang biayanya sudah tercatat.</div>@endif
<div class="stats-grid">@foreach($stats as $label=>$value)<div class="card stat-card"><p>{{ $label }}</p><strong>{{ $value }}</strong></div>@endforeach</div>
<div class="dashboard-grid mt-6"><section class="card"><h2>Donasi bersih bulanan</h2><p class="help-text">Enam bulan terakhir · biaya simulasi mengikuti konfigurasi sandbox</p><div class="chart-wrap"><canvas id="donation-chart" data-chart="{{ $chart->toJson() }}" aria-label="Grafik donasi bulanan" role="img"></canvas></div></section><section class="card"><h2>Menunggu persetujuan</h2>@forelse($visits as $v)<a class="detail-row" href="{{ route('admin.transaction.show',['kunjungan',$v->id]) }}"><div><strong>{{ $v->nama }}</strong><small class="block">{{ $v->tanggal }} · {{ $v->peserta }} peserta</small></div><span><svg class="w-4 h-4 text-emerald-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg></span></a>@empty<p class="mt-5">Tidak ada pengajuan kunjungan yang menunggu.</p>@endforelse</section></div>
@endsection
