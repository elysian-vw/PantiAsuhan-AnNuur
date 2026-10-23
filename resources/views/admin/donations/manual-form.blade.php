@extends('layouts.admin')
@section('title', 'Catat Donasi Manual')
@section('content')
    <a class="text-link mb-5 inline-flex items-center gap-1.5" href="{{ route('admin.transaction.index', 'donasi') }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg> Kembali ke daftar donasi
    </a>

    <div class="card max-w-3xl">
        <h1 class="text-2xl font-bold mb-1">Catat Penerimaan Donasi Manual</h1>
        <p class="text-sm text-gray-600 mb-6">Gunakan formulir ini untuk mencatat donasi yang diterima langsung di kantor panti (tunai) atau transfer langsung ke rekening bank panti di luar payment gateway Midtrans.</p>

        <form method="post" action="{{ route('admin.donation.manual.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-field name="nama" label="Nama Donatur / Hamba Allah" type="text" :value="old('nama')" placeholder="Nama lengkap donatur" required />
                <x-field name="whatsapp" label="Nomor WhatsApp" type="tel" :value="old('whatsapp')" placeholder="Contoh: 081234567890" required />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-field name="email" label="Email Donatur (Opsional)" type="email" :value="old('email')" placeholder="donatur@example.com" />

                <div class="field">
                    <label for="kategori_donasi_id">Kategori Donasi <span class="text-rose-500">*</span></label>
                    <select name="kategori_donasi_id" id="kategori_donasi_id" required>
                        <option value="">Pilih Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('kategori_donasi_id') == $cat->id)>
                                {{ $cat->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('kategori_donasi_id')
                        <small class="error-text">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-field name="nominal" label="Nominal Diterima (Rp)" type="number" min="5000" step="1" :value="old('nominal')" placeholder="Min. Rp5.000" required />

                <div class="field">
                    <label for="metode">Metode Penerimaan <span class="text-rose-500">*</span></label>
                    <select name="metode" id="metode" required>
                        <option value="tunai" @selected(old('metode') === 'tunai')>Tunai (Kas Langsung)</option>
                        <option value="transfer" @selected(old('metode') === 'transfer')>Transfer Bank Langsung</option>
                    </select>
                    @error('metode')
                        <small class="error-text">{{ $message }}</small>
                    @enderror
                </div>

                <x-field name="tanggal_diterima" label="Tanggal Diterima" type="date" :value="old('tanggal_diterima', date('Y-m-d'))" :max="date('Y-m-d')" required />
            </div>

            <x-field name="referensi_manual" label="Nomor Referensi / Nomor Kuitansi Fisik (Opsional)" type="text" :value="old('referensi_manual')" placeholder="Contoh: KWT-2026/10/012 atau Ref Bank 987654" />

            <div class="field my-4 p-4 rounded-lg border border-gray-200 bg-gray-50/50">
                <label for="bukti">Berkas Bukti / Scan Kuitansi / Bukti Transfer (Opsional)</label>
                <input type="file" id="bukti" name="bukti" accept=".jpg,.jpeg,.png,.webp,.pdf">
                <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, WebP, PDF; maksimal 5 MB.</p>
                @error('bukti')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </div>

            <x-field name="catatan" label="Catatan Tambahan (Opsional)" type="textarea" :value="old('catatan')" placeholder="Keterangan peruntukan atau doa dari donatur..." />

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="button">Simpan Penerimaan Donasi</button>
                <a href="{{ route('admin.transaction.index', 'donasi') }}" class="button outline">Batal</a>
            </div>
        </form>
    </div>
@endsection
