@extends('layouts.public')
@section('content')
    @php
        $featuredActivity = $activities->first(fn($activity) => filled($activity->foto));
        $beneficiaries = \App\Models\Setting::read('penerima_manfaat');
    @endphp
    <div class="landing-progress" aria-hidden="true"><span></span></div>
    <div class="landing-page landing-refresh" id="landing-top">
        <section class="relative isolate overflow-hidden bg-[#f7f6ee]">
            <div class="pointer-events-none absolute -left-40 top-20 size-96 rounded-full bg-emerald-100/40 blur-3xl"
                aria-hidden="true"></div>
            <div class="container relative grid items-center gap-12 py-14 lg:grid-cols-2 lg:gap-16 lg:py-20">
                <div class="landing-hero-copy">
                    <p class="landing-kicker"><span class="size-2 rounded-full bg-[#be8745]"></span> SEBUAH RUMAH. SEJUTA
                        HARAPAN.</p>
                    <h1
                        class="mb-6 text-[clamp(2.75rem,5.2vw,4.75rem)] leading-[1.08]! font-medium tracking-[-0.045em] text-[#173f2e]">
                        Bersama, tumbuhkan<br><em class="relative font-normal text-[#ad763c]">harapan</em> mereka.</h1>
                    <p class="max-w-lg text-base leading-8">Setiap anak berhak tumbuh dengan kasih sayang dan kesempatan.
                        Bersama Panti Asuhan NU An-Nuur 2 Kota Kediri, jadilah bagian dari perjalanan mereka.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a class="landing-primary" href="{{ route('submission.form', 'donasi') }}">Mulai berbagi <span
                                aria-hidden="true">↗</span></a>
                        <a class="landing-secondary" href="{{ route('page', 'profil') }}">Kenali panti kami <span
                                aria-hidden="true">→</span></a>
                    </div>
                    <div class="mt-9 flex items-center gap-4 border-t border-[#dce1d3] pt-6">
                        <span
                            class="grid size-11 shrink-0 place-items-center rounded-full border border-[#d6dfce] text-xl text-[#185237]"
                            aria-hidden="true">♡</span>
                        <p class="mb-0! text-xs leading-6"><strong class="text-[#244936]">Kebaikan kecil, arti yang
                                besar.</strong><br>Donasi mulai Rp5.000 · Tanpa membuat akun</p>
                    </div>
                </div>
                <div class="landing-hero-visual relative mx-auto w-full max-w-xl pb-7">
                    <div
                        class="relative overflow-hidden rounded-t-[10rem] rounded-b-3xl bg-[#dce6d4] ring-8 ring-white/60 sm:rounded-t-[14rem]">
                        @if ($featuredActivity)
                            <img class="aspect-[5/4] w-full object-cover"
                                src="{{ asset('storage/' . $featuredActivity->foto) }}" alt="{{ $featuredActivity->judul }}"
                                fetchpriority="high" width="640" height="512">
                            <div class="absolute inset-0 bg-linear-to-t from-[#123b29]/70 via-transparent to-transparent"
                                aria-hidden="true"></div>
                            <a class="absolute inset-x-7 bottom-8 text-sm font-semibold text-white!"
                                href="{{ route('article', ['kegiatan', $featuredActivity->id]) }}">{{ $featuredActivity->judul }}
                                <span aria-hidden="true">↗</span></a>
                        @else
                            @include('public.partials.home-illustration')
                        @endif
                    </div>
                    <div
                        class="landing-floating-note absolute -bottom-1 left-3 right-3 flex items-center gap-4 rounded-2xl border border-white bg-white/95 p-5 shadow-xl shadow-emerald-950/10 backdrop-blur sm:left-6 sm:right-10">
                        <span
                            class="grid size-12 shrink-0 place-items-center rounded-xl bg-[#f3eddb] text-2xl text-[#987334]"
                            aria-hidden="true">⌂</span>
                        <div><strong class="block text-sm text-[#173f2e]">Lebih dari tempat tinggal.</strong><span
                                class="text-xs text-[#5e6f66]">Rumah untuk belajar, tumbuh, dan bermimpi.</span></div>
                    </div>
                    <span
                        class="landing-floating-badge absolute right-0 top-8 rounded-full bg-[#f1cc83] px-4 py-2 text-[10px] font-bold tracking-widest text-[#564321] shadow-sm sm:-right-3">TUMBUH
                        BERSAMA ♡</span>
                </div>
            </div>
            <nav class="landing-jump container border-t border-[#dce1d3]" aria-label="Jelajahi beranda"><span>Temukan di
                    sini</span><a href="#kebutuhan-beranda">Kebutuhan panti ↗</a><a href="#panduan-berbagi">Cara berbagi
                    ↗</a><a href="#cerita-beranda">Cerita kami ↗</a><a href="#pertanyaan">Tanya jawab ↗</a></nav>
        </section>

        <section class="container py-16 lg:py-20" aria-labelledby="participate-title">
            <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="landing-kicker">AMBIL BAGIAN DALAM KEBAIKAN</p>
                    <h2 id="participate-title" class="landing-title">Ada banyak cara untuk peduli.</h2>
                </div>
                <p class="mb-0! max-w-xs text-sm leading-7">Pilih bentuk dukungan yang paling dekat dengan hati Anda.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ([['key' => 'donasi', 'number' => '01', 'icon' => 'heart', 'title' => 'Berbagi rezeki', 'copy' => 'Bantu mendukung keseharian anak-anak melalui donasi sesuai kemampuan Anda.', 'label' => 'Donasi uang'], ['key' => 'bantuan', 'number' => '02', 'icon' => 'box', 'title' => 'Penuhi kebutuhan', 'copy' => 'Dari bahan pangan hingga perlengkapan belajar, setiap bantuan punya arti.', 'label' => 'Bantuan barang']] as $way)
                    <a href="{{ route('submission.form', $way['key']) }}"
                        class="landing-way group {{ $loop->first ? 'landing-way-featured' : '' }}">
                        <div class="mb-9 flex items-center justify-between"><span
                                class="landing-way-icon">@include('public.partials.home-icon', ['icon' => $way['icon']])</span><span
                                class="text-xs tracking-widest opacity-60">/ {{ $way['number'] }}</span></div>
                        <h3 class="mb-3 text-xl">{{ $way['title'] }}</h3>
                        <p class="mb-7 text-sm leading-7">{{ $way['copy'] }}</p>
                        <span
                            class="mt-auto flex items-center justify-between border-t border-current/15 pt-5 text-sm font-semibold">{{ $way['label'] }}<span
                                class="transition-transform group-hover:translate-x-1 motion-reduce:transform-none"
                                aria-hidden="true">↗</span></span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="border-y border-[#e4e8de] bg-[#f6f7f0] py-16 lg:py-20">
            <div class="container grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
                <div>
                    <p class="landing-kicker">MENGENAL AN-NUUR 2</p>
                    <h2 class="landing-title mb-6!">Tempat bertumbuh,<br>ruang untuk berharap.</h2><a class="text-link"
                        href="{{ route('page', 'profil') }}">Lebih dekat dengan kami <span aria-hidden="true">↗</span></a>
                </div>
                <div>
                    <p class="text-base leading-8">
                        {{ \App\Models\Setting::read('tentang') ?: 'Di Panti Asuhan NU An-Nuur 2 Kota Kediri, kepedulian mempertemukan kita. Kenali panti kami, ikuti cerita keseharian anak-anak, dan temukan cara untuk ikut mendukung perjalanan mereka.' }}
                    </p>
                    <div class="mt-7 grid gap-5 border-t border-[#dce1d3] pt-7 sm:grid-cols-2">
                        @if ($beneficiaries !== null && $beneficiaries !== '')
                            <div><strong class="font-serif text-4xl text-[#185237]">{{ $beneficiaries }}</strong><span
                                    class="mt-2 block text-sm">Penerima manfaat</span><small
                                    class="text-[#5e6f66]">{{ \App\Models\Setting::read('periode_statistik') }}</small>
                            </div>
                        @else
                            <div><strong class="block text-lg text-[#185237]">Kasih & kepedulian</strong><span
                                    class="mt-2 block text-sm text-[#5e6f66]">Menjadi bagian dari tumbuh kembang
                                    anak.</span></div>
                        @endif
                        <div><strong class="block text-lg text-[#185237]">Terhubung lebih dekat</strong><span
                                class="mt-2 block text-sm text-[#5e6f66]">Kenali kegiatan dan kabar dari rumah
                                kami.</span><a class="mt-3 inline-flex text-sm font-semibold text-[#185237]"
                                href="{{ route('page', 'galeri') }}">Jelajahi galeri <span class="ml-2"
                                    aria-hidden="true">→</span></a></div>
                    </div>
                </div>
            </div>
        </section>

        <section id="kebutuhan-beranda" class="section container">
            <div class="section-heading">
                <div>
                    <p class="landing-kicker">DUKUNGAN YANG TEPAT SASARAN</p>
                    <h2>Kebutuhan nyata. Kepedulian nyata.</h2>
                    <p class="mt-4 mb-0! max-w-xl text-sm leading-7">Lihat kebutuhan yang dicatat pengurus dan bantu
                        lengkapi yang masih diperlukan.</p>
                </div><a class="text-link" href="{{ route('page', 'kebutuhan') }}">Semua kebutuhan <span
                        aria-hidden="true">↗</span></a>
            </div>
            <div class="card-grid">
                @forelse ($needs as $item)
                    @include('public.partials.need')
                @empty
                    <div class="landing-empty"><span
                            class="landing-way-icon mx-auto mb-5">@include('public.partials.home-icon', ['icon' => 'box'])</span>
                        <h3>Informasi kebutuhan segera hadir</h3>
                        <p>Pengurus sedang menyiapkan daftar kebutuhan. Anda tetap dapat mengajukan bantuan barang.</p><a
                            class="text-link" href="{{ route('submission.form', 'bantuan') }}">Ajukan bantuan barang
                            <span aria-hidden="true">→</span></a>
                    </div>
                @endforelse
            </div>
        </section>
        @include('public.partials.giving-guide')
        <section id="cerita-beranda" class="section container">
            <div class="section-heading">
                <div>
                    <p class="landing-kicker">CERITA DARI RUMAH KAMI</p>
                    <h2>Momen kecil, kenangan berarti.</h2>
                    <p class="mt-4 mb-0! text-sm leading-7">Lebih dekat dengan kegiatan dan kebersamaan di An-Nuur 2.</p>
                </div><a class="text-link" href="{{ route('page', 'kegiatan') }}">Semua kegiatan <span
                        aria-hidden="true">↗</span></a>
            </div>
            <div class="card-grid">
                @forelse ($activities as $item)
                    @include('public.partials.article-card', ['page' => 'kegiatan'])
                @empty
                    <div class="landing-empty"><span
                            class="landing-way-icon mx-auto mb-5">@include('public.partials.home-icon', ['icon' => 'people'])</span>
                        <h3>Setiap kebersamaan punya cerita</h3>
                        <p>Kegiatan yang dipublikasikan pengurus akan tampil di sini. Sementara itu, mari mengenal panti
                            lebih dekat.</p><a class="text-link" href="{{ route('page', 'profil') }}">Kenali An-Nuur 2
                            <span aria-hidden="true">→</span></a>
                    </div>
                @endforelse
            </div>
        </section>
        <section
            class="container landing-invitation relative isolate overflow-hidden rounded-3xl px-7! py-12 md:px-14! md:py-16">
            <div class="pointer-events-none absolute -right-20 -top-32 -z-10 size-96 rounded-full border-[50px] border-white/5"
                aria-hidden="true"></div>
            <div class="grid items-center gap-8 lg:grid-cols-[1fr_auto]">
                <div>
                    <p class="landing-kicker">BERSAMA MENDUKUNG ANAK-ANAK PANTI</p>
                    <h2 class="mb-5 text-3xl font-medium sm:text-4xl">Berbagi hari ini.<br>Menumbuhkan harapan esok.</h2>
                    <p class="mb-0! max-w-lg text-sm leading-7">Dukung kebutuhan sehari-hari dan pendidikan anak-anak
                        melalui donasi sesuai kemampuan Anda.</p>
                </div>
                <div class="flex flex-col items-start gap-4"><a
                        class="landing-primary bg-[#f1cc83]! text-[#173f2e]! hover:bg-[#f7dba8]!"
                        href="{{ route('submission.form', 'donasi') }}">Mulai berdonasi <span
                            aria-hidden="true">↗</span></a><span class="text-xs text-white/70">Setiap dukungan Anda
                        berarti</span></div>
            </div>
        </section>
        @include('public.partials.home-faq')
        <button class="back-to-top" type="button" aria-label="Kembali ke atas" hidden><span
                aria-hidden="true">↑</span><span>Ke atas</span></button>
    </div>
@endsection
