# Audit web, API, dan Flutter — 22 Oktober 2026

Pembaruan 23 Oktober: perbaikan lanjutan beserta hasil tes dan batas verifikasinya tersedia di [catatan tindak lanjut](perbaikan-lanjutan-audit-2026-10-23.md).

Tindak lanjut: masalah konsistensi pembatalan dan perhitungan laporan telah diperbaiki pada sesi berikutnya. Lihat [catatan perbaikan](perbaikan-pembayaran-laporan-2026-10-22.md). Temuan di bawah dipertahankan sebagai catatan kondisi saat audit; probe runtime mereproduksi kondisi lama dan bukan tes regresi versi terbaru.

## Kesimpulan

Fondasi sistem sudah berjalan dan pengujian yang tersedia mayoritas memadai untuk alur normal. Namun ada cacat pada pembatalan pembayaran, konsistensi laporan donasi manual, pencegahan transaksi ganda, pagination mobile, dan pemulihan sesi mobile. Perbaiki ini sebelum penggunaan operasional. Laporan ini adalah pemeriksaan kode dan pengujian lokal, bukan sertifikasi seluruh kemungkinan alur atau kesiapan produksi.

Source aplikasi tidak diperbaiki pada audit ini. Yang ditambahkan adalah laporan dan probe diagnostik di folder runtime yang diabaikan Git. Build asset web dijalankan ulang. Perubahan source web yang sudah ada sejak awal tetap dipertahankan.

## Pemeriksaan dan hasil

| Pemeriksaan | Hasil |
| --- | --- |
| `php artisan test` | 54 lulus, 509 assertion; SQLite in-memory |
| `npm run build` | Berhasil |
| `flutter analyze` | Tidak ditemukan masalah statis |
| `flutter test --no-pub --concurrency=1` | 16 tes lulus; termasuk layout 320/390 px dan skala teks 1,0/1,5 pada login, beranda, dan formulir donasi |
| Browser publik | 12 halaman × 5 lebar = 60 pemeriksaan; semua HTTP 200; tidak ada pageerror JavaScript |
| Responsif web | 360, 390, 768, 1440 px tanpa overflow; 320 px mengalami overflow pada seluruh 12 halaman yang diperiksa |
| Browser admin | Skrip berhenti saat login; akun dalam `.runtime/admin-access.json` tidak ditemukan di database aktif. Pengujian backend admin tetap lulus |
| Probe backend tambahan | 3 probe berhasil mereproduksi cacat, 10 assertion |
| Probe Flutter tambahan | 2 probe berhasil mereproduksi cacat |

Tes Flutter pertama yang berjalan paralel berhenti akibat kehabisan memori proses Dart. Pengulangan berurutan lulus. Itu tidak dijadikan bukti bug aplikasi.

Halaman browser: `/`, `/profil`, `/kontak`, `/kebutuhan`, `/kegiatan`, `/berita`, `/galeri`, `/donasi`, `/bantuan`, `/admin/login`, `/donatur/login`, `/donatur/lupa-password`. Pemeriksaan mencakup status respons, overflow dokumen, serta error JavaScript; bukan semua interaksi di setiap halaman. Screenshot formulir donasi 390 px ditinjau secara visual.

## Temuan prioritas

### 1. P1 — Pembatalan lokal berhasil meskipun provider gagal

Lokasi: `app/Http/Controllers/Api/V1/DonationController.php:152`, `app/Services/MidtransService.php`, metode `cancel`.

Controller mengabaikan boolean hasil pembatalan provider dan langsung mengubah donasi menjadi `Dibatalkan`. Probe membuat provider mengembalikan HTTP 503; API tetap membalas 200 dengan status dibatalkan. Pengguna bisa mengira order sudah ditutup padahal pembayaran provider mungkin masih aktif.

Selain itu, sinkronisasi dan pembaruan lokal tidak mengunci ulang status donasi di dalam transaksi; callback sukses yang masuk bersamaan berpotensi tertimpa pembatalan lokal.

Perbaikan: bedakan hasil batal terkonfirmasi, gagal, dan belum diketahui. Jangan mengklaim pembatalan selesai saat hasil provider belum pasti. Kunci dan baca ulang status sebelum perubahan akhir, pertahankan status sukses sebagai terminal, dan tambahkan tes timeout serta callback bersamaan. Order yang memang belum pernah dibuat di provider perlu aturan pembatalan lokal tersendiri.

### 2. P1 — Donasi manual hilang dari total bersih dashboard dan laporan

Lokasi: `app/Services/ManualDonationService.php`, `app/Http/Controllers/Admin/DashboardController.php:19`, `app/Http/Controllers/Admin/ReportController.php:108`.

Pencatatan manual meninggalkan `biaya = null`. Dashboard dan laporan hanya menjumlahkan catatan dengan biaya tidak null. Probe donasi tunai Rp100.000 menghasilkan total bersih laporan Rp0. Arus kas justru memasukkan penerimaan manual tersebut. Akibatnya, angka antarmodul tidak konsisten; dashboard juga menganggap biaya donasi manual belum tersedia.

Perbaikan: tetapkan perlakuan biaya donasi manual secara eksplisit, selaraskan query dashboard/laporan/arus kas, dan tangani data manual lama. Tambahkan tes yang membandingkan angka antarmodul untuk transaksi sama.

### 3. P1 — Pengiriman ulang dapat membuat order donasi ganda

Lokasi: `app/Http/Controllers/Api/V1/DonationController.php:62–90`, mobile `lib/data/repositories/donation_repository.dart`.

`idempotency_key` divalidasi tetapi tidak disimpan/digunakan. Pencegahan hanya mencari nominal/kategori sama selama 15 detik sebelum transaksi database. Probe mengirim key sama, memajukan waktu 16 detik, lalu mengirim ulang: dua donasi tercipta. Ini relevan karena timeout HTTP aplikasi 30 detik. Request bersamaan juga tidak dilindungi constraint idempotensi.

Perbaikan: kirim key tetap untuk satu tindakan donasi; simpan dengan unique constraint per pemilik; kembalikan order semula saat retry. Jangan mengandalkan kesamaan nominal/kategori karena dua donasi yang sengaja berbeda bisa memiliki nilai sama.

### 4. P2 — Halaman terakhir riwayat donasi mobile tidak pernah dimuat

Lokasi mobile: `lib/providers/donation_provider.dart:33,76,86`.

Setelah halaman pertama, `_currentPage` diset ke halaman berikutnya, tetapi `hasMore` memakai `< _lastPage`. Jika total dua halaman, nilainya menjadi `2 < 2`, sehingga halaman kedua tidak diminta. Probe paginator dua halaman membuktikan hanya request halaman 1 yang terkirim.

Perbaikan: pisahkan nomor halaman aktif dan berikutnya, atau sesuaikan batas perbandingan. Uji 1, 2, dan 3 halaman serta retry ketika request halaman gagal.

Filter `Berhasil`/`Menunggu` juga hanya menyaring data yang sudah dimuat (`lib/ui/screens/donation/donation_history_tab.dart:39`). Jika halaman pertama tidak mengandung status tersebut, UI dapat menampilkan daftar kosong padahal hasil ada di halaman berikutnya. Gunakan filter status server dan reset pagination saat filter berubah.

### 5. P2 — Gangguan jaringan/server menghapus sesi login mobile

Lokasi mobile: `lib/providers/auth_provider.dart:36–49`, `lib/main.dart`.

Semua exception saat pemulihan profil menghapus token, termasuk HTTP 503 atau jaringan terputus. Probe respons 503 membuktikan token tersimpan hilang dan status menjadi unauthenticated. `initialize()` juga ditunggu sebelum `runApp`, sehingga saat server lambat UI Flutter belum tampil sampai pemeriksaan selesai/timeout.

Perbaikan: hapus sesi hanya pada respons autentikasi yang memang tidak sah; simpan token saat error sementara dan tampilkan retry/offline. Jalankan pemulihan setelah UI awal tampil. Endpoint lain yang mendapat 401 juga perlu penanganan sesi terpusat, karena beberapa provider saat ini menelan error tanpa informasi untuk pengguna.

### 6. P2 — Header web meluber pada layar 320 px

Lokasi: `resources/views/layouts/public.blade.php:54–71` dan aturan `.header-inner`/`.menu-toggle` pada `resources/css/app.css`.

Browser mengukur lebar dokumen 327 px pada viewport 320 px. Sisi kanan tombol menu berada di 326,98 px. Masalah sama terjadi pada 12 halaman yang berbagi header. Pada 360 px ke atas, pemeriksaan overflow lulus.

Perbaikan: ringkas jarak, ukuran logo, atau CTA header pada breakpoint kecil. Pertahankan tombol menu 40 px yang mudah disentuh. Jangan hanya menyembunyikan overflow karena itu dapat memotong tombol.

## Temuan tambahan dan saran

1. **Arus kas mobile tidak menyampaikan biaya yang belum diketahui.** `CashflowService` menghitung penerimaan dengan biaya null seolah nol sambil menyediakan `unknown_fee_count`, tetapi `TransparencyController::summary` tidak meneruskan indikator itu. Pengguna mobile melihat saldo tanpa mengetahui angka belum final. Tampilkan keterangan sementara dan pisahkan bruto, biaya diketahui, serta bersih terkonfirmasi.
2. **URL kembali checkout ulang tidak valid.** `PaymentController.php:32` memakai token acak baru tanpa mengubah hash token donasi. Jika cabang checkout ini berhasil membuat URL pembayaran, URL tracking kembali tidak cocok dengan donasi dan berakhir 404. Gunakan halaman callback mobile yang valid atau mekanisme token yang konsisten. Temuan ini dari inspeksi kode, belum diuji melalui provider nyata.
3. **Pendaftaran API tidak dibatasi throttle.** Route register publik tidak memiliki throttle dan tidak ada throttle grup API pada bootstrap. Tambahkan pembatasan agar pembuatan akun menunggu tidak mudah dispam. Login dan lupa password sudah memiliki pembatasan di controller.
4. **Persetujuan admin sekaligus mengisi `email_verified_at`.** Ini membuktikan persetujuan admin, bukan otomatis membuktikan kepemilikan kotak email. Pisahkan status persetujuan dan verifikasi email jika keduanya memang menjadi persyaratan sistem; saat ini API login memperlakukan email itu sebagai verified.
5. **Aturan nominal web/mobile berbeda.** Web minimal Rp5.000 dengan integer; API mobile minimal Rp10.000 dengan numeric. Samakan aturan bisnis atau jelaskan perbedaan kanal. API numeric juga menerima pecahan sementara checkout mengubah nominal ke integer; sebaiknya validasi rupiah utuh agar tidak muncul ketidakcocokan nominal callback.
6. **Penanganan pembayaran WebView perlu dipersempit.** Pencocokan substring `finish` atau `order_id=` pada URL apa pun terlalu luas; gunakan origin/path callback yang dikenali. Tetap pertahankan verifikasi status ke backend, yang saat ini sudah dilakukan. Tambahkan UI kegagalan load dan uji kembali dari aplikasi pembayaran eksternal serta tombol back di Android nyata.
7. **Konfigurasi Android masih untuk pengembangan.** Default API adalah loopback HTTP, manifest utama mengizinkan cleartext, dan build release memakai signing debug. Pisahkan konfigurasi debug/release, wajibkan backend HTTPS yang dapat dijangkau perangkat dan signing rilis sebelum distribusi. Ini bukan masalah untuk demo lokal yang memang memakai USB forwarding.
8. **Tautan bukti transfer manual menunjuk ke penyimpanan yang berbeda.** `ManualDonationService.php:24` menyimpan file pada disk default (konfigurasi bawaan: local/private), tetapi `resources/views/admin/transactions/show.blade.php:39` membuat URL publik `/storage/...`. Pada disk local, file tidak tersedia melalui URL publik tersebut. Route web yang diperiksa hanya menyediakan unduhan terotorisasi untuk bukti pengeluaran. Ganti tautan bukti donasi manual dengan endpoint unduhan terotorisasi dari disk privat; jangan memindahkan bukti ke publik hanya agar URL bekerja.
9. **Cakupan tes perlu diperluas pada kondisi gagal.** Tes normal sudah lulus tetapi belum menangkap cacat di atas. Jadikan repro ini tes regresi dengan harapan perilaku benar ketika melakukan perbaikan. Tambahkan uji ganti akun pada perangkat yang sama, koneksi putus, refresh sesi, pagination/filter, serta pembayaran sukses/gagal/timeout/callback terlambat.

## Bagian yang sudah baik

- Callback pembayaran memeriksa signature dan nominal; redirect saja tidak mengubah status menjadi berhasil.
- Akses transaksi pribadi memeriksa pemilik dan verifikasi kepemilikan; tes akses transaksi donatur lain lulus.
- Penonaktifan akun melalui service mencabut token mobile dan sesi web; dugaan awal bahwa token tidak dicabut tidak terbukti pada alur tersebut.
- Bukti pengeluaran privat dibatasi aksesnya dan diuji.
- Pengeluaran memakai alur draft, pembukuan, dan pembatalan beralasan.
- Tampilan formulir donasi web 390 px tersusun satu kolom dengan input dan pilihan nominal yang jelas.

## Batas pemeriksaan

- Belum menjalankan aplikasi di perangkat Android/emulator pada audit ini; tidak ada pengujian APK release atau transaksi Midtrans/Fonnte/email sungguhan.
- Belum menyelesaikan inspeksi visual dashboard admin karena akun otomatisasi lokal tidak ada. Tidak membuat atau mengganti akun operasional untuk mengatasi itu.
- Belum melakukan load test, pemulihan backup, audit CVE dependensi, atau UAT bersama pengurus.
- Angka penerima manfaat, kontak, foto, dan konten dummy yang tampil perlu divalidasi pemilik sebelum publikasi; audit kode tidak membuktikan kebenaran isi konten.

## Bukti lokal dan reproduksi

- `.runtime/AuditReviewTest.php`: jalankan `php vendor/bin/phpunit .runtime/AuditReviewTest.php` dari proyek web.
- `../annuur_mobile/.dart_tool/audit_review_test.dart`: jalankan `flutter test --no-pub --concurrency=1 .dart_tool/audit_review_test.dart` dari proyek mobile.
- `.runtime/audit-public-results.json`: hasil per halaman dan lebar.
- `.runtime/audit-public.mjs`, `.runtime/audit-overflow.mjs`: pemeriksaan browser hanya baca.
- `.runtime/audit-flutter-tests.log`: hasil 16 tes Flutter berurutan.
- `.runtime/screenshots/audit-donasi-390.png`, `audit-donasi-320.png`, `audit-home-390.png`: screenshot web.

Probe audit sengaja mengassert perilaku cacat yang saat ini terjadi. Status probe lulus berarti cacat berhasil direproduksi, bukan berarti perilakunya benar. Folder runtime dapat dibersihkan dan bukan pengganti tes regresi permanen.

Urutan pengerjaan yang disarankan: konsistensi status pembayaran dan laporan → idempotensi → pagination/filter dan sesi mobile → responsif 320 px → validasi bisnis dan konfigurasi distribusi → UAT perangkat nyata dan integrasi sandbox.
