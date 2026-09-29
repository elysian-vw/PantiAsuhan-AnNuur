@extends('errors.layout')

@section('code', '500')
@section('icon_style', 'background: linear-gradient(135deg, #fdf1ed, #f9dbd4); border-color: #f2cdc3; color: #b84332;')
@section('icon')
<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
@endsection
@section('badge_style', 'background:#fdf1ed; border-color:#f2cdc3; color:#8c2a1e;')
@section('badge_text', 'KESALAHAN SERVER')
@section('title', 'Ups, ada masalah di server')
@section('description', 'Server kami mengalami gangguan sementara. Tim kami sedang menangani hal ini. Silakan coba beberapa saat lagi atau kembali ke beranda.')
@section('extra_action')
    <a href="javascript:location.reload()" class="btn btn-outline">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
        Coba lagi
    </a>
@endsection
