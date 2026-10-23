@extends('layouts.public')
@section('title', 'Akun donatur')
@section('content')
    <div class="container narrow section"><section class="card">
        <p class="eyebrow">AKUN DONATUR TETAP</p><h1 class="page-title">Halo, {{ auth()->user()->name }}.</h1><x-flash />
        <p>Email: {{ auth()->user()->email }} · Terverifikasi</p>
        <p>Total donasi berhasil milik Anda yang sudah diverifikasi:</p>
        <p class="amount-large">Rp{{ number_format($total, 0, ',', '.') }}</p>
        <p class="help-text">Halaman ini mendukung pemeriksaan akun. Fitur donasi dan monitoring mobile tersedia setelah aplikasi mobile dikembangkan.</p>
        <h2 class="mt-6">Riwayat terverifikasi</h2>
        @forelse ($donations as $donation)
            <div class="detail-row"><strong>{{ $donation->kode }}<small class="block">{{ $donation->created_at->format('d/m/Y') }}</small></strong><span>Rp{{ number_format($donation->nominal, 0, ',', '.') }}<small class="block">{{ $donation->status }}</small></span></div>
        @empty
            <p class="mt-4">Belum ada riwayat yang terhubung. Hubungi pengurus jika Anda memiliki transaksi lama yang perlu diverifikasi.</p>
        @endforelse
        <div class="mt-5">{{ $donations->links() }}</div>
        <form method="post" action="{{ route('logout') }}" class="mt-6">@csrf<button class="button outline">Keluar akun</button></form>
    </section></div>
@endsection
