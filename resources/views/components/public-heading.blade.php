@props(['title', 'eyebrow' => 'PANTI ASUHAN NU AN-NUUR 2', 'description' => null])
<section class="public-page-heading">
    <div class="container relative">
        <nav class="public-breadcrumb" aria-label="Jejak halaman"><a href="{{ route('home') }}">Beranda</a><span
                aria-hidden="true">/</span><span aria-current="page">{{ $title }}</span></nav>
        <p class="public-kicker"><span aria-hidden="true"></span>{{ $eyebrow }}</p>
        <h1>{{ $title }}</h1>
        @if ($description)
            <p class="public-page-description">{{ $description }}</p>
        @endif
        @if ($slot->isNotEmpty())
            <div class="public-page-links">{{ $slot }}</div>
        @endif
    </div>
</section>
