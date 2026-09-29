@extends('errors.layout')

@section('code', '403')
@section('icon_style', 'background: linear-gradient(135deg, #fdf6e8, #f8e8c5); border-color: #e8d08a; color: #9a7225;')
@section('icon')
<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
@endsection
@section('badge_style', 'background:#fdf6e8; border-color:#e8d8a8; color:#7a5518;')
@section('badge_text', 'AKSES DITOLAK')
@section('title', 'Kamu tidak memiliki akses')
@section('description', 'Halaman ini hanya dapat diakses oleh pengguna dengan hak akses tertentu. Jika kamu merasa ini keliru, silakan hubungi pengurus.')
@section('extra_action')
    <a href="/kontak" class="btn btn-outline">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
        Hubungi pengurus
    </a>
@endsection
