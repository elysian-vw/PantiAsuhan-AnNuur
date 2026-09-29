@extends('layouts.admin')
@section('title', 'Catat tamu langsung')
@section('content')
    <form class="card max-w-3xl" method="post" action="{{ route('admin.walkin.store') }}">@csrf<p class="mb-5">Tamu yang
            datang langsung dicatat dengan status Hadir.</p>
        <div class="form-grid"><x-field name="nama" label="Nama penanggung jawab" required /><x-field name="whatsapp"
                label="Nomor WhatsApp" type="tel" required /></div>@include('public.partials.visit-fields')<label
            class="checkbox-label"><input type="checkbox" name="consent" value="1" required><span>Tamu menyetujui
                pencatatan data kunjungan.</span></label><button class="button mt-6">Catat ke buku tamu</button>
    </form>
@endsection
