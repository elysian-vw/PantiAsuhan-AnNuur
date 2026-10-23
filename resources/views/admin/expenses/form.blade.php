@extends('layouts.admin')
@section('title', $expense->exists ? 'Edit Draft Pengeluaran' : 'Tambah Pengeluaran')
@section('content')
    <a class="text-link mb-5 inline-flex items-center gap-1.5" href="{{ route('admin.expenses.index') }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg> Kembali ke daftar pengeluaran
    </a>

    <div class="card max-w-3xl">
        <h1 class="text-2xl font-bold mb-1">{{ $expense->exists ? 'Edit Draft Pengeluaran (' . $expense->kode . ')' : 'Catat Pengeluaran Baru' }}</h1>
        <p class="text-sm text-gray-600 mb-6">Pastikan tanggal, keperluan, dan nominal sesuai bukti transaksi fisik panti.</p>

        <form method="post" action="{{ $expense->exists ? route('admin.expenses.update', $expense->id) : route('admin.expenses.store') }}" enctype="multipart/form-data">
            @csrf
            @if($expense->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-field name="tanggal" label="Tanggal Pengeluaran" type="date" :value="old('tanggal', $expense->tanggal?->format('Y-m-d') ?? date('Y-m-d'))" required />

                <div class="field">
                    <label for="kategori_pengeluaran_id">Kategori Pengeluaran <span class="text-rose-500">*</span></label>
                    <select name="kategori_pengeluaran_id" id="kategori_pengeluaran_id" required>
                        <option value="">Pilih Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('kategori_pengeluaran_id', $expense->kategori_pengeluaran_id) == $cat->id)>
                                {{ $cat->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('kategori_pengeluaran_id')
                        <small class="error-text">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <x-field name="keperluan" label="Keperluan / Keterangan Belanja" type="text" :value="old('keperluan', $expense->keperluan)" placeholder="Contoh: Belanja beras 50kg, telur, dan minyak goreng santri" required />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-field name="nominal" label="Nominal (Rp)" type="number" min="1" step="1" :value="old('nominal', $expense->nominal)" placeholder="Contoh: 750000" required />
                <x-field name="penerima" label="Pihak Penerima / Toko" type="text" :value="old('penerima', $expense->penerima)" placeholder="Contoh: Toko Barokah / PLN" />
            </div>

            <x-field name="keterangan" label="Catatan Tambahan (Opsional)" type="textarea" :value="old('keterangan', $expense->keterangan)" placeholder="Catatan internal pengurus..." />

            <div class="field my-4 p-4 rounded-lg border border-gray-200 bg-gray-50/50">
                <label for="bukti">Berkas Bukti / Struk / Kuitansi (JPG, PNG, WebP, PDF; maks 5 MB)</label>
                @if($expense->bukti_path)
                    <div class="mb-2 text-xs text-gray-700 flex items-center gap-2">
                        <span>Berkas saat ini tersimpan: <strong>{{ basename($expense->bukti_path) }}</strong></span>
                        <a href="{{ route('admin.expenses.download', $expense->id) }}" class="text-link text-xs" target="_blank">Unduh berkas</a>
                    </div>
                @endif
                <input type="file" id="bukti" name="bukti" accept=".jpg,.jpeg,.png,.webp,.pdf">
                @error('bukti')
                    <small class="error-text">{{ $message }}</small>
                @enderror

                <div class="mt-4 pt-3 border-t border-gray-200">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="is_public" value="1" class="mt-1" @checked(old('is_public', $expense->is_public))>
                        <div>
                            <span class="font-medium text-gray-900">Publikasikan bukti ini ke donatur tetap di mobile</span>
                            <p class="text-xs text-gray-500">Centang jika berkas struk/kuitansi aman dari data pribadi sensitif (seperti nomor rekening pribadi atau tanda tangan rahasia) dan layak dilihat oleh donatur tetap pada menu transparansi.</p>
                        </div>
                    </label>
                </div>
            </div>

            @if(!$expense->exists || $expense->isDraft())
                <div class="mb-6 p-4 rounded-lg border border-emerald-200 bg-emerald-50/40">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="langsung_dibukukan" value="1" class="mt-1" @checked(old('langsung_dibukukan'))>
                        <div>
                            <span class="font-medium text-emerald-950">Langsung bukukan pengeluaran ini sekarang</span>
                            <p class="text-xs text-emerald-800">Jika dicentang, transaksi akan berstatus <strong>Dibukukan</strong> dan langsung mengurangi saldo kas riil. Jika tidak dicentang, transaksi tersimpan sebagai <strong>Draft</strong> untuk diperiksa kembali.</p>
                        </div>
                    </label>
                </div>
            @endif

            <div class="flex items-center gap-3">
                <button type="submit" class="button">
                    {{ $expense->exists ? 'Simpan Perubahan Draft' : 'Simpan Pengeluaran' }}
                </button>
                <a href="{{ route('admin.expenses.index') }}" class="button outline">Batal</a>
            </div>
        </form>
    </div>
@endsection
