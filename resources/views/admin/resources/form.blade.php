@extends('layouts.admin')
@section('title', ($item->exists ? 'Edit ' : 'Tambah ') . $title)
@section('content')
    <form method="post" enctype="multipart/form-data"
        action="{{ $item->exists ? route('admin.resource.update', [$resource, $item->id]) : route('admin.resource.store', $resource) }}"
        class="card max-w-3xl">
        @csrf @if ($item->exists)
            @method('PUT')
        @endif
        @foreach ($fields as $name => $field)
            @php([$label, $type, $rules] = $field)
            @if (in_array($type, ['select', 'relation']))
                <div class="field"><label for="{{ $name }}">{{ $label }}</label><select
                        id="{{ $name }}" name="{{ $name }}">
                        <option value="">Pilih…</option>@php($options = $type === 'select' ? $field[3] : $field[3]::all()->mapWithKeys(fn($r) => [$r->id => $r->nama ?? $r->judul])->all())@foreach ($options as $value => $text)
                            <option value="{{ $value }}" @selected((string) old($name, $item->$name) === (string) $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif($type === 'file')
                <div class="field"><label for="{{ $name }}">{{ $label }}</label><input type="file"
                        id="{{ $name }}" name="{{ $name }}" accept="image/jpeg,image/png,image/webp">
                    @if ($item->$name)
                        <img src="{{ asset('storage/' . $item->$name) }}" class="preview-image" alt="Foto saat ini">
                    @endif
                </div>
            @else
                <x-field :name="$name" :label="$label" :type="$type" :value="$item->$name ?? ''" :step="$type === 'number' ? 'any' : null" />
                @if ($type === 'password')
                    <x-field name="password_confirmation" label="Ulangi password" type="password"
                        autocomplete="new-password" />
                    <p class="help-text">Saat mengedit, kosongkan untuk mempertahankan password.</p>
                @endif
            @endif
        @endforeach
        <div class="flex flex-wrap gap-3 mt-6"><button class="button">Simpan perubahan</button><a class="button outline"
                href="{{ route('admin.resource.index', $resource) }}">Batal</a></div>
    </form>
@endsection
