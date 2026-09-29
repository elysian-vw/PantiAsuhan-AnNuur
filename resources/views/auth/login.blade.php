@extends('layouts.public')
@section('title', 'Masuk pengurus')
@section('content')
    <div class="container login-wrap">
        <div class="card">
            <p class="eyebrow">RUANG PENGURUS</p>
            <h1 class="page-title">Selamat datang kembali.</h1>
            <p class="mb-6">Masuk untuk mengelola informasi dan layanan panti.</p><x-flash />
            <form action="{{ route('login.submit') }}" method="post">@csrf<x-field name="email" label="Email" type="email"
                    autocomplete="username" required /><x-field name="password" label="Password" type="password"
                    autocomplete="current-password" required /><button
                    class="button w-full mt-4 inline-flex items-center justify-center gap-2"><span>Masuk dashboard</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg></button></form>
        </div>
    </div>
@endsection
