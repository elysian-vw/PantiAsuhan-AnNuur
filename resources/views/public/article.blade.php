@extends('layouts.public')
@section('title',$item->judul)
@section('content')
<article class="section container narrow"><a class="text-link" href="{{ route('page',$page) }}">← Kembali ke {{ $page }}</a><p class="eyebrow mt-8">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }} @if($item->lokasi) · {{ $item->lokasi }} @endif</p><h1 class="page-title">{{ $item->judul }}</h1>@if($item->foto)<img class="article-hero" src="{{ asset('storage/'.$item->foto) }}" alt="{{ $item->judul }}">@endif<p class="prose-text mt-8">{{ $item->deskripsi }}</p>@if($page==='kegiatan')<div class="card-grid mt-8">@foreach($item->photos as $photo)<img class="rounded-xl" src="{{ asset('storage/'.$photo->foto) }}" alt="{{ $photo->judul }}" loading="lazy">@endforeach</div>@endif</article>
@endsection
