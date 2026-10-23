@extends('layouts.public')
@section('title', 'Tetapkan password donatur')
@section('content')
    <div class="container login-wrap"><section class="card">
        <p class="eyebrow">AKUN DONATUR TETAP</p><h1 class="page-title">Tetapkan password Anda.</h1>
        <p>Gunakan tautan dari email untuk aktivasi akun atau pemulihan password. Password minimal 12 karakter.</p><x-flash />
        <form method="post" action="{{ route('donor.password.update') }}">@csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-field name="email" label="Email terdaftar" type="email" :value="$email" autocomplete="email" required />
            <x-field name="password" label="Password baru" type="password" autocomplete="new-password" minlength="12" maxlength="200" required />
            <x-field name="password_confirmation" label="Ulangi password baru" type="password" autocomplete="new-password" required />
            <button class="button w-full mt-4">Simpan password</button>
        </form>
    </section></div>
@endsection
