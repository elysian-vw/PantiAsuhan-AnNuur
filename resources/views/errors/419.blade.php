@extends('errors.layout')

@section('code', '419')
@section('icon_style', 'background: linear-gradient(135deg, #fdf6e8, #f8e8c5); border-color: #e8d08a; color: #9a7225;')
@section('icon')
<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
@endsection
@section('badge_style', 'background:#fdf6e8; border-color:#e8d8a8; color:#7a5518;')
@section('badge_text', 'SESI KEDALUWARSA')
@section('title', 'Sesi formulir kedaluwarsa')
@section('description', 'Token keamanan formulir sudah tidak berlaku karena sesi terlalu lama tidak aktif. Muat ulang halaman dan coba kirim formulir lagi.')
@section('extra_action')
    <a href="javascript:location.reload()" class="btn btn-outline">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
        Muat ulang halaman
    </a>
@endsection
