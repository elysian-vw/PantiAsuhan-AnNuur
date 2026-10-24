@extends('layouts.admin')
@section('title',
    [
    'donasi' => 'Donasi uang',
    'bantuan' => 'Bantuan barang',
    ][$kind])
@section('content')
    <div class="toolbar">
        <form method="get" class="search-form flex-wrap"><input aria-label="Cari nama atau kode" name="q"
                value="{{ request('q') }}" placeholder="Cari nama / kode"><select name="status" aria-label="Status">
                <option value="">Semua status</option>
                @foreach (match ($kind) {
            'donasi' => ['Menunggu pembayaran', 'Berhasil', 'Gagal', 'Kedaluwarsa', 'Dibatalkan'],
            'bantuan' => ['Diajukan', 'Dikonfirmasi', 'Diterima', 'Ditolak'],
        } as $status)
                    <option @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <button class="button outline small">Terapkan</button>
        </form>
        @if ($kind === 'donasi')
            <a href="{{ route('admin.donation.manual.create') }}" class="button small">+ Catat Donasi Manual</a>
        @endif
    </div>
    <div class="card table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nama & referensi</th>
                        <th>{{ $kind === 'donasi' ? 'Nominal & Sumber' : 'Tanggal' }}</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td><strong>{{ $item->nama }}</strong><small class="block code">{{ $item->kode }}</small>
                            </td>
                            <td>
                                @if ($kind === 'donasi')
                                    <strong>Rp{{ number_format($item->nominal, 0, ',', '.') }}</strong>
                                    <small class="block text-gray-500">
                                        {{ $item->isManual() ? 'Manual (' . ucfirst($item->metode) . ')' : 'Online (Midtrans)' }}
                                    </small>
                                @else
                                    {{ $item->tanggal_diterima ?? $item->created_at->format('Y-m-d') }}
                                @endif
                            </td>
                            <td><span class="status">{{ $item->status }}</span></td>
                            <td><a class="text-link inline-flex items-center gap-1"
                                    href="{{ route('admin.transaction.show', [$kind, $item->id]) }}">Detail <svg
                                        class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg></a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-cell">
                                <div class="empty-state">
                                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="empty-title">Belum ada transaksi
                                        {{ $kind === 'donasi' ? 'donasi uang' : 'bantuan barang' }}</p>
                                    <p class="empty-subtitle">Belum ada data transaksi yang sesuai dengan filter atau kata
                                        kunci pencarian Anda.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $items->links() }}</div>
@endsection
