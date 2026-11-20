@extends('layouts.public')
@section('title', 'Masuk akun donatur')
@section('content')
    <div class="container login-wrap">
        <section class="card">
            <p class="eyebrow">DONATUR TETAP</p>
            <h1 class="page-title">Masuk akun donatur.</h1>
            <p>Untuk berdonasi dan memantau transparansi keuangan panti, silakan gunakan <strong>Aplikasi Mobile An-Nuur 2</strong>. Halaman ini mendukung pemeriksaan akun setelah aktivasi atau pemulihan password melalui email.</p><x-flash />
            <form method="post" action="{{ route('donor.login.submit') }}">@csrf
                <x-field name="email" label="Email" type="email" autocomplete="username" required />
                <x-field name="password" label="Password" type="password" autocomplete="current-password" required />
                <button class="button w-full mt-4">Masuk akun</button>
            </form>
            <a class="text-link inline-block mt-5" href="{{ route('donor.password.request') }}">Lupa password?</a>
        </section>
    </div>
@endsection
