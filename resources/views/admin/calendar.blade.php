@extends('layouts.admin')
@section('title', 'Jadwal kunjungan')
@section('content')
    <p class="mb-5">Pilih kunjungan untuk melihat detail. Warna kuning menandai jadwal ulang yang menunggu konfirmasi.</p>
    <div class="card">
        <div id="visit-calendar" data-events="{{ $events->toJson() }}"></div>
    </div>
@endsection
