# Dokumentasi Pembaruan Sistem dan Naskah Tugas Akhir (TA) / Skripsi

**Studi Kasus:** Panti Asuhan NU An-Nuur 2 Kota Kediri  
**Tanggal Pembaruan:** 20 Oktober 2026  
**Status Implementasi:** Tahap 1 s.d. Tahap 6 Selesai (100% Teruji)  

---

## 1. Rekomendasi Pembaruan Judul Tugas Akhir

Berdasarkan arahan revisi penguji/pembimbing (penghapusan fitur kunjungan dan penambahan aplikasi mobile donatur tetap serta transparansi dana kas panti), berikut adalah opsi formulasi judul skripsi baru:

1. **Rekomendasi Utama:**  
   > **"Rancang Bangun Sistem Informasi Pengelolaan Donasi dan Transparansi Dana Berbasis Web dan Mobile Menggunakan Payment Gateway (Studi Kasus: Panti Asuhan NU An-Nuur 2 Kota Kediri)"**
2. **Alternatif Penekanan Multiplatform:**  
   > **"Pengembangan Sistem Informasi Donasi dan Rekonsiliasi Kas Panti Asuhan Berbasis Web dan Mobile Flutter Terintegrasi Midtrans"**

---

## 2. Matriks Perubahan Ruang Lingkup (Sebelum vs Sesudah)

| Aspek | Sistem Sebelum Revisi | Sistem Setelah Revisi |
|---|---|---|
| **Platform** | Hanya Web Responsive (Laravel Blade) | **Multiplatform**: Web Admin Pengurus + Aplikasi Mobile Android (Flutter) untuk Donatur Tetap |
| **Modul Kunjungan** | Formulir kunjungan publik, kalender, buku tamu, persetujuan admin | **Dihapus Sepenuhnya** (seluruh tabel lama diarsipkan rapi ke `arsip_kunjungan` demi integritas data historis) |
| **Klasifikasi Donatur** | Semua donatur setara tanpa sistem akun | **Terbagi Dua**: <br>1. *Donatur Biasa*: Berdonasi via web tanpa akun.<br>2. *Donatur Tetap*: Memiliki akun terverifikasi, login via mobile, riwayat donasi tersimpan |
| **Manajemen Pengeluaran** | Tidak ada pencatatan pengeluaran | **Tersedia Lengkap**: Penginputan draft & pembukuan pengeluaran riil, kategori, saldo awal bulanan, dan unggah nota/bukti fisik |
| **Transparansi Kas** | Hanya menampilkan grafik donasi masuk | **Transparansi Arus Kas Riil**: Menghitung saldo awal + donasi online Midtrans + donasi manual - pengeluaran dibukukan |
| **Keamanan Bukti Nota** | Belum ada | Fleksibel: Pengurus dapat mengatur bukti pengeluaran bersifat privat atau dipublikasikan ke donatur (`is_public`) |
| **Ekspor Laporan** | Hanya donasi dan bantuan | Ekspor Donasi, Bantuan, Pengeluaran, dan Rekapitulasi Arus Kas dalam format **PDF & Excel** |

---

## 3. Aktor Sistem dan Hak Akses (Use Case)

### A. Donatur Biasa (Masyarakat Umum - Akses Web)
1. Melihat profil panti, program donasi, dan transparansi kas bulanan.
2. Melakukan donasi online langsung via Midtrans (QRIS, VA, E-Wallet) tanpa registrasi.
3. Melakukan pengajuan bantuan barang perlengkapan santri.
4. Melacak status donasi/bantuan melalui kode tracking unik.

### B. Donatur Tetap (Akses Aplikasi Mobile Flutter)
1. Login menggunakan akun yang telah diverifikasi oleh pengurus via Laravel Sanctum Token.
2. Melihat dashboard donasi pribadi (total dana yang telah disalurkan, jumlah transaksi berhasil).
3. Melakukan penyaluran donasi baru secara cepat dengan peruntukan kategori dan preset nominal.
4. Melakukan checkout pembayaran Midtrans di dalam aplikasi menggunakan WebView terintegrasi.
5. Memantau riwayat donasi pribadi secara real-time beserta status transaksinya.
6. Memantau transparansi kas panti bulanan (arus kas masuk, arus kas keluar, saldo berjalan, dan meninjau foto nota/kuitansi publik).
7. Mengelola profil (nama, WhatsApp) dan memperbarui kata sandi.

### C. Pengurus Panti / Bendahara / Administrator (Akses Web Admin)
1. Mengelola master data donatur, klasifikasi jenis donatur (`biasa` vs `tetap`), dan aktivasi akun donatur.
2. Mencatat donasi manual (penerimaan kas tunai langsung atau transfer bank manual panti).
3. Mengelola saldo awal kas bulanan yang disahkan panti.
4. Mencatat dan membukukan pengeluaran operasional panti beserta bukti kuitansi fisik.
5. Memonitor rekapitulasi arus kas dan mengekspor laporan ke PDF/Excel.
6. Mengonfirmasi bantuan barang dari donatur.
7. Memelihara konten website (kegiatan santri, profil panti).

---

## 4. Arsitektur Teknis Sistem

```
+-------------------------------------------------------------------------------+
|                                LAPISAN KLIEN                                  |
|                                                                               |
|   [ Web Browser (Publik & Pengurus) ]          [ Flutter Mobile App ]         |
|      - Form Donasi Publik Web (Guest)             - Autentikasi Sanctum       |
|      - Web Admin Pengurus (Blade + Vite)          - Dashboard Donatur Tetap   |
|      - Rekonsiliasi & Ekspor Laporan              - Transparansi Dana Panti   |
|                                                   - Midtrans WebView Checkout |
+---------------------------------------+---------------------------------------+
                                        | (HTTPS / REST API JSON)
+---------------------------------------v---------------------------------------+
|                       LAPISAN BACKEND (LARAVEL 11 / 13)                       |
|                                                                               |
|   [ Controllers Web & API v1 ]                                                |
|      - AuthController, ProfileController, DonationController                   |
|      - TransparencyController, PaymentController, ExpenseController           |
|                                                                               |
|   [ Domain Services ]                                                         |
|      - MidtransService: Snap Token creation & Webhook Idempotency             |
|      - CashflowService: Formula Rekonsiliasi Saldo Kas Riil                   |
|      - DonorAccountService: Aktivasi, Token Revocation, Audit Trail           |
|                                                                               |
|   [ Security & Data Integrity ]                                               |
|      - Laravel Sanctum Bearer Token Guard (`auth:sanctum`)                    |
|      - Anti-tampering Owner Lock (Input donatur_id dari klien ditolak)        |
|      - Sensor Bukti Fisik Privat (HTTP 403 jika `is_public == false`)         |
+-------------------+---------------------------------------+-------------------+
                    |                                       |
+-------------------v-------------------+   +---------------v-------------------+
|            DATABASE MYSQL             |   |      MIDTRANS PAYMENT GATEWAY     |
|   - donaturs, users, donasis          |   |   - QRIS, GoPay, ShopeePay        |
|   - pembayarans, pengeluarans         |   |   - BCA, BNI, BRI, Mandiri VA     |
|   - saldo_awals, audit_logs           |   |   - Webhook Real-time Callback    |
|   - arsip_kunjungan (arsip terisolasi)|   +-----------------------------------+
+---------------------------------------+
```

---

## 5. Formula Perhitungan Transparansi Keuangan

Sistem menjamin angka transparansi kas panti bersifat dapat dipertanggungjawabkan (*auditable*) melalui rumus:

$$\text{Saldo Akhir Kas} = \text{Saldo Awal Bulan} + \text{Penerimaan Bersih} - \text{Pengeluaran Dibukukan}$$

Di mana:
1. **$\text{Saldo Awal Bulan}$**: Saldo kas riil pembukaan bulan berjalan yang ditetapkan dan diverifikasi pengurus (`saldo_awals`).
2. **$\text{Penerimaan Bersih}$**:
   - Total donasi online berstatus `'Berhasil'` via Midtrans (setelah dikurangi biaya transaksi jika penanggung biaya adalah donatur).
   - Ditambah total donasi manual tunai/transfer langsung yang disahkan pengurus.
3. **$\text{Pengeluaran Dibukukan}$**: Total pengeluaran panti yang berstatus `'dibukukan'` (pengeluaran berstatus `'draft'` tidak memotong saldo).

---

## 6. Hasil Pengujian Sistem (Testing Suite)

### A. Pengujian Backend Laravel (PHPUnit & Pest)
- **Total Pengujian**: 50 Test Cases, **457 Assertions**.
- **Hasil**: **100% LULUS (0 Failures, 0 Errors)**.
- **Cakupan Pengujian**:
  1. `DonorAccountTest`: Verifikasi donatur tetap, pencegahan duplikasi akun, isolasi sesi, token revocation saat ganti password/email.
  2. `ExpenseAndCashflowTest`: Siklus hidup pengeluaran (draft vs dibukukan vs pembatalan), saldo awal, donasi manual, dan privasi bukti nota.
  3. `MobileApiTest`: Autentikasi token Sanctum, proteksi endpoint, snapshot identitas otomatis, pencegahan akses transaksi silang, dan sensor bukti privat.
  4. `PantiTest`: Pembayaran Midtrans, idempotensi webhook, tracking donasi publik.
  5. `VisitRemovalTest`: Memastikan seluruh rute kunjungan mati dan data lama tersimpan aman di tabel arsip.
  6. `ReportExportTest`: Validasi laporan PDF dan Excel.

### B. Pengujian Aplikasi Mobile Flutter (`annuur_mobile`)
- **Statistik Analisis Kode**: `flutter analyze` $\rightarrow$ **No issues found (0 warnings, 0 errors)**.
- **Unit & Widget Testing**: 7 Test Cases $\rightarrow$ **100% LULUS**.
- **Cakupan Pengujian**:
  1. Serialisasi & deserialisasi model data API (`UserModel`, `DonaturModel`, `DonationModel`, `CashflowSummaryModel`, `ExpenseModel`).
  2. Formatting mata uang Rupiah (`Formatters.currency`) dan tanggal lokal Indonesia (`Formatters.date`).
  3. UI Widget Testing: Rendering elemen brand pada `SplashScreen` dan form validasi `LoginScreen`.
  4. Pengujian alur navigasi checkout Midtrans WebView.

---

## 7. Panduan Menjalankan Sistem

### A. Menjalankan Backend Laravel
1. Pastikan Web Server Apache & MySQL (Laragon/XAMPP) aktif.
2. Jalankan migrasi dan seeder:
   ```bash
   php artisan migrate
   ```
3. Jalankan server lokal:
   ```bash
   php artisan serve --port=8000
   ```
4. Jalankan antrean background (notifikasi WhatsApp/Email):
   ```bash
   php artisan queue:work
   ```
5. Untuk pengujian Midtrans WebView dari HP Android fisik, gunakan tunnel ngrok:
   ```bash
   ngrok http 8000
   ```
   *(Salin URL ngrok HTTPS ke variabel `APP_URL` di file `.env` Laravel dan `AppConfig.apiBaseUrl` di Flutter).*

### B. Menjalankan Aplikasi Mobile Flutter
1. Dari direktori web `projectTA`, masuk ke project Flutter yang terpisah:
   ```bash
   cd ../annuur_mobile
   ```
2. Ambil seluruh dependensi:
   ```bash
   flutter pub get
   ```
3. Hubungkan perangkat Android fisik (via USB debugging) atau jalankan Emulator Android.
4. Jalankan aplikasi:
   ```bash
   flutter run --dart-define=API_BASE_URL=https://HOST-BACKEND/api/v1
   ```
5. Kredensial Donatur Tetap untuk Demo:
   - **Email:** `donatur.tetap@annuur.id` (atau buat melalui menu Admin Donatur di Web)
   - **Password:** `password`
