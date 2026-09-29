@extends('errors.layout')

@section('code', '404')
@section('card_style', 'border-top: 4px solid var(--green);')
@section('icon_style', 'background: linear-gradient(135deg, #eef7ea, #d8edcf); border-color: #c2dec0; color: var(--green);')
@section('icon')
<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803M10.5 7.5v6m3-3h-6"/></svg>
@endsection
@section('badge_style', 'background:#edf7e8; border-color:#c5dfc2; color:var(--green);')
@section('badge_text', 'HALAMAN TIDAK DITEMUKAN')
@section('title', 'Halaman tidak ditemukan')
@section('description', 'Halaman yang kamu cari tidak ada, sudah dipindah, atau URL yang dimasukkan tidak tepat. Periksa kembali tautan yang kamu gunakan.')
@section('extra_action')
    <a href="javascript:history.back()" class="btn btn-outline">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali
    </a>
@endsection
