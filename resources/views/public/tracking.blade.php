@extends('layouts.public')
@section('title', 'Riwayat ' . ucfirst($kind))
@section('content')
    <div class="container narrow section">
        <p class="eyebrow">TAUTAN PRIBADI</p>
        <h1 class="page-title">Perkembangan {{ $kind }}</h1><x-flash />
        <section class="card">
            <div class="flex flex-wrap justify-between gap-3"><span class="code">{{ $record->kode }}</span><span
                    class="status">{{ $record->status }}</span></div>
            <h2 class="mt-5">Terima kasih, {{ $record->nama }}.</h2>
            @if ($kind === 'donasi')
                <p class="amount-large">Rp{{ number_format($record->nominal, 0, ',', '.') }}</p>
                <p>{{ $record->category?->nama }}</p>
                @if ($record->status === 'Menunggu pembayaran')
                    @if (config('panti.midtrans_key'))
                        <form action="{{ route('payment', $token) }}" method="post" class="mt-6">@csrf<button
                                class="button inline-flex items-center gap-1.5">Buka pembayaran sandbox <svg class="w-4 h-4"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg></button></form>@else<div class="notice mt-6">Pengurus belum mengaktifkan layanan
                            pembayaran sandbox. Donasi belum dibayar; data pengajuan Anda sudah tersimpan.</div>
                    @endif
                @endif
            @elseif($kind === 'bantuan')
                <div class="mt-6">
                    @foreach ($record->items as $item)
                        <div class="detail-row"><strong>{{ $item->nama }}</strong><span>{{ (float) $item->jumlah }}
                                {{ $item->satuan }}</span></div>
                    @endforeach
                </div>
                <p class="mt-5">Tanggal diterima: {{ $record->tanggal_diterima ?: 'Belum diterima' }}</p>
            @else<div class="detail-row">
                    <strong>Jadwal</strong><span>{{ $record->tanggal }}<br>{{ substr($record->jam, 0, 5) }}–{{ substr($record->jam_selesai, 0, 5) }}
                        WIB</span>
                </div>
                <div class="detail-row"><strong>Peserta</strong><span>{{ $record->peserta }} orang</span></div>
                <p class="mt-5">{{ $record->tujuan }}</p>
                @if ($record->status === 'Dijadwalkan ulang')
                    <div class="notice">Hubungi pengurus melalui WhatsApp untuk memberikan persetujuan atas jadwal baru.
                    </div>
                @endif
            @endif
            <hr>
            <h3>Riwayat status</h3>
            <ol class="timeline">
                @foreach ($record->histories()->orderBy('id')->get() as $history)
                    <li><strong>{{ $history->ke }}</strong><small>{{ $history->created_at->format('d/m/Y H:i') }}
                            WIB</small>
                        @if ($history->catatan)
                            <p>{{ $history->catatan }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
        <p class="help-text mt-5">Simpan alamat halaman ini. Tautan ini membuka data pribadi pengajuan Anda.</p><a
            class="button outline mt-4 inline-flex items-center gap-2" href="{{ route('tracking', [$kind, $token]) }}"><svg
                class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>Perbarui status</a>
    </div>
@endsection
