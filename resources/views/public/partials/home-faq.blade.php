<section id="pertanyaan" class="section container home-faq">
    <div>
        <p class="eyebrow">SEBELUM MULAI BERBAGI</p>
        <h2>Masih ingin tahu?</h2>
        <p>Temukan jawaban singkat tentang donasi dan bantuan barang.</p><a class="text-link"
            href="{{ route('page', 'kontak') }}">Hubungi pengurus <span aria-hidden="true">→</span></a>
    </div>
    <div class="faq-list">
        @foreach ([
        'Apakah saya perlu membuat akun?' => 'Tidak. Anda dapat mengisi formulir donasi atau bantuan barang tanpa login. Simpan tautan pribadi yang muncul setelah pengajuan untuk melihat perkembangannya.',
        'Berapa minimal nominal donasi?' => 'Anda dapat memilih nominal yang tersedia atau mengisi nominal sendiri mulai Rp5.000. Pembayaran saat ini menggunakan Midtrans Sandbox untuk pengujian.',
        'Bolehkah memberikan beberapa jenis barang?' => 'Boleh. Tambahkan rincian nama, jumlah, dan satuan untuk setiap barang di formulir bantuan. Pengurus akan memeriksa dan mengonfirmasi pengajuan Anda.',
    ] as $question => $answer)
            <details name="home-questions">
                <summary>{{ $question }}<span aria-hidden="true"></span></summary>
                <p>{{ $answer }}</p>
            </details>
        @endforeach
    </div>
</section>
