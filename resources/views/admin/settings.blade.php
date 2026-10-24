@extends('layouts.admin')
@section('title', 'Profil & pengaturan')
@section('content')
    <div class="dashboard-grid">
        <form id="profil" class="card" method="post" action="{{ route('admin.settings.save') }}">@csrf @method('PUT')
            <h2 class="mb-6">Profil panti & website</h2>
            @foreach ($fields as $key => $label)
                {{-- blade-formatter-disable --}}
                <x-field :name="$key" :label="$label" :type="in_array($key, [
                    'tentang',
                    'sejarah',
                    'visi',
                    'misi',
                    'alamat',
                    'pendiri',
                    'rekap_penerima',
                    'tata_tertib',
                    'sumber_profil',
                    'tindak_lanjut',
                ])
                    ? 'textarea'
                    : ($key === 'penerima_manfaat'
                        ? 'number'
                        : ($key === 'email'
                            ? 'email'
                            : ($key === 'maps_url'
                                ? 'url'
                                : 'text')))" :value="$values[$key] ?? ''" />
                {{-- blade-formatter-enable --}}
            @endforeach
            <button class="button mt-5">
                Simpan profil</button>
        </form>
        <section id="integrasi" class="card self-start">
            <p class="eyebrow">KONFIGURASI LOKAL</p>
            <h2>Integrasi layanan</h2>
            <div class="detail-row"><strong>Midtrans</strong><span>Sandbox ·
                    {{ config('panti.midtrans_key') ? 'Credential terisi' : 'Belum dikonfigurasi' }}</span></div>
            <div class="detail-row"><strong>WhatsApp
                    Fonnte</strong><span>{{ config('panti.whatsapp_enabled') && config('panti.fonnte_token') ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
            <p class="mt-5">Credential diatur melalui file .env oleh pengelola aplikasi. Secret tidak ditampilkan atau
                disimpan melalui formulir ini.</p>
            <p class="help-text mt-4">Callback: <code>{{ route('midtrans.webhook') }}</code>. Saat pengujian, gunakan URL
                HTTPS tunnel yang mengarah ke endpoint ini.</p>
            <p class="help-text mt-4">Biaya sandbox:
                {{ config('panti.simulated_fee') === null ? 'belum ditentukan' : 'Rp' . config('panti.simulated_fee') . ' (simulasi)' }}.
            </p>
        </section>
    </div>
    <section class="card table-card mt-6">
        <div class="p-6">
            <h2>Log notifikasi WhatsApp</h2>
            <p class="help-text">Diterima provider tidak berarti pesan sudah dibaca penerima.</p>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Tujuan</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->tujuan }}</td>
                            <td>{{ $log->jenis }}</td>
                            <td>{{ $log->status }}</td>
                            <td>{{ $log->keterangan }}</td>
                        </tr>
                    @empty

                        <tr>
                            <td colspan="5" class="empty-cell">
                                <div class="empty-state">
                                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <p class="empty-title">Belum ada riwayat notifikasi</p>
                                    <p class="empty-subtitle">Log pengiriman notifikasi WhatsApp otomatis akan tercatat di
                                        sini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-6">{{ $logs->links() }}</div>
@endsection
