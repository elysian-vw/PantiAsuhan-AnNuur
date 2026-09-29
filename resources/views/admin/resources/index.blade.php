@extends('layouts.admin')
@section('title', $title)
@section('content')
    <div class="toolbar">
        <form method="get" class="search-form"><label class="sr-only" for="q">Cari {{ $title }}</label><input
                id="q" name="q" value="{{ request('q') }}" placeholder="Cari {{ strtolower($title) }}…"><button
                class="button outline small">Cari</button></form><a class="button small inline-flex items-center gap-1.5"
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
                            <td>{{ $item->status ?? ($item->jabatan ?? ($item->email ?? (\Illuminate\Support\Str::limit($item->deskripsi, 80) ?? '—'))) }}
                            </td>
                            <td>
                                <div class="flex gap-4 items-center"><a class="text-link"
                                        href="{{ route('admin.resource.edit', [$resource, $item->id]) }}">Edit</a>
                                    <form method="post"
                                        action="{{ route('admin.resource.destroy', [$resource, $item->id]) }}"
                                        onsubmit="return confirm('Hapus data ini? Data yang masih digunakan tidak dapat dihapus.')">
                                        @csrf @method('DELETE')<button class="text-link danger">Hapus</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="empty-cell">Belum ada data. Tambahkan melalui tombol di atas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $items->links() }}</div>
@endsection
