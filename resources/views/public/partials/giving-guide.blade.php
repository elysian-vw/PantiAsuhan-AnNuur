@php
    $guides = [
        'donasi' => [
            'label' => 'Donasi uang',
            'title' => 'Pilih nominal, mulai kebaikan.',
            'description' => 'Mulai dari Rp5.000, setiap donasi menjadi bentuk kepedulian Anda untuk panti.',
            'steps' => [
                'Pilih kategori dan nominal donasi.',
                'Isi nama serta nomor WhatsApp Anda.',
                'Lanjutkan pembayaran dan lihat status pada tautan pribadi.',
            ],
            'action' => 'Isi formulir donasi',
            'note' => 'Pembayaran saat ini dalam mode pengujian sandbox.',
        ],
        'bantuan' => [
            'label' => 'Bantuan barang',
            'title' => 'Barang yang dibutuhkan, perhatian yang berarti.',
            'description' =>
                'Ajukan bantuan berupa barang. Anda dapat mencantumkan beberapa jenis barang dalam satu pengajuan.',
            'steps' => [
                'Isi identitas dan rincian barang.',
                'Tunggu konfirmasi dari pengurus.',
                'Serahkan barang sesuai kesepakatan dengan pengurus.',
            ],
            'action' => 'Ajukan bantuan barang',
            'note' => 'Tanggal penerimaan dicatat setelah barang diterima oleh panti.',
        ],
        'kunjungan' => [
            'label' => 'Kunjungan',
            'title' => 'Hadir langsung, dekatkan silaturahmi.',
            'description' => 'Rencanakan waktu untuk bertemu dan berkegiatan bersama, sesuai persetujuan pengurus.',
            'steps' => [
                'Pilih tanggal dan jam antara 07.00–21.00 WIB.',
                'Isi jumlah peserta serta tujuan kunjungan.',
                'Tunggu persetujuan jadwal melalui WhatsApp.',
            ],
            'action' => 'Rencanakan kunjungan',
            'note' => 'Jika jadwal berubah, pengurus akan meminta persetujuan Anda kembali.',
        ],
    ];
@endphp
<section id="panduan-berbagi" class="giving-guide section" x-data="givingGuide">
    <div class="container guide-grid">
        <div class="guide-intro">
            <p class="eyebrow">SATU NIAT, BANYAK CARA</p>
            <h2>Temukan cara<br>berbagi Anda.</h2>
            <p>Pilih bentuk kepedulian untuk melihat langkah selanjutnya. Semuanya dapat dilakukan tanpa membuat akun.
            </p>
            <div class="guide-tabs" role="tablist" aria-label="Pilih cara berbagi" x-cloak>
                @foreach ($guides as $key => $guide)
                    <button type="button" id="tab-{{ $key }}" role="tab"
                        aria-controls="guide-{{ $key }}" :aria-selected="active === '{{ $key }}'"
                        :tabindex="active === '{{ $key }}' ? 0 : -1" @click="active='{{ $key }}'"
                        @keydown.arrow-right.prevent="move(1)" @keydown.arrow-left.prevent="move(-1)"
                        @keydown.home.prevent="select('donasi')"
                        @keydown.end.prevent="select('kunjungan')"><span>{{ $guide['label'] }}</span><span
                            class="guide-tab-arrow" aria-hidden="true">→</span></button>
                @endforeach
            </div>
        </div>
        <div class="guide-panels">
            @foreach ($guides as $key => $guide)
                <section id="guide-{{ $key }}" class="guide-panel" role="tabpanel"
                    aria-label="{{ $guide['label'] }}" tabindex="0" x-show="active==='{{ $key }}'">
                    <span class="guide-kicker">{{ $guide['label'] }}</span>
                    <h3>{{ $guide['title'] }}</h3>
                    <p>{{ $guide['description'] }}</p>
                    <ol class="guide-steps">
                        @foreach ($guide['steps'] as $step)
                            <li><span aria-hidden="true">0{{ $loop->iteration }}</span>
                                <p>{{ $step }}</p>
                            </li>
                        @endforeach
                    </ol>
                    <a class="button" href="{{ route('submission.form', $key) }}">{{ $guide['action'] }} <span
                            aria-hidden="true">→</span></a>
                    <p class="guide-note">{{ $guide['note'] }}</p>
                </section>
            @endforeach
        </div>
    </div>
</section>
