@extends('layouts.admin')
@section('title', 'Akun ' . $donor->nama)
@section('content')
    <div class="toolbar">
        <a class="text-link" href="{{ route('admin.resource.index', 'donatur') }}">Kembali ke data donatur</a>
        <a class="button outline small" href="{{ route('admin.resource.edit', ['donatur', $donor->id]) }}">Edit data
            donatur</a>
    </div>
    <section class="card mb-6">
        <h2>{{ $donor->nama }}</h2>
        <p class="mt-4">Jenis: <strong>{{ ucfirst($donor->jenis) }}</strong></p>
        <p>WhatsApp: {{ $donor->whatsapp }}</p>
        <p>Email: {{ $donor->email ?: 'Belum diisi' }}</p>
        <p>Riwayat Donasi Berhasil: <strong>{{ $successCount ?? 0 }} transaksi</strong> (Total Rp{{ number_format($successTotal ?? 0, 0, ',', '.') }})</p>
        <p>Status Verifikasi Donatur Tetap:
            @if ($donor->status_verifikasi === 'pending')
                <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded bg-amber-100 text-amber-900 border border-amber-300">Menunggu Verifikasi Pengurus</span>
            @elseif ($donor->status_verifikasi === 'disetujui' || $donor->jenis === 'tetap')
                <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded bg-green-100 text-green-900 border border-green-300">Disetujui (Donatur Tetap)</span>
            @elseif ($donor->status_verifikasi === 'ditolak')
                <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded bg-red-100 text-red-900 border border-red-300">Ditolak</span>
            @else
                <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded bg-slate-100 text-slate-700 border border-slate-300">Belum Diajukan</span>
            @endif
        </p>
        @if ($donor->alasan_penolakan)
            <p class="text-xs text-rose-700 mt-1">Alasan penolakan: {{ $donor->alasan_penolakan }}</p>
        @endif
        @if ($donor->user)
            <p>Status akun:
                @if ($donor->user->account_status === 'menunggu')
                    <span
                        class="inline-block px-2.5 py-0.5 text-xs font-bold rounded bg-amber-100 text-amber-900 border border-amber-300">Menunggu
                        Verifikasi</span>
                @elseif ($donor->user->account_status === 'aktif')
                    <span
                        class="inline-block px-2.5 py-0.5 text-xs font-bold rounded bg-green-100 text-green-900 border border-green-300">Aktif</span>
                @else
                    <span
                        class="inline-block px-2.5 py-0.5 text-xs font-bold rounded bg-red-100 text-red-900 border border-red-300">Nonaktif</span>
                @endif
            </p>
            <p>Email terverifikasi:
                {{ $donor->user->email_verified_at ? $donor->user->email_verified_at->format('d/m/Y H:i') . ' WIB' : 'Belum diverifikasi' }}
            </p>

            @if ($donor->jenis === 'tetap' && $donor->user->account_status === 'menunggu')
                <div class="mt-4 p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-950">
                    <p class="font-semibold text-sm">Pendaftaran Menunggu Persetujuan</p>
                    <p class="text-xs mt-1">Calon donatur telah mendaftar melalui aplikasi mobile/web. Silakan verifikasi
                        kebenaran identitas sebelum mengaktifkan akun.</p>
                </div>
            @endif

            <div class="flex flex-wrap gap-3 mt-5">
                @if ($donor->jenis === 'tetap' && $donor->user->account_status === 'menunggu')
                    <form method="post" action="{{ route('admin.donor.approve', $donor) }}"
                        data-confirm="Tautan aktivasi akun donatur akan dikirimkan ke email {{ $donor->user->email }}. Donatur dapat membuka tautan tersebut untuk membuat kata sandi."
                        data-confirm-title="Setujui & Kirim Aktivasi?" data-confirm-btn="Ya, Setujui & Kirim"
                        data-confirm-type="success">
                        @csrf
                        {{-- blade-formatter-disable --}}
                        <button type="submit" class="button bg-green-600 hover:bg-green-700 text-white font-medium">Setujui &amp; Kirim Aktivasi</button>
                        {{-- blade-formatter-enable --}}
                    </form>
                    <p class="help-text">Akun aktif setelah donatur membuka tautan email dan menetapkan kata sandi.</p>
                @endif
                <form method="post" action="{{ route('admin.donor.access', $donor) }}"
                    data-confirm="{{ $donor->user->account_status === 'nonaktif' ? 'Akses akun donatur ini akan diaktifkan kembali sehingga donatur dapat masuk ke aplikasi.' : 'Akses akun donatur ini akan dinonaktifkan sehingga donatur tidak dapat masuk ke aplikasi.' }}"
                    data-confirm-title="{{ $donor->user->account_status === 'nonaktif' ? 'Aktifkan Kembali Akses?' : 'Nonaktifkan Akses Donatur?' }}"
                    data-confirm-btn="{{ $donor->user->account_status === 'nonaktif' ? 'Ya, Aktifkan' : 'Ya, Nonaktifkan' }}"
                    data-confirm-type="{{ $donor->user->account_status === 'nonaktif' ? 'success' : 'danger' }}">
                    @csrf
                    <input type="hidden" name="access"
                        value="{{ $donor->user->account_status === 'nonaktif' ? 'aktif' : 'nonaktif' }}">
                    @if ($donor->jenis === 'tetap' || $donor->user->account_status !== 'nonaktif')
                        <button type="submit"
                            class="button outline {{ $donor->user->account_status === 'nonaktif' ? 'text-emerald-700 border-emerald-300 hover:bg-emerald-50' : 'text-rose-700 border-rose-300 hover:bg-rose-50' }}">{{ $donor->user->account_status === 'nonaktif' ? 'Aktifkan kembali akses' : 'Nonaktifkan / Tolak akses' }}</button>
                    @endif
                </form>
            </div>
            @if (in_array(config('mail.default'), ['log', 'array']))
                <p class="help-text mt-4">Email masih dalam mode pengujian lokal. Undangan belum dikirim ke kotak masuk.</p>
            @endif
        @else
            <p class="mt-4">Donatur biasa berdonasi melalui aplikasi mobile atau web tanpa akun. Verifikasi riwayat donasi di bawah untuk menetapkan sebagai Donatur Tetap.</p>
        @endif

        @if ($donor->jenis === 'biasa')
            <div class="mt-6 pt-5 border-t">
                <h3 class="font-semibold text-base">Verifikasi Perubahan Menjadi Donatur Tetap</h3>
                <p class="text-sm mt-1">
                    @if ($donor->status_verifikasi === 'pending')
                        Donatur biasa ini telah menyelesaikan donasi dan otomatis masuk ke antrean kandidat Donatur Tetap.
                    @else
                        Anda dapat memverifikasi donatur biasa ini menjadi Donatur Tetap berdasarkan riwayat donasinya.
                    @endif
                </p>
                <form method="post" action="{{ route('admin.donor.promote', $donor) }}" class="mt-4 space-y-4">
                    @csrf
                    <label class="field" for="promote-email">Email Akun Donatur Tetap (opsional bila hanya mengubah status tanpa membuat akun login)
                        <input type="email" id="promote-email" name="email" value="{{ old('email', $donor->email) }}" placeholder="contoh@donatur.com">
                    </label>
                    <label class="field" for="promote-reason">Catatan Verifikasi Pengurus (opsional)
                        <input type="text" id="promote-reason" name="reason" value="{{ old('reason', 'Diverifikasi oleh pengurus berdasarkan riwayat donasi berhasil.') }}">
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="verify_history" value="1" checked>
                        <span>Sekaligus tautkan seluruh riwayat donasi pada profil ini ke akun donatur tetap (bila email diisi).</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="send_invite" value="1" checked>
                        <span>Kirim email undangan aktivasi akun (bila email diisi).</span>
                    </label>
                    <div class="flex flex-wrap gap-3 mt-3">
                        <button type="submit" class="button bg-green-600 hover:bg-green-700 text-white font-medium">Setujui Jadi Donatur Tetap</button>
                    </div>
                </form>

                @if ($donor->status_verifikasi === 'pending')
                    <form method="post" action="{{ route('admin.donor.reject-promotion', $donor) }}" class="mt-5 pt-4 border-t">
                        @csrf
                        <label class="field" for="reject-reason">Alasan Penolakan (jika belum memenuhi syarat Donatur Tetap)
                            <input type="text" id="reject-reason" name="reason" required minlength="5" placeholder="Tuliskan alasan penolakan...">
                        </label>
                        <button type="submit" class="button outline text-rose-700 border-rose-300 hover:bg-rose-50 mt-2">Tolak Pengajuan</button>
                    </form>
                @endif
            </div>
        @endif
    </section>
    <section class="card mb-6">
        <h2>Verifikasi riwayat donasi</h2>
        <p>Nomor WhatsApp yang sama belum membuktikan kepemilikan transaksi. Verifikasi dengan donatur dan catat dasar
            pemeriksaan sebelum menautkan donasi ke akun.</p>
        @forelse ($donations as $donation)
            <div class="mt-6 border-t pt-5">
                <p>
                    <strong>{{ $donation->kode }}</strong> · Rp{{ number_format($donation->nominal, 0, ',', '.') }} ·
                    {{ $donation->status }}
                </p>
                <p class="text-xs text-slate-600 mt-1">
                    Atas nama: <strong>{{ $donation->nama }}</strong> · {{ $donation->created_at?->format('d/m/Y H:i') }} WIB
                </p>
                <a class="text-link" href="{{ route('admin.transaction.show', ['donasi', $donation->id]) }}">Lihat detail
                    transaksi</a>
                @if ($donation->owner_user_id)
                    <p class="help-text mt-3">Kepemilikan sudah diverifikasi pada {{ $donation->ownership_verified_at }}.
                    </p>
                @elseif ($donor->jenis === 'tetap' && $donor->user_id)
                    <form method="post" action="{{ route('admin.donor.verify', [$donor, $donation]) }}" class="mt-4">
                        @csrf
                        <label class="field" for="reason-{{ $donation->id }}">Dasar verifikasi
                            <textarea name="reason" id="reason-{{ $donation->id }}" required minlength="10" maxlength="2000"></textarea>
                        </label>
                        <label class="checkbox-label"><input type="checkbox" name="confirmed" value="1"
                                required><span>Saya telah memverifikasi bahwa transaksi ini milik donatur
                                tersebut.</span></label>
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
                    <small>{{ $audit->created_at }} WIB ·
                        {{ $audit->actor_user_id ? 'Pengurus #' . $audit->actor_user_id : 'Pemilik akun' }}</small>
                    <p class="prose-text break-words">{{ $audit->details }}</p>
                </li>
            @empty
                <li>Belum ada aktivitas pengelolaan akun.</li>
            @endforelse
        </ol>
    </section>
@endsection
