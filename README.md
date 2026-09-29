# Panti Asuhan NU An-Nuur 2 — Laravel

Aplikasi Laravel 13, MySQL, Blade, Tailwind CSS 4, dan Alpine.js. Website publik dan dashboard memakai layout responsif yang mengutamakan mobile. Dokumen sumber ruang lingkup: `project_plan_ta_an_nuur_2.md` (tidak diubah).

## Menjalankan project yang sudah disiapkan

- Website: http://127.0.0.1:8000
- Dashboard: http://127.0.0.1:8000/admin
- Akun pengurus lokal: `.runtime/admin-access.json`. Password dibuat acak, bukan password bawaan aplikasi. File ini diabaikan Git. Ganti melalui menu Pengguna jika diperlukan.
- PHP CLI yang digunakan memerlukan **PHP 8.3 atau lebih baru**. Instalasi Laragon lain yang masih memakai PHP 8.1 perlu mengganti versi PHP terlebih dahulu.
- MySQL lokal project berjalan pada `127.0.0.1:3307`, database `an_nuur_2`. Data disimpan di `.runtime/mysql`, terpisah dari database Laragon lain. Instance lokal ini tidak membuka akses jaringan luar.

Untuk menyalakan kembali server lokal, jalankan dari PowerShell di folder project:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start-local.ps1
```

Jika MySQL sudah berjalan sesuai `.env`, cukup:

```console
php artisan serve --host=127.0.0.1 --port=8000
```

Asset produksi sudah dibangun. Saat mengedit tampilan, gunakan `npm run dev` atau ulangi `npm run build` setelah perubahan. Jika memakai virtual host Apache/Nginx, document root harus mengarah ke folder **public**, bukan root project.

## Instalasi di lingkungan lain

1. Siapkan PHP >=8.3 dengan ekstensi PDO MySQL, mbstring, bcmath, fileinfo, GD, XML, ZIP, intl, dan Composer; Node.js yang kompatibel dengan Vite; serta MySQL.
2. Jalankan `composer install` dan `npm install`.
3. Salin `.env.example` menjadi `.env` jika belum ada. Sesuaikan koneksi MySQL dan `APP_URL`; buat database kosong `an_nuur_2` di server MySQL milik Anda.
4. Jalankan `php artisan key:generate`, `php artisan migrate --seed`, `php artisan storage:link`, dan `npm run build`.
5. Buat akun dengan `php artisan panti:admin email-pengurus@example.com`. Password dimasukkan melalui prompt tersembunyi. Tidak ada registrasi admin publik.
6. Jalankan `php artisan serve`.

Untuk akun demonstrasi lokal baru tersedia `php scripts/local-admin.php`. Perintah ini hanya bekerja pada APP_ENV=local dan tidak mengganti password akun yang sudah ada.

## Modul yang tersedia

- Profil, sejarah, visi-misi, struktur organisasi, kontak/lokasi, kegiatan, berita, dan galeri. Konten sebenarnya diisi pengurus; tidak ada statistik anak atau transaksi fiktif.
- Pengelolaan pengguna, kategori donasi/bantuan, jenis kunjungan, dan donatur.
- Kebutuhan dengan target, satuan, jumlah terpenuhi manual, dan status yang dihitung dari realisasi.
- Donasi tanpa akun: nominal tetap/custom minimal Rp5.000; referensi pembayaran dan tautan riwayat pribadi.
- Bantuan beberapa jenis barang per pengajuan; konfirmasi, penolakan, penerimaan, dan pengaitan opsional dengan kunjungan.
- Kunjungan 07.00–21.00 WIB, penjadwalan ulang dengan persetujuan ulang yang dicatat admin, peringatan benturan, buku tamu langsung, riwayat, dan kalender.
- Dashboard dengan statistik dan grafik bulanan, laporan web/PDF/Excel, dan log perubahan status serta notifikasi.

Kategori awal dari seeder adalah nilai awal yang dapat diubah di master data, bukan hasil wawancara final.

## Midtrans Sandbox

Isi `MIDTRANS_SERVER_KEY` pada `.env`. Implementasi saat ini sengaja hanya menggunakan endpoint sandbox. Tidak ada mode pembayaran produksi.

Callback: `POST /webhooks/midtrans`. Midtrans tidak dapat mengakses localhost secara langsung. Untuk pengujian end-to-end, buat tunnel HTTPS sementara ke port 8000, set `APP_URL` ke alamat tunnel, lalu daftarkan `https://alamat-tunnel/webhooks/midtrans` pada dashboard sandbox. Setelah mengubah konfigurasi, jalankan `php artisan config:clear` dan mulai ulang worker jika ada.

Signature SHA-512 dan nominal diverifikasi. Callback berulang tidak menghitung donasi dua kali atau mengirim notifikasi ganda. Redirect browser tidak mengubah transaksi menjadi berhasil. Status pembayaran tidak dapat dipaksa berhasil melalui dashboard.

Jika permintaan pembuatan pembayaran mengalami timeout, sistem tidak membuat order baru secara otomatis; pengurus perlu merekonsiliasi referensi order di Midtrans terlebih dahulu. Ini mencegah order ganda ketika hasil permintaan belum diketahui.

`SANDBOX_FEE_RUPIAH` boleh diisi nominal biaya tetap khusus simulasi (misalnya 1000). Biaya ditandai **Simulasi sandbox**. Jika kosong, biaya belum diketahui; transaksi tersebut belum masuk total bersih. Biaya aktual produksi belum diimplementasikan dan tidak boleh disimpulkan dari simulasi.

## Fonnte

Isi `FONNTE_TOKEN`, kemudian ubah `WHATSAPP_ENABLED=true` hanya ketika siap mengirim pesan ke nomor penguji. Jalankan:

```console
php artisan queue:work --tries=1
```

Tanpa konfigurasi, log ditandai Nonaktif dan tidak ada pesan yang dikirim. Diterima provider berarti permintaan diterima Fonnte, bukan bukti pesan sudah dibaca. Hasil timeout ditandai Tidak diketahui dan tidak diulang otomatis. Persetujuan pengunjung atas jadwal ulang diberikan lewat WhatsApp secara manual; admin mencatatnya sebelum menyetujui jadwal baru.

## Pengujian

```console
php artisan test
npm run build
node scripts/check-browser.mjs
```

Tes PHP memakai SQLite in-memory yang terpisah dari database lokal. Mencakup validasi, pembatasan akses, autentikasi, token riwayat, alur bantuan/kunjungan, signature dan idempotensi callback, serta ekspor. HTTP provider dipalsukan dalam tes; tes tersebut bukan bukti integrasi provider langsung sudah berhasil.

Pemeriksaan browser menggunakan Chrome terpasang dan `@playwright/test`, server lokal yang sedang hidup, serta akun lokal dari `.runtime/admin-access.json`. Menguji lebar 360, 390, 768, dan 1440 piksel, overflow halaman, pilihan nominal, penambahan barang, menu admin, dan kesalahan JavaScript. Screenshot disimpan di `.runtime/screenshots`.

## Batas dan tindak lanjut

- Isi profil, kontak, foto, dan statistik penerima manfaat harus diberikan/divalidasi pengurus.
- Integrasi langsung Midtrans dan Fonnte menunggu credential pemilik serta tunnel callback. Tidak ada transaksi nyata atau pesan WhatsApp yang dikirim selama setup.
- Riwayat publik hanya per transaksi/pengajuan menggunakan tautan rahasia. Nomor WhatsApp dipakai untuk pengelompokan kontak, bukan bukti kepemilikan.
- Pengajuan bantuan dicatat diterima sekaligus; penerimaan parsial bertahap belum termasuk.
- Laporan donasi berstatus Berhasil menggunakan tanggal pembayaran. Semua status menggunakan tanggal dibuat. Bantuan Diterima menggunakan tanggal diterima; semua status menggunakan tanggal pengajuan. Kunjungan menggunakan tanggal jadwal.
- Hosting/deployment dan UAT pengurus belum dilakukan. Semua pengurus memiliki tingkat akses yang sama.
- Jangan mengekspos root project, `.env`, `.runtime`, atau server MySQL ke internet. Tunnel pengujian harus mengarah ke server Laravel dengan document root public.
