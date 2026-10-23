@extends('layouts.public')
@php
    $titles = [
        'profil' => 'Mengenal An-Nuur 2',
        'kontak' => 'Mari terhubung',
        'kegiatan' => 'Kegiatan panti',
        'berita' => 'Berita & informasi',
        'galeri' => 'Galeri kebersamaan',
        'kebutuhan' => 'Kebutuhan panti',
    ];
@endphp
@section('title', $titles[$page])
@section('content')

    @php
        $descriptions = [
            'profil' => 'Rumah yang merawat harapan dan menumbuhkan masa depan anak-anak di Kota Kediri.',
            'kontak' => 'Kami siap menyambut niat baik Anda. Temukan kontak pengurus dan rencanakan pertemuan.',
            'kegiatan' => 'Momen belajar, bertumbuh, dan berbagi kebahagiaan dari rumah kami.',
            'berita' => 'Ikuti kabar terbaru dan informasi yang dibagikan oleh pengurus panti.',
            'galeri' => 'Lebih dekat dengan keseharian kami melalui potret kebersamaan.',
            'kebutuhan' => 'Setiap bantuan punya arti. Temukan kebutuhan yang masih perlu kita lengkapi bersama.',
        ];
    @endphp
    <x-public-heading :title="$titles[$page]" :description="$descriptions[$page]">
        @if ($page === 'profil')
            <a href="#tentang">Tentang kami ↗</a><a href="#sejarah">Sejarah ↗</a><a href="#visi">Visi &amp; misi ↗</a><a
                href="#struktur">Kepengurusan ↗</a>
        @elseif ($page === 'kebutuhan')
            <a href="{{ route('submission.form', 'bantuan') }}">Bantu penuhi kebutuhan ↗</a>
        @elseif (in_array($page, ['kegiatan', 'berita', 'galeri']))
            @foreach (['kegiatan' => 'Kegiatan', 'berita' => 'Berita', 'galeri' => 'Galeri'] as $key => $label)
                <a href="{{ route('page', $key) }}"
                    @if ($key === $page) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        @endif
    </x-public-heading>

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
                    <p class="eyebrow">BERBAGI KEBAIKAN</p>
                    <h2>Dukung kebutuhan panti</h2>
                    <p>Lihat kebutuhan panti dan pilih bantuan yang ingin Anda berikan.</p><a
                        class="button mt-6 inline-flex items-center gap-1.5" href="{{ route('page', 'kebutuhan') }}">Lihat
                        kebutuhan <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
