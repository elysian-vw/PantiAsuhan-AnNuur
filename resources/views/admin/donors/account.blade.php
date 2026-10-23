@extends('layouts.admin')
@section('title', 'Akun ' . $donor->nama)
@section('content')
    <div class="toolbar">
        <a class="text-link" href="{{ route('admin.resource.index', 'donatur') }}">Kembali ke data donatur</a>
        <a class="button outline small" href="{{ route('admin.resource.edit', ['donatur', $donor->id]) }}">Edit data donatur</a>
    </div>
    <section class="card mb-6">
        <h2>{{ $donor->nama }}</h2>
        <p class="mt-4">Jenis: <strong>{{ ucfirst($donor->jenis) }}</strong></p>
        <p>Email: {{ $donor->email ?: 'Belum diisi' }}</p>
        @if ($donor->user)
            <p>Status akun: 
                @if ($donor->user->account_status === 'menunggu')
                    <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded bg-amber-100 text-amber-900 border border-amber-300">Menunggu Verifikasi</span>
                @elseif ($donor->user->account_status === 'aktif')
                    <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded bg-green-100 text-green-900 border border-green-300">Aktif</span>
                @else
                    <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded bg-red-100 text-red-900 border border-red-300">Nonaktif</span>
                @endif
            </p>
            <p>Email terverifikasi: {{ $donor->user->email_verified_at ? $donor->user->email_verified_at->format('d/m/Y H:i') . ' WIB' : 'Belum diverifikasi' }}</p>
            
            @if ($donor->jenis === 'tetap' && $donor->user->account_status === 'menunggu')
                <div class="mt-4 p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-950">
                    <p class="font-semibold text-sm">Pendaftaran Menunggu Persetujuan</p>
                    <p class="text-xs mt-1">Calon donatur telah mendaftar melalui aplikasi mobile/web. Silakan verifikasi kebenaran identitas sebelum mengaktifkan akun.</p>
                </div>
            @endif

            <div class="flex flex-wrap gap-3 mt-5">
                @if ($donor->jenis === 'tetap' && $donor->user->account_status === 'menunggu')
                    <form method="post" action="{{ route('admin.donor.approve', $donor) }}" onsubmit="return confirm('Setujui dan kirim tautan aktivasi ke email donatur ini?')">@csrf
                        <button class="button bg-green-600 hover:bg-green-700 text-white font-medium">Setujui &amp; Kirim Aktivasi</button>
                    </form>
                    <p class="help-text">Akun aktif setelah donatur membuka tautan email dan menetapkan kata sandi.</p>
                @endif
                <form method="post" action="{{ route('admin.donor.access', $donor) }}">@csrf
                    <input type="hidden" name="access" value="{{ $donor->user->account_status === 'nonaktif' ? 'aktif' : 'nonaktif' }}">
                    @if ($donor->jenis === 'tetap' || $donor->user->account_status !== 'nonaktif')
                        <button class="button outline">{{ $donor->user->account_status === 'nonaktif' ? 'Aktifkan kembali akses' : 'Nonaktifkan / Tolak akses' }}</button>
                    @endif
                </form>
            </div>
            @if (in_array(config('mail.default'), ['log', 'array']))
                <p class="help-text mt-4">Email masih dalam mode pengujian lokal. Undangan belum dikirim ke kotak masuk.</p>
            @endif
        @else
            <p class="mt-4">Donatur biasa berdonasi melalui web tanpa akun. Pilih jenis tetap dan isi email untuk menyiapkan akun.</p>
        @endif
    </section>
    <section class="card mb-6">
        <h2>Verifikasi riwayat donasi</h2>
        <p>Nomor WhatsApp yang sama belum membuktikan kepemilikan transaksi. Verifikasi dengan donatur dan catat dasar pemeriksaan sebelum menautkan donasi ke akun.</p>
        @forelse ($donations as $donation)
            <div class="mt-6 border-t pt-5">
                <p><strong>{{ $donation->kode }}</strong> · Rp{{ number_format($donation->nominal, 0, ',', '.') }} · {{ $donation->status }}</p>
                <a class="text-link" href="{{ route('admin.transaction.show', ['donasi', $donation->id]) }}">Lihat detail transaksi</a>
                @if ($donation->owner_user_id)
                    <p class="help-text mt-3">Kepemilikan sudah diverifikasi pada {{ $donation->ownership_verified_at }}.</p>
                @elseif ($donor->jenis === 'tetap' && $donor->user_id)
                    <form method="post" action="{{ route('admin.donor.verify', [$donor, $donation]) }}" class="mt-4">@csrf
                        <label class="field" for="reason-{{ $donation->id }}">Dasar verifikasi
                            <textarea name="reason" id="reason-{{ $donation->id }}" required minlength="10" maxlength="2000"></textarea>
                        </label>
                        <label class="checkbox-label"><input type="checkbox" name="confirmed" value="1" required><span>Saya telah memverifikasi bahwa transaksi ini milik donatur tersebut.</span></label>
                        <button class="button outline small mt-4">Verifikasi dan tautkan</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="mt-5">Belum ada transaksi pada data donatur ini.</p>
        @endforelse
        <div class="mt-5">{{ $donations->links() }}</div>
    </section>
    <section class="card">
        <h2>Jejak pengelolaan akun</h2>
        <ol class="timeline">
            @forelse ($audits as $audit)
                <li><strong>{{ str_replace('_', ' ', $audit->action) }}</strong>
                    <small>{{ $audit->created_at }} WIB · {{ $audit->actor_user_id ? 'Pengurus #' . $audit->actor_user_id : 'Pemilik akun' }}</small>
                    <p class="prose-text break-words">{{ $audit->details }}</p>
                </li>
            @empty
                <li>Belum ada aktivitas pengelolaan akun.</li>
            @endforelse
        </ol>
    </section>
@endsection
