<section id="pertanyaan" class="section container home-faq">
    <div>
        <p class="eyebrow">SEBELUM MULAI BERBAGI</p>
        <h2>Masih ingin tahu?</h2>
        <p>Temukan jawaban singkat tentang donasi dan bantuan barang.</p><a class="text-link"
            href="{{ route('page', 'kontak') }}">Hubungi pengurus <span aria-hidden="true">→</span></a>
    </div>
    <div class="faq-list">
        @foreach ([
        'Bagaimana cara melakukan donasi uang?' => 'Donasi uang kini dilakukan melalui Aplikasi Mobile An-Nuur 2. Anda dapat langsung berdonasi mulai Rp5.000 tanpa perlu login terlebih dahulu (Donatur Biasa), dan riwayat donasi otomatis tersimpan di perangkat Anda.',
        'Bagaimana cara menjadi Donatur Tetap?' => 'Setelah Anda memiliki minimal 1 transaksi donasi yang berhasil di Aplikasi Mobile, data Anda otomatis masuk ke antrean verifikasi Pengurus Panti untuk disetujui menjadi Donatur Tetap.',
        'Bolehkah memberikan bantuan berupa barang lewat website?' => 'Boleh. Pengajuan bantuan barang tetap dapat dilakukan langsung melalui formulir Bantuan Barang di website ini tanpa perlu membuat akun.',
    ] as $question => $answer)
            <details name="home-questions">
                <summary>{{ $question }}<span aria-hidden="true"></span></summary>
                <p>{{ $answer }}</p>
            </details>
        @endforeach
    </div>
</section>
