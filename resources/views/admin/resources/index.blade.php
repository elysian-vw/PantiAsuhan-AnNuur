@extends('layouts.admin')
@section('title', $title)
@section('content')
    <div class="toolbar">
        <form method="get" class="search-form"><label class="sr-only" for="q">Cari {{ $title }}</label><input
                id="q" name="q" value="{{ request('q') }}" placeholder="Cari {{ strtolower($title) }}…">
            @if ($resource === 'donatur')
                <select name="jenis" aria-label="Jenis donatur">
                    <option value="">Semua jenis</option>
                    <option value="biasa" @selected(request('jenis') === 'biasa')>Biasa</option>
                    <option value="tetap" @selected(request('jenis') === 'tetap')>Tetap</option>
                </select>
                <select name="verifikasi" aria-label="Status verifikasi">
                    <option value="">Semua status verifikasi</option>
                    <option value="pending" @selected(request('verifikasi') === 'pending')>Kandidat Tetap (Menunggu Verifikasi)</option>
                    <option value="disetujui" @selected(request('verifikasi') === 'disetujui')>Disetujui</option>
                    <option value="ditolak" @selected(request('verifikasi') === 'ditolak')>Ditolak</option>
                </select>
            @endif
            <button class="button outline small">Cari</button>
        </form><a class="button small inline-flex items-center gap-1.5"
            href="{{ route('admin.resource.create', $resource) }}"><svg class="w-4 h-4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg> Tambah data</a>
    </div>
    <div class="card table-card">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nama / judul</th>
                        <th>Informasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td><strong>{{ $item->nama ?? ($item->name ?? $item->judul) }}</strong></td>
                            <td>
                                @if ($resource === 'donatur')
                                    <strong>{{ ucfirst($item->jenis) }}</strong>
                                    <small class="block">{{ $item->email ?: 'Tanpa email' }} · {{ $item->success_donations_count ?? 0 }} donasi berhasil</small>
                                    @if ($item->jenis === 'biasa' && $item->status_verifikasi === 'pending')
                                        <span
                                            class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold rounded bg-amber-100 text-amber-900 border border-amber-300">Kandidat Donatur Tetap (Menunggu Verifikasi)</span>
                                    @elseif ($item->status_verifikasi === 'ditolak')
                                        <span
                                            class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold rounded bg-red-100 text-red-900 border border-red-300">Verifikasi Ditolak</span>
                                    @endif
                                    @if ($item->user?->account_status === 'menunggu')
                                        <span
                                            class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold rounded bg-amber-100 text-amber-900 border border-amber-300">Menunggu
                                            Verifikasi</span>
                                    @elseif ($item->user?->account_status === 'aktif')
                                        <span
                                            class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold rounded bg-green-100 text-green-900">Aktif</span>
                                    @elseif ($item->user?->account_status === 'nonaktif')
                                        <span
                                            class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold rounded bg-red-100 text-red-900">Nonaktif</span>
                                    @endif
                                @else
                                    {{ $item->status ?? ($item->jabatan ?? ($item->email ?? (\Illuminate\Support\Str::limit($item->deskripsi, 80) ?? '—'))) }}
                                @endif
                            </td>
                            <td>
                                <div class="flex gap-4 items-center"><a class="text-link"
                                        href="{{ route('admin.resource.edit', [$resource, $item->id]) }}">Edit</a>
                                    @if ($resource === 'donatur')
                                        <a class="text-link" href="{{ route('admin.donor.account', $item) }}">Akun &
                                            riwayat</a>
                                    @endif
                                    <form method="post"
                                        action="{{ route('admin.resource.destroy', [$resource, $item->id]) }}"
                                        data-confirm="Data '{{ $item->nama ?? ($item->name ?? $item->judul) }}' akan dihapus secara permanen. Data yang masih digunakan tidak dapat dihapus."
                                        data-confirm-title="Hapus {{ $title }}?" data-confirm-btn="Ya, Hapus Data"
                                        data-confirm-type="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-link danger inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="empty-cell">
                                <div class="empty-state">
                                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="empty-title">Belum ada data {{ strtolower($title) }}</p>
                                    <p class="empty-subtitle">Data masih kosong atau tidak ada catatan yang sesuai dengan
                                        kata kunci pencarian.</p>
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
