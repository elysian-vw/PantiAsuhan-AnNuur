<section id="pertanyaan" class="section container home-faq">
    <div>
        <p class="eyebrow">SEBELUM MULAI BERBAGI</p>
        <h2>Masih ingin tahu?</h2>
        <p>Temukan jawaban singkat tentang donasi, bantuan, dan kunjungan.</p><a class="text-link"
            href="{{ route('page', 'kontak') }}">Hubungi pengurus <span aria-hidden="true">→</span></a>
    </div>
    <div class="faq-list">
        @foreach ([
        'Apakah saya perlu membuat akun?' => 'Tidak. Anda dapat mengisi formulir donasi, bantuan barang, atau kunjungan tanpa login. Simpan tautan pribadi yang muncul setelah pengajuan untuk melihat perkembangannya.',
        'Berapa minimal nominal donasi?' => 'Anda dapat memilih nominal yang tersedia atau mengisi nominal sendiri mulai Rp5.000. Pembayaran saat ini menggunakan Midtrans Sandbox untuk pengujian.',
        'Bolehkah memberikan beberapa jenis barang?' => 'Boleh. Tambahkan rincian nama, jumlah, dan satuan untuk setiap barang di formulir bantuan. Pengurus akan memeriksa dan mengonfirmasi pengajuan Anda.',
        'Kapan saya bisa berkunjung?' => 'Ajukan kunjungan pada pukul 07.00–21.00 WIB. Jadwal berlaku setelah disetujui pengurus. Jika jadwal diubah, persetujuan Anda akan diminta kembali.',
    ] as $question => $answer)
            <details name="home-questions">
                <summary>{{ $question }}<span aria-hidden="true"></span></summary>
                <p>{{ $answer }}</p>
            </details>
        @endforeach
    </div>
</section>
