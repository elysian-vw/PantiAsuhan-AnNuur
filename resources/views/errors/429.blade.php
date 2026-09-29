@extends('errors.layout')

@section('code', '429')
@section('icon_style', 'background: linear-gradient(135deg, #fdf1ed, #f9dbd4); border-color: #f2cdc3; color: #b84332;')
@section('icon')
<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
@endsection
@section('badge_style', 'background:#fdf1ed; border-color:#f2cdc3; color:#8c2a1e;')
@section('badge_text', 'TERLALU BANYAK PERMINTAAN')
@section('title', 'Terlalu banyak permintaan')
@section('description', 'Kamu telah mengirimkan terlalu banyak permintaan dalam waktu singkat. Istirahat sejenak, lalu coba lagi beberapa menit kemudian.')
