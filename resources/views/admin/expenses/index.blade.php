@extends('layouts.admin')
@section('title', 'Pengeluaran Dana')
@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Pengeluaran Dana Panti</h1>
            <p class="text-sm text-gray-600">Catat keperluan operasional, kelola berkas bukti kuitansi, dan lakukan pembukuan dana.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.cashflow.index') }}" class="button outline small">Arus Kas & Saldo</a>
            <a href="{{ route('admin.expenses.create') }}" class="button small">+ Tambah Pengeluaran</a>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card bg-emerald-50/50 border-emerald-200">
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Total Dibukukan</span>
            <p class="mt-2 text-2xl font-bold text-emerald-900">Rp{{ number_format($metrics['total_dibukukan'], 0, ',', '.') }}</p>
            <p class="text-xs text-emerald-700 mt-1">Mengurangi saldo kas & tampil di transparansi.</p>
        </div>
        <div class="card bg-amber-50/50 border-amber-200">
            <span class="text-xs font-semibold uppercase tracking-wider text-amber-800">Draft Pengeluaran ({{ $metrics['count_draft'] }})</span>
            <p class="mt-2 text-2xl font-bold text-amber-900">Rp{{ number_format($metrics['total_draft'], 0, ',', '.') }}</p>
            <p class="text-xs text-amber-700 mt-1">Belum dibukukan (belum memotong saldo kas).</p>
        </div>
        <div class="card bg-rose-50/50 border-rose-200">
            <span class="text-xs font-semibold uppercase tracking-wider text-rose-800">Dibatalkan / Koreksi</span>
            <p class="mt-2 text-2xl font-bold text-rose-900">Rp{{ number_format($metrics['total_dibatalkan'], 0, ',', '.') }}</p>
            <p class="text-xs text-rose-700 mt-1">Tidak memotong saldo (terekam di jejak audit).</p>
        </div>
    </div>

    <div class="toolbar">
        <form method="get" class="search-form flex-wrap">
            <input aria-label="Cari kode atau keperluan" name="q" value="{{ request('q') }}" placeholder="Cari kode / keperluan / penerima">
            <select name="kategori" aria-label="Kategori">
                <option value="">Semua kategori</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('kategori') == $cat->id)>{{ $cat->nama }}</option>
                @endforeach
            </select>
            <select name="status" aria-label="Status">
                <option value="">Semua status</option>
                @foreach (['Draft', 'Dibukukan', 'Dibatalkan'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                @endforeach
            </select>
            <input type="date" name="awal" value="{{ request('awal') }}" aria-label="Tanggal awal" title="Dari tanggal">
            <input type="date" name="akhir" value="{{ request('akhir') }}" aria-label="Tanggal akhir" title="Sampai tanggal">
            <button class="button outline small">Filter</button>
            @if(request()->anyFilled(['q', 'kategori', 'status', 'awal', 'akhir']))
                <a href="{{ route('admin.expenses.index') }}" class="button ghost small text-gray-500">Reset</a>
            @endif
        </form>
    </div>

    <div class="card table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Kode & Tanggal</th>
                        <th>Kategori & Keperluan</th>
                        <th>Penerima</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>
                                <strong class="code">{{ $item->kode }}</strong>
                                <small class="block text-gray-500">{{ $item->tanggal->format('d/m/Y') }}</small>
                            </td>
                            <td>
                                <span class="badge text-xs">{{ $item->category?->nama ?? '—' }}</span>
                                <p class="font-medium mt-1">{{ $item->keperluan }}</p>
                            </td>
                            <td>{{ $item->penerima ?: '—' }}</td>
                            <td>
                                <strong class="text-gray-900">Rp{{ number_format($item->nominal, 0, ',', '.') }}</strong>
                            </td>
                            <td>
                                @if($item->isBooked())
                                    <span class="status bg-emerald-100 text-emerald-800">Dibukukan</span>
                                @elseif($item->isDraft())
                                    <span class="status bg-amber-100 text-amber-800">Draft</span>
                                @else
                                    <span class="status bg-rose-100 text-rose-800">Dibatalkan</span>
                                @endif
                            </td>
                            <td>
                                @if($item->bukti_path)
                                    @if($item->is_public)
                                        <span class="inline-flex items-center gap-1 text-xs text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            ✓ Publik (Mobile)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-700 bg-gray-50 px-2 py-0.5 rounded border border-gray-200">
                                            🔒 Privat (Admin)
                                        </span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400">Tanpa berkas</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a class="text-link inline-flex items-center gap-1 text-sm" href="{{ route('admin.expenses.show', $item->id) }}">
                                        Detail
                                    </a>
                                    @if($item->isDraft())
                                        <a class="text-sm text-gray-600 hover:text-gray-900" href="{{ route('admin.expenses.edit', $item->id) }}">
                                            Edit
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-cell">Belum ada catatan pengeluaran sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $items->links() }}</div>
@endsection
