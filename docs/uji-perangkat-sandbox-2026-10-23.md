# Uji perangkat Android dan Midtrans sandbox — 23 Oktober 2026

Pengujian dilakukan pada Infinix X6833B fisik melalui USB debugging, APK debug hasil perbaikan audit, Laravel lokal port 8000, dan akun Midtrans sandbox yang sudah dikonfigurasi. Tidak ada pembayaran uang nyata. Akun donatur uji dibuat sebagai fixture aktif; ini tidak menguji pengiriman email aktivasi.

## Hasil yang terverifikasi

| Skenario | Hasil |
| --- | --- |
| Instalasi APK dan startup | Berhasil di HP fisik |
| Login dan pengambilan dashboard | Berhasil melalui USB forwarding |
| Putus koneksi, hentikan dan buka ulang aplikasi | Token tetap tersimpan; tampil pesan koneksi dan tombol Coba lagi |
| Pulihkan forwarding dan tekan Coba lagi | Kembali ke akun tanpa login ulang |
| Nominal Rp4.999 | Ditolak dengan pesan minimal Rp5.000 |
| Nominal Rp5.000 | Checkout sandbox terbuka di WebView |
| Tombol kembali Android | Dialog konfirmasi muncul dan dapat menutup checkout |
| Buka pembayaran dari riwayat | Menggunakan order yang sama; jumlah order uji tetap satu |
| Buka di browser eksternal | Checkout terbuka di Chrome HP |
| Pembayaran QRIS melalui simulator resmi | Simulator PAID; backend settlement/Berhasil sebelum tombol periksa status ditekan |
| Periksa status di HP | Dialog Pembayaran Berhasil; riwayat menunjukkan satu keberhasilan Rp5.000 dan nol menunggu |
| Filter Menunggu setelah pembayaran | Daftar kosong sesuai data server |
| Cabut token akun uji lalu minta riwayat | Aplikasi kembali ke login |
| Browser publik/admin | 52 pemeriksaan pada 360, 390, 768, 1440 px lulus, tanpa error JavaScript |

Order pengujian perangkat: `DON-01M5JD5SVRF7VCA2DY2V4Y48F7`. Simulator resmi: https://simulator.sandbox.midtrans.com/v2/qris/index. Bukti lokal berada di `.runtime/device-test-result.json`, `.runtime/screenshots/device-offline-retry.png`, `device-checkout.png`, `device-payment-success.png`, dan `sandbox-paid.png`.

Sebelumnya juga dibuat satu checkout sandbox mandiri `AUDIT-20261023010913-1cd2def1` untuk memeriksa koneksi: HTTP 201, token diterima, halaman checkout HTTP 200. Checkout mandiri ini tidak dibayar dan tidak dimasukkan ke database donasi.

## Temuan dan tindakan

1. Server lokal sempat berhenti saat pengujian login. Server dijalankan kembali melalui sesi terminal, lalu login berhasil. Percobaan menjalankan proses latar belakang ditolak peninjauan persetujuan otomatis; sesi terminal berhasil.
2. Domain callback ngrok pada APP_URL awalnya offline (`ERR_NGROK_3200`, HTTP 404). Tunnel dijalankan kembali ke port 8000; halaman `/pembayaran/mobile/kembali` kemudian HTTP 200. Callback status pembayaran dari sandbox berhasil mengubah database menjadi Berhasil. Ketersediaan server/tunnel tetap diperlukan selama demo.
3. Nama merchant checkout adalah **Koperasi Merah Putih**, bukan An-Nuur. Cocokkan akun/identitas merchant di dashboard Midtrans sebelum penggunaan operasional. Tidak mengubah credential secara otomatis.
4. Pada checkout Rp5.000, pilihan BCA berkelas `disabled` dan tidak dapat dipilih; QRIS tersedia dan berhasil. Penyebab pembatasan kanal belum diverifikasi di dashboard provider. Jangan menyimpulkan seluruh kanal menerima nominal minimum aplikasi.
5. Saat koneksi login putus, UI sempat menampilkan pesan teknis `ClientConnection closed before full header was received`. Sudah diperbaiki: kegagalan koneksi, timeout, dan error tak terduga menggunakan pesan yang mudah dipahami tanpa detail internal. Tes regresi mencakup ketiga kondisi tersebut.
6. Tombol dialog **Kembali ke Beranda** setelah pembayaran dari riwayat kembali ke layar utama dengan tab Riwayat masih aktif. Label sudah diubah menjadi **Selesai**, sehingga sesuai dengan perilaku mempertahankan tab terakhir.

## Pembersihan dan batas

Verifikasi setelah perbaikan pesan login dan label tombol: 28 tes Flutter lulus (`flutter test --no-pub --concurrency=1`) dan `flutter analyze --no-pub` tidak menemukan masalah.

Akun pengurus sementara, akun donatur sementara, token, sesi, satu donasi uji lokal, transaksi pembayaran terkait, riwayat statusnya, dan log WhatsApp nonaktif sudah dibersihkan secara terbatas berdasarkan ID fixture. Rekaman sandbox di provider tetap merupakan data uji provider. Aplikasi ditinggalkan pada layar login. Tidak ada akun operasional yang diubah.

Email menggunakan `MAIL_MAILER=log`; pengiriman ke kotak masuk nyata belum dapat diuji. WhatsApp nonaktif. Build release dengan keystore produksi belum diuji karena signing produksi belum dikonfigurasi. Pengujian perpindahan ke aplikasi dompet digital nyata belum dilakukan; yang diuji adalah browser eksternal dan simulator QRIS. Pengujian ini tidak menggantikan UAT pengurus atau seluruh kombinasi perangkat.
