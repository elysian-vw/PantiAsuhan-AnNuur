@extends('layouts.admin')
@section('title', 'Detail Pengeluaran ' . $expense->kode)
@section('content')
    <a class="text-link mb-5 inline-flex items-center gap-1.5" href="{{ route('admin.expenses.index') }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg> Kembali ke daftar pengeluaran
    </a>

    <div class="dashboard-grid">
        <section class="card">
            <div class="flex items-center justify-between mb-4">
                <span class="code text-sm font-semibold">{{ $expense->kode }}</span>
                @if($expense->isBooked())
                    <span class="status bg-emerald-100 text-emerald-800">Dibukukan</span>
                @elseif($expense->isDraft())
                    <span class="status bg-amber-100 text-amber-800">Draft</span>
                @else
                    <span class="status bg-rose-100 text-rose-800">Dibatalkan</span>
                @endif
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $expense->keperluan }}</h1>
            <p class="text-sm text-gray-500 mb-4">{{ $expense->category?->nama ?? 'Tanpa Kategori' }} · Tanggal: {{ $expense->tanggal->format('d/m/Y') }}</p>

            <div class="my-5 p-4 rounded-lg bg-gray-50 border border-gray-100">
                <span class="text-xs uppercase tracking-wider text-gray-500 font-medium">Nominal Pengeluaran</span>
                <p class="text-3xl font-extrabold text-emerald-900 mt-1">Rp{{ number_format($expense->nominal, 0, ',', '.') }}</p>
            </div>

            <div class="detail-row">
                <strong>Pihak Penerima / Toko</strong>
                <span>{{ $expense->penerima ?: '—' }}</span>
            </div>
            <div class="detail-row">
                <strong>Dicatat Oleh</strong>
                <span>{{ $expense->creator?->name ?? 'Pengurus' }}</span>
            </div>
            @if($expense->isBooked() && $expense->poster)
                <div class="detail-row">
                    <strong>Dibukukan Oleh</strong>
                    <span>{{ $expense->poster->name }} ({{ $expense->posted_at?->format('d/m/Y H:i') }} WIB)</span>
                </div>
            @endif
            @if($expense->isCancelled())
                <div class="detail-row text-rose-800">
                    <strong>Dibatalkan Oleh</strong>
                    <span>{{ $expense->canceller?->name ?? 'Pengurus' }} ({{ $expense->cancelled_at?->format('d/m/Y H:i') }} WIB)</span>
                </div>
                <div class="mt-3 p-3 bg-rose-50 border border-rose-200 rounded text-sm text-rose-900">
                    <strong>Alasan Pembatalan:</strong>
                    <p class="mt-1">{{ $expense->cancellation_reason }}</p>
                </div>
            @endif

            <div class="detail-row mt-4">
                <strong>Berkas Bukti / Kuitansi</strong>
                <div>
                    @if($expense->bukti_path)
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.expenses.download', $expense->id) }}" class="button outline small">
                                ⬇ Unduh Bukti
                            </a>
                            @if($expense->is_public)
                                <span class="text-xs text-emerald-700 bg-emerald-50 px-2 py-1 rounded border border-emerald-200">
                                    ✓ Terpublikasi ke donatur mobile
                                </span>
                            @else
                                <span class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded border border-gray-200">
                                    🔒 Privat (Hanya pengurus)
                                </span>
                            @endif
                        </div>
                    @else
                        <span class="text-gray-400">Tidak ada berkas bukti fisik diunggah.</span>
                    @endif
                </div>
            </div>

            @if($expense->keterangan)
                <div class="mt-5 pt-4 border-t border-gray-200">
                    <strong class="text-xs uppercase text-gray-500">Keterangan Tambahan:</strong>
                    <p class="prose-text mt-1 text-sm text-gray-700">{{ $expense->keterangan }}</p>
                </div>
            @endif

            <hr class="my-6">
            <h3 class="text-base font-semibold mb-3">Jejak Perubahan Status</h3>
            <ol class="timeline">
                @foreach ($expense->histories()->orderBy('id')->get() as $history)
                    <li>
                        <strong>{{ $history->ke }}</strong>
                        <small>{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->user?->name ?? $history->sumber }}</small>
                        @if($history->catatan)
                            <p class="mt-1 text-sm text-gray-600">{{ $history->catatan }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>

        <aside class="space-y-4">
            @if($expense->isDraft())
                <div class="card bg-amber-50/50 border-amber-200">
                    <h2 class="text-lg font-bold text-amber-950 mb-2">Tindakan Draft</h2>
                    <p class="text-xs text-amber-800 mb-4">Pengeluaran ini masih berstatus <strong>Draft</strong> dan belum mengurangi saldo kas panti.</p>

                    <form method="post" action="{{ route('admin.expenses.post', $expense->id) }}" class="mb-3">
                        @csrf
                        <button type="submit" class="button w-full" onclick="return confirm('Apakah Anda yakin ingin membukukan pengeluaran ini? Setelah dibukukan, nominal akan memotong saldo kas panti.')">
                            ✓ Bukukan Pengeluaran Sekarang
                        </button>
                    </form>

                    <a href="{{ route('admin.expenses.edit', $expense->id) }}" class="button outline w-full mb-3 text-center">
                        Edit Draft
                    </a>

                    <form method="post" action="{{ route('admin.expenses.destroy', $expense->id) }}" onsubmit="return confirm('Hapus draft pengeluaran ini secara permanen?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="button ghost w-full text-rose-600 hover:bg-rose-50">
                            Hapus Draft
                        </button>
                    </form>
                </div>
            @elseif($expense->isBooked())
                <div class="card border-rose-200">
                    <h2 class="text-lg font-bold text-rose-950 mb-2">Koreksi / Pembatalan</h2>
                    <p class="text-xs text-gray-600 mb-4">Pengeluaran yang sudah dibukukan tidak boleh dihapus secara diam-diam. Untuk koreksi atau salah input, batalkan transaksi dengan menyertakan alasan sah.</p>

                    <form method="post" action="{{ route('admin.expenses.cancel', $expense->id) }}" onsubmit="return confirm('Batalkan pembukuan pengeluaran ini? Nominal akan dikembalikan ke saldo kas riil.')">
                        @csrf
                        <div class="field">
                            <label for="alasan">Alasan Pembatalan / Catatan Koreksi <span class="text-rose-500">*</span></label>
                            <textarea id="alasan" name="alasan" rows="3" required placeholder="Contoh: Kesalahan input nominal, kuitansi diganti nomor baru..."></textarea>
                            @error('alasan')
                                <small class="error-text">{{ $message }}</small>
                            @enderror
                        </div>
                        <button type="submit" class="button outline w-full text-rose-600 border-rose-300 hover:bg-rose-50 mt-3">
                            Batalkan Pengeluaran Ini
                        </button>
                    </form>
                </div>
            @else
                <div class="card bg-gray-50 border-gray-200">
                    <h2 class="text-base font-semibold text-gray-700 mb-1">Status Final</h2>
                    <p class="text-xs text-gray-500">Pengeluaran ini telah dibatalkan dan tidak lagi mempengaruhi perhitungan saldo kas panti. Data tetap tersimpan untuk keperluan audit historis.</p>
                </div>
            @endif
        </aside>
    </div>
@endsection
