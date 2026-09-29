@extends('layouts.public')
@php($titles = ['profil' => 'Mengenal An-Nuur 2', 'kontak' => 'Mari terhubung', 'kegiatan' => 'Kegiatan panti', 'berita' => 'Berita & informasi', 'galeri' => 'Galeri kebersamaan', 'kebutuhan' => 'Kebutuhan panti'])
@section('title', $titles[$page])
@section('content')

    {{-- PAGE HEADING --}}
    @if ($page === 'profil')
        <section class="page-heading profil-heading">
            <div class="profil-heading-bg" aria-hidden="true">
                <span class="ph-orb ph-orb-1"></span>
                <span class="ph-orb ph-orb-2"></span>
                <span class="ph-grid"></span>
            </div>
            <div class="container profil-heading-inner">
                <div class="profil-heading-badge">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Panti Asuhan NU An-Nuur 2</span>
                </div>
                <h1>Mengenal<br><em>An-Nuur 2</em></h1>
                <p>Rumah yang merawat harapan dan menumbuhkan masa depan anak-anak di Kota Kediri.</p>
                <div class="profil-heading-anchors">
                    <a href="#tentang">Tentang kami</a>
                    <a href="#sejarah">Sejarah</a>
                    <a href="#visi">Visi &amp; Misi</a>
                    <a href="#struktur">Struktur</a>
                </div>
            </div>
        </section>
    @else
        <section class="page-heading">
            <div class="container">
                <p class="eyebrow">PANTI ASUHAN NU AN-NUUR 2</p>
                <h1>{{ $titles[$page] }}</h1>
                <p>Informasi dan kabar dari rumah kami di Kota Kediri.</p>
            </div>
        </section>
    @endif

    {{-- PAGE CONTENT --}}
    <div class="section container">
        @if ($page === 'profil')

            @include('public.partials.profil-cards')

            @include('public.partials.photo-profile')

            <div class="profil-org-header" id="struktur">
                <div class="profil-org-header-inner">
                    <span class="profil-org-badge">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        STRUKTUR ORGANISASI
                    </span>
                    <h2 class="profil-org-title">Kepengurusan panti</h2>
                    <p class="profil-org-period">Periode
                        {{ \App\Models\Setting::read('periode_pengurus') ?: 'belum dicantumkan' }}</p>
                </div>
            </div>

            @include('public.partials.org-tree')
        @elseif($page === 'kontak')
            <div class="profile-grid">
                <section class="card">
                    <h2>Kontak &amp; lokasi</h2>
                    @foreach (['alamat' => 'Alamat', 'telepon' => 'Telepon', 'email' => 'Email'] as $key => $label)
                        <div class="mt-5"><strong>{{ $label }}</strong>
                            <p>{{ \App\Models\Setting::read($key) ?: 'Belum dicantumkan oleh pengurus.' }}</p>
                        </div>
                    @endforeach
                    @if ($map = \App\Models\Setting::read('maps_url'))
                        <a class="button mt-6 inline-flex items-center gap-1.5" href="{{ $map }}" target="_blank"
                            rel="noopener noreferrer">Lihat lokasi <svg class="w-3.5 h-3.5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg></a>
                    @endif
                </section>
                <section class="card soft">
                    <p class="eyebrow">SILATURAHMI</p>
                    <h2>Rencanakan kunjungan</h2>
                    <p>Kunjungan berlangsung pukul 07.00–21.00 WIB, sesuai jadwal yang disetujui pengurus.</p><a
                        class="button mt-6 inline-flex items-center gap-1.5"
                        href="{{ route('submission.form', 'kunjungan') }}">Isi formulir kunjungan <svg class="w-3.5 h-3.5"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg></a>
                </section>
            </div>
        @else
            <div class="card-grid">
                @forelse($items as $item)
                    @if ($page === 'kebutuhan')
                        @include('public.partials.need')
                    @elseif($page === 'galeri')
                        <figure class="card article-card">
                            <img src="{{ asset('storage/' . $item->foto) }}" alt="{{ $item->judul }}" loading="lazy">
                            <figcaption class="card-body">
                                <h3>{{ $item->judul }}</h3>
                                <p>{{ $item->deskripsi }}</p>
                            </figcaption>
                        </figure>
                    @else
                        @include('public.partials.article-card')
                    @endif
                @empty


                    <div class="empty-state wide">
                        <span class="empty-icon"><svg class="w-10 h-10 mx-auto text-emerald-800/40" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg></span>
                        <h2>Belum ada informasi yang ditampilkan</h2>
                        <p>Silakan kembali untuk melihat pembaruan dari pengurus.</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-8">{{ $items->links() }}</div>
        @endif

    </div>
@endsection
