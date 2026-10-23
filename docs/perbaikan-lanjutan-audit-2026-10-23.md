# Perbaikan lanjutan audit — 23 Oktober 2026

Pembaruan pengujian: uji HP fisik dan pembayaran sandbox sampai settlement telah dilakukan setelah catatan ini. Lihat [hasil uji perangkat dan sandbox](uji-perangkat-sandbox-2026-10-23.md), termasuk dua temuan UX lanjutan dan batas integrasi yang tersisa.

Melanjutkan [audit sistem](audit-sistem-2026-10-22.md) dan [perbaikan pembayaran/laporan](perbaikan-pembayaran-laporan-2026-10-22.md). Perubahan mencakup Laravel di `projectTA` dan Flutter di folder sejajar `annuur_mobile`.

## Perubahan

- **Pencegahan order ganda:** API menyimpan idempotency key dan hash payload dengan unique constraint per pemilik serta penguncian akun dalam transaksi. Retry dengan key sama mengembalikan order semula, termasuk setelah 15 detik atau status terminal. Payload berbeda dengan key sama ditolak 409. Flutter menyimpan key secara persisten sebelum mengirim, mempertahankannya saat gagal, dan menggantinya setelah menerima order yang valid. Klien lama tanpa key tetap memakai fallback 15 detik; perlindungan baru memerlukan pembaruan aplikasi.
- **Riwayat mobile:** halaman terakhir dapat dimuat, filter status diterapkan di server, respons filter lama diabaikan, dan kegagalan halaman menyediakan retry tanpa melewati halaman tersebut.
- **Sesi mobile:** gangguan jaringan/503 mempertahankan token dan menawarkan retry. UI awal tampil sebelum pemulihan profil selesai. Respons 401 ditangani terpusat dengan perlindungan terhadap respons sesi lama. Provider data pribadi direset ketika akun berubah.
- **Checkout mobile:** tersedia halaman kembali yang valid; backend mengirim URL callback agar tetap sesuai saat alamat API perangkat berbeda dari domain backend. WebView memeriksa origin/path, menyediakan retry dan pembukaan browser, serta memeriksa status ke backend. Donasi pending dapat mencoba checkout lagi dari riwayat/detail.
- **Validasi:** web, API referensi, dan formulir mobile memakai minimal Rp5.000; API menerima rupiah bulat sampai Rp999.999.999. Registrasi dibatasi lima permintaan per menit dan email dinormalisasi.
- **Aktivasi akun:** persetujuan admin mengirim tautan aktivasi. Akun baru tetap menunggu sampai pemilik menukarkan token email; persetujuan saja tidak mengisi verifikasi email. Data verifikasi lama tidak diubah. Konfigurasi email yang berfungsi diperlukan untuk alur operasional ini.
- **Bukti transfer manual:** penyimpanan menggunakan disk privat dan tautan unduhan melalui endpoint pengurus yang memeriksa otorisasi.
- **Layar kecil:** header web diperbaiki untuk viewport 320 piksel tanpa menyembunyikan konten yang meluber.
- **Android:** cleartext hanya diizinkan pada debug. Release menggunakan signing tersendiri dan mensyaratkan API HTTPS eksplisit; tidak lagi menggunakan signing debug.

## Database dan build

Migrasi `2026_10_22_000100_add_donation_idempotency.php` sudah diterapkan ke database lokal. Saat memperbarui lingkungan lain, jalankan migrasi backend sebelum menjalankan versi aplikasi ini.

APK debug berhasil dibuat di `D:\apps\laragon\www\annuur_mobile\build\app\outputs\flutter-apk\app-debug.apk`. Build ini menggunakan konfigurasi API debug bawaan; akses perangkat memerlukan backend yang dapat dijangkau atau USB forwarding sesuai konfigurasi lokal.

Untuk release, isi `android/key.properties` mengikuti `android/key.properties.example` dengan keystore milik pengelola, lalu jalankan dari folder mobile:

```console
flutter build apk --release --dart-define=API_BASE_URL=https://HOST-BACKEND/api/v1
```

Ganti host dengan backend sebenarnya. Keystore dan kata sandi tidak boleh dimasukkan ke Git. APK release belum dibuat karena konfigurasi signing produksi belum tersedia.

## Verifikasi

| Pemeriksaan | Hasil |
| --- | --- |
| `php artisan test` | 67 tes lulus, 639 assertion |
| `flutter test --no-pub --concurrency=1` | 27 tes lulus |
| `flutter analyze --no-pub` | Tidak ditemukan masalah |
| `flutter build apk --debug --no-pub` | Berhasil |
| `npm run build` | Berhasil |
| Browser publik: 12 halaman × 5 ukuran | 60 pemeriksaan lulus, termasuk 320 px; tanpa overflow/error JavaScript |

Tes regresi permanen ditambahkan pada `tests/Feature/AuditFollowupTest.php` dan mobile `test/audit_followup_test.dart`; tes akun dan API juga disesuaikan. Tes provider memakai respons tiruan.

## Pemeriksaan yang masih memerlukan lingkungan operasional

Belum dilakukan transaksi provider nyata, pengiriman email aktivasi nyata, UAT Android fisik untuk perpindahan aplikasi pembayaran/tombol kembali, atau build release bertanda tangan produksi. Inspeksi browser admin masih terbatas karena akun otomatisasi lokal tidak tersedia; pengujian backend admin lulus. Load test, pemulihan backup, audit dependensi, dan validasi konten oleh pengurus tetap merupakan tindak lanjut terpisah.
