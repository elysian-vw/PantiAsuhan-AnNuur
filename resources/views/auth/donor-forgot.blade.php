@extends('layouts.public')
@section('title', 'Pemulihan akun donatur')
@section('content')
    <div class="container login-wrap"><section class="card">
        <p class="eyebrow">PEMULIHAN AKUN</p><h1 class="page-title">Lupa password?</h1>
        <p>Masukkan email yang sudah diverifikasi pada akun donatur Anda. Kami akan memproses tautan untuk membuat password baru.</p><x-flash />
        <form method="post" action="{{ route('donor.password.email') }}">@csrf
            <x-field name="email" label="Email terdaftar" type="email" autocomplete="email" required />
            <button class="button w-full mt-4">Kirim tautan pemulihan</button>
        </form>
        <a class="text-link inline-block mt-5" href="{{ route('donor.login') }}">Kembali ke login</a>
    </section></div>
@endsection
