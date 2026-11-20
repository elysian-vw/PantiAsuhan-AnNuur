@extends('layouts.public')
@php(
    $title = match ($kind) {
        'donasi' => 'Donasi melalui Aplikasi Mobile',
        'bantuan' => 'Kebaikan dalam setiap pemberian'
    }
)
@section('title', ucfirst($kind))
@section('content')
    <x-public-heading :title="$title" :eyebrow="['donasi' => 'DONASI UANG · APLIKASI MOBILE', 'bantuan' => 'BANTUAN BARANG'][$kind]" :description="$kind === 'donasi' ? 'Layanan donasi uang difokuskan melalui Aplikasi Mobile An-Nuur 2. Anda dapat langsung berdonasi tanpa perlu membuat akun terlebih dahulu.' : 'Isi formulir di bawah ini untuk mengajukan bantuan barang. Tidak perlu membuat akun.'">
        @foreach (['donasi' => 'Donasi uang (Mobile)', 'bantuan' => 'Bantuan barang'] as $key => $label)
            <a href="{{ route('submission.form', $key) }}"
                @if ($key === $kind) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </x-public-heading>
    <div class="container form-layout section">
        <div><x-flash />
            @if ($kind === 'donasi')
                <div class="card form-card">
                    <div class="form-section">
                        <span class="step-number">📱</span>
                        <h2>Gunakan Aplikasi Mobile An-Nuur 2 untuk Berdonasi</h2>
                    </div>
                    <p class="text-base leading-7 text-[#374a40]">
                        Untuk memberikan pengalaman berdonasi yang lebih aman, praktis, dan transparan, layanan <strong>Donasi Uang</strong> kini difokuskan sepenuhnya melalui <strong>Aplikasi Mobile Panti Asuhan NU An-Nuur 2</strong>.
                    </p>

                    <div class="mt-6 grid gap-4">
                        <div class="rounded-2xl border border-[#dce1d3] bg-[#f7f8f3] p-5">
                            <strong class="block text-base text-[#173f2e]">1. Langsung Donasi Tanpa Login (Donatur Biasa)</strong>
                            <p class="mt-1 mb-0! text-sm leading-6 text-[#52635a]">
                                Buka aplikasi mobile dan langsung pilih menu <em>Donasi Sekarang</em> mulai dari Rp5.000 tanpa harus mendaftar atau login terlebih dahulu. Riwayat donasi Anda tersimpan otomatis di perangkat Anda.
                            </p>
                        </div>
                        <div class="rounded-2xl border border-[#dce1d3] bg-[#f7f8f3] p-5">
                            <strong class="block text-base text-[#173f2e]">2. Verifikasi Menjadi Donatur Tetap oleh Pengurus</strong>
                            <p class="mt-1 mb-0! text-sm leading-6 text-[#52635a]">
                                Setelah donasi Anda berhasil, data Anda otomatis masuk ke antrean verifikasi Pengurus Panti. Anda juga dapat mendaftarkan akun di aplikasi menggunakan nomor WhatsApp yang sama untuk diangkat menjadi <strong>Donatur Tetap</strong>.
                            </p>
                        </div>
                        <div class="rounded-2xl border border-[#dce1d3] bg-[#f7f8f3] p-5">
                            <strong class="block text-base text-[#173f2e]">3. Pantau Transparansi & Laporan Keuangan Panti</strong>
                            <p class="mt-1 mb-0! text-sm leading-6 text-[#52635a]">
                                Lihat rekapitulasi pemasukan, pengeluaran, dan dokumentasi penyaluran dana panti secara langsung dari aplikasi mobile.
                            </p>
                        </div>
                    </div>

                    <hr class="my-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <strong class="block text-sm text-[#173f2e]">Ingin menyumbangkan barang kebutuhan panti?</strong>
                            <span class="text-xs text-[#5e6f66]">Pengajuan bantuan barang tetap dapat dilakukan melalui website ini.</span>
                        </div>
                        <a href="{{ route('submission.form', 'bantuan') }}" class="button outline small">Ajukan Bantuan Barang →</a>
                    </div>
                </div>
            @else
                <form action="{{ route('submission.store', $kind) }}" method="post" class="card form-card" x-data="{ submitting: false }"
                    @submit="submitting=true">
                    @csrf
                    <div class="form-section"><span class="step-number">01</span>
                        <h2>Data pemberi bantuan</h2>
                    </div>
                    <div class="form-grid"><x-field name="nama" label="Nama lengkap" autocomplete="name" maxlength="150"
                            required /><x-field name="whatsapp" label="Nomor WhatsApp" type="tel" inputmode="tel"
                            autocomplete="tel" placeholder="081234567890" required /></div>
                    <x-field name="email" label="Email (opsional)" type="email" autocomplete="email" />
                    <hr>
                    <div class="form-section"><span class="step-number">02</span>
                        <h2>Barang yang akan diberikan</h2>
                    </div>
                    <div x-data="assistanceItems({{ Js::from(old('items', [['nama' => '', 'kategori_bantuan_id' => '', 'jumlah' => 1, 'satuan' => '']])) }})">
                        <template x-for="(item,index) in items" :key="item.key">
                            <div class="item-form">
                                <div class="flex justify-between items-center mb-4"><strong
                                        x-text="'Barang '+(index+1)"></strong><button type="button"
                                        class="text-link danger inline-flex items-center gap-1" @click="remove(index)"
                                        x-show="items.length>1"><svg class="w-3.5 h-3.5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg> Hapus</button></div>
                                <div class="form-grid"><label class="field">Nama barang *<input
                                            :name="'items[' + index + '][nama]'" x-model="item.nama" maxlength="150"
                                            required></label><label class="field">Kategori *<select
                                            :name="'items[' + index + '][kategori_bantuan_id]'"
                                            x-model="item.kategori_bantuan_id" required>
                                            <option value="">Pilih kategori</option>
                                            @foreach ($assistanceCategories as $category)
                                                <option value="{{ $category->id }}">{{ $category->nama }}</option>
                                            @endforeach
                                        </select></label><label class="field">Jumlah *<input type="number" min="0.01"
                                            max="999999999" step="0.01" :name="'items[' + index + '][jumlah]'"
                                            x-model="item.jumlah" required></label><label class="field">Satuan *<input
                                            :name="'items[' + index + '][satuan]'" x-model="item.satuan"
                                            placeholder="kg, liter, buah..." maxlength="40" required></label></div>
                            </div>
                        </template><button type="button" class="button outline small inline-flex items-center gap-1.5"
                            @click="add()" :disabled="items.length >= 30"><svg class="w-4 h-4" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg> Tambah barang</button>
                    </div>
                    <x-field class="mt-6" name="catatan" label="Catatan (opsional)" type="textarea" maxlength="2000" />
                    <hr><label class="checkbox-label"><input type="checkbox" name="consent" value="1" required
                            @checked(old('consent'))><span>Saya menyetujui penggunaan data ini oleh pengurus untuk memproses
                            {{ $kind }} dan mengirim pemberitahuan melalui WhatsApp.</span></label>
                    <button class="button w-full mt-6" type="submit" :disabled="submitting"
                        x-text="submitting ? 'Menyimpan…' : 'Kirim pengajuan →'">Kirim pengajuan →</button>
                </form>
            @endif
        </div>
        <aside class="form-aside">
            <p class="eyebrow">KEBAIKAN ANDA BERARTI</p>
            <h2>Langkah kecil,<br>arti yang besar.</h2>
            @if ($kind === 'donasi')
                <p>Layanan donasi uang tersedia melalui <strong>Aplikasi Mobile An-Nuur 2</strong> untuk memudahkan pembayaran digital dan pencatatan riwayat otomatis.</p>
                <div class="notice">Anda dapat berdonasi tanpa perlu login terlebih dahulu melalui Aplikasi Mobile.</div>
            @elseif($kind === 'bantuan')
                <p>Pengurus akan memeriksa dan mengonfirmasi bantuan Anda. Tanggal penerimaan dicatat setelah barang
                    benar-benar diterima.</p>
                <div class="aside-divider"></div>
                <h3>Simpan tautan pribadi Anda</h3>
                <p>Setelah mengirim formulir, simpan tautan halaman riwayat untuk memeriksa perkembangan. Jangan membagikannya
                    kepada orang lain.</p>
            @endif
            <div class="aside-divider"></div>
            <a class="text-link inline-flex items-center gap-1"
                href="{{ route('page', 'kontak') }}">Hubungi pengurus <svg class="w-3.5 h-3.5" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg></a>
        </aside>
    </div>
@endsection
