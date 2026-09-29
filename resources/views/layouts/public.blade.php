<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#1a5336">
        <title>@yield('title', 'Beranda') · An-Nuur 2</title>

        {{-- Meta Description --}}
        <meta name="description" content="@yield('og_description', 'Informasi Panti Asuhan NU An-Nuur 2 Kota Kediri — donasi, bantuan barang, dan kunjungan.')">

        {{-- Open Graph (Facebook, WhatsApp, Telegram, dll.) --}}
        <meta property="og:site_name" content="Panti Asuhan NU An-Nuur 2">
        <meta property="og:locale" content="id_ID">
        <meta property="og:type" content="@yield('og_type', 'website')">
        <meta property="og:title" content="@yield('og_title', config('app.name', 'An-Nuur 2'))">
        <meta property="og:description" content="@yield('og_description', 'Informasi Panti Asuhan NU An-Nuur 2 Kota Kediri — donasi, bantuan barang, dan kunjungan.')">
        <meta property="og:url" content="{{ url()->current() }}">
        @hasSection('og_image')
            <meta property="og:image" content="@yield('og_image')">
            <meta property="og:image:width" content="1200">
            <meta property="og:image:height" content="630">
        @endif

        {{-- Twitter / X Card --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="@yield('og_title', config('app.name', 'An-Nuur 2'))">
        <meta name="twitter:description" content="@yield('og_description', 'Informasi Panti Asuhan NU An-Nuur 2 Kota Kediri — donasi, bantuan barang, dan kunjungan.')">
        @hasSection('og_image')
            <meta name="twitter:image" content="@yield('og_image')">
        @endif

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
            rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body>
        <a class="skip-link" href="#main">Langsung ke isi</a>
        <div class="topline">
            <div class="container flex justify-between items-center gap-3"><span>Panti Asuhan NU An-Nuur 2 · Kota
                    Kediri</span><span class="hidden sm:inline-flex items-center gap-1.5"><svg
                        class="w-3.5 h-3.5 text-emerald-300" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg> Bersama merawat harapan</span></div>
        </div>
        <header class="site-header" x-data="{ open: false }" @keydown.escape.window="open = false">
            <div class="container header-inner">
                <a href="{{ route('home') }}" class="brand" aria-label="An-Nuur 2, beranda"><span class="brand-mark"
                        aria-hidden="true">ن</span><span><strong>AN-NUUR 2</strong><small>PANTI ASUHAN ·
                            KEDIRI</small></span></a>
                <nav class="desktop-nav" aria-label="Navigasi utama">
                    @foreach (['home' => 'Beranda', 'profil' => 'Profil', 'kegiatan' => 'Kegiatan', 'kebutuhan' => 'Kebutuhan', 'kunjungan' => 'Kunjungan'] as $key => $label)
                        <a class="{{ request()->path() === ($key === 'home' ? '/' : $key) ? 'active' : '' }}"
                            href="{{ $key === 'home' ? route('home') : ($key === 'kunjungan' ? route('submission.form', $key) : route('page', $key)) }}">{{ $label }}</a>
                    @endforeach
                </nav>
                <a href="{{ route('submission.form', 'donasi') }}" class="button small header-donate">Donasi <svg
                        class="w-3.5 h-3.5 ml-1 transition-transform group-hover:translate-x-0.5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M5 12h14M12 5l7 7-7 7" />
                    </svg></a>
                <button type="button" class="menu-toggle" @click="open = !open" :aria-expanded="open"
                    aria-controls="mobile-menu" aria-label="Buka atau tutup menu">
                    <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="open" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav id="mobile-menu" class="mobile-nav container" x-show="open" x-cloak aria-label="Navigasi mobile">
                <a href="{{ route('home') }}">Beranda</a>
                @foreach (['profil' => 'Profil Panti', 'kegiatan' => 'Kegiatan', 'berita' => 'Berita', 'galeri' => 'Galeri', 'kebutuhan' => 'Kebutuhan Panti', 'kontak' => 'Kontak'] as $key => $label)
                    <a href="{{ route('page', $key) }}">{{ $label }}</a>
                @endforeach
                <a href="{{ route('submission.form', 'bantuan') }}">Bantuan Barang</a><a
                    href="{{ route('submission.form', 'kunjungan') }}">Kunjungan</a>
            </nav>
        </header>
        <main id="main">@yield('content')</main>
        <footer class="site-footer">
            <div class="container footer-grid">
                <div><a href="{{ route('home') }}" class="brand"><span class="brand-mark"
                            aria-hidden="true">ن</span><span><strong>AN-NUUR 2</strong><small>PANTI ASUHAN ·
                                KEDIRI</small></span></a>
                    <p class="mt-5 max-w-sm">Setiap perhatian berarti. Bersama menghadirkan ruang untuk tumbuh dan
                        meraih harapan bagi anak-anak panti.</p>
                </div>
                <div><strong>Kenali kami</strong><a href="{{ route('page', 'profil') }}">Profil panti</a><a
                        href="{{ route('page', 'berita') }}">Berita</a><a
                        href="{{ route('page', 'galeri') }}">Galeri</a><a
                        href="{{ route('page', 'kontak') }}">Kontak
                        & lokasi</a></div>
                <div><strong>Mari berpartisipasi</strong><a href="{{ route('submission.form', 'donasi') }}">Donasi
                        uang</a><a href="{{ route('submission.form', 'bantuan') }}">Bantuan barang</a><a
                        href="{{ route('submission.form', 'kunjungan') }}">Rencanakan kunjungan</a></div>
                <div><strong>Waktu kunjungan</strong>
                    <p>07.00–21.00 WIB<br>Melalui persetujuan pengurus.</p><a href="{{ route('login') }}"
                        class="inline-flex items-center gap-1">Masuk pengurus <svg class="w-3.5 h-3.5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg></a>
                </div>
            </div>
            <div class="container footer-bottom">© {{ date('Y') }} Panti Asuhan NU An-Nuur 2 Kota Kediri · Amanah
                & Transparan</div>
        </footer>
    </body>

</html>
