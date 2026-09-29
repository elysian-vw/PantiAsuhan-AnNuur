# PROJECT PLAN TUGAS AKHIR

## 1. Informasi Proyek

**Judul:**  
Sistem Informasi Profil dan Pengelolaan Donasi serta Bantuan Terintegrasi Payment Gateway dan WhatsApp Gateway pada Panti Asuhan NU An-Nuur 2 Kota Kediri

**Jenis Sistem:**  
Sistem Informasi Berbasis Web

**Objek:**  
Panti Asuhan NU An-Nuur 2 Kota Kediri

**Framework Utama:**  
Laravel

**Database:**  
MySQL

**Frontend:**  
Blade + Tailwind CSS + Alpine.js

---

## 2. Latar Belakang Singkat

Panti Asuhan NU An-Nuur 2 Kota Kediri membutuhkan media informasi berbasis web untuk memperkenalkan profil panti, mempublikasikan kegiatan, menyampaikan kebutuhan panti, serta mempermudah masyarakat dalam memperoleh informasi.

Selain itu, pengelolaan donasi, bantuan, dan kunjungan perlu dilakukan secara lebih terstruktur. Sistem akan menyediakan layanan donasi digital yang terintegrasi dengan Payment Gateway serta WhatsApp Gateway untuk mengirimkan notifikasi otomatis kepada donatur maupun pengunjung.

---

## 3. Tujuan Sistem

1. Menyediakan website profil sebagai media informasi dan publikasi Panti Asuhan NU An-Nuur 2 Kota Kediri.
2. Membantu pengurus mengelola data donasi dan donatur secara terstruktur.
3. Membantu pengurus mengelola bantuan berupa barang.
4. Menampilkan kebutuhan panti kepada masyarakat.
5. Memfasilitasi pembayaran donasi secara digital melalui Payment Gateway.
6. Mengirimkan notifikasi otomatis melalui WhatsApp Gateway.
7. Memfasilitasi pendaftaran dan pengelolaan kunjungan.
8. Membantu pengurus membuat laporan donasi, bantuan, dan kunjungan.
9. Mempermudah publikasi kegiatan dan dokumentasi panti.

---

## 4. Aktor Sistem

### 4.1 Pengunjung

Pengunjung merupakan masyarakat umum yang mengakses website tanpa harus melakukan login.

Hak akses:
- Melihat halaman beranda.
- Melihat profil panti.
- Melihat visi dan misi.
- Melihat struktur organisasi.
- Melihat kegiatan panti.
- Melihat berita.
- Melihat galeri.
- Melihat kebutuhan panti.
- Melihat informasi kontak dan lokasi.
- Mengajukan kunjungan.

### 4.2 Donatur

Donatur merupakan masyarakat yang memberikan donasi uang atau bantuan barang.

Hak akses:
- Melihat informasi panti.
- Melihat kebutuhan panti.
- Memilih jenis donasi.
- Memilih nominal donasi yang disediakan.
- Memasukkan nominal donasi sendiri.
- Melakukan pembayaran melalui Payment Gateway.
- Mengajukan bantuan barang.
- Menerima notifikasi melalui WhatsApp.
- Mengisi pendaftaran kunjungan jika diperlukan.

### 4.3 Admin/Pengurus

Admin merupakan pengurus panti yang memiliki akses ke dashboard sistem.

Hak akses:
- Login.
- Mengelola profil panti.
- Mengelola struktur organisasi.
- Mengelola kegiatan.
- Mengelola berita.
- Mengelola galeri.
- Mengelola kebutuhan panti.
- Mengelola data donatur.
- Mengelola data donasi.
- Mengelola bantuan barang.
- Mengelola kunjungan dan buku tamu.
- Melihat status pembayaran.
- Mengelola notifikasi.
- Melihat dashboard statistik.
- Membuat laporan.

---

## 5. Modul Sistem

### 5.1 Modul Website Profil

Fitur:
- Beranda.
- Tentang panti.
- Sejarah panti.
- Visi dan misi.
- Struktur organisasi.
- Kontak.
- Lokasi.
- Statistik singkat penerima manfaat.

Tujuan:
Sebagai media informasi dan promosi Panti Asuhan NU An-Nuur 2 Kota Kediri.

### 5.2 Modul Kegiatan dan Publikasi

Fitur:
- Tambah kegiatan.
- Edit kegiatan.
- Hapus kegiatan.
- Publikasi kegiatan.
- Upload foto kegiatan.
- Berita/informasi.
- Galeri dokumentasi.

Data kegiatan:
- Judul.
- Tanggal.
- Lokasi.
- Deskripsi.
- Foto.
- Status publikasi.

Kegiatan yang dipublikasikan oleh admin akan otomatis tampil pada website publik.

### 5.3 Modul Kebutuhan Panti

Fitur:
- Tambah kebutuhan.
- Edit kebutuhan.
- Hapus kebutuhan.
- Menentukan kategori kebutuhan.
- Menentukan target kebutuhan.
- Mencatat jumlah yang telah terpenuhi.
- Mengubah status kebutuhan.

Contoh status:
- Dibutuhkan.
- Sebagian terpenuhi.
- Terpenuhi.

### 5.4 Modul Donasi Uang

Fitur:
- Form donasi.
- Data donatur.
- Kategori donasi.
- Pilihan nominal.
- Nominal custom.
- Payment Gateway.
- Status transaksi.
- Riwayat transaksi.

Contoh pilihan nominal:
- Rp25.000.
- Rp50.000.
- Rp100.000.
- Rp250.000.
- Rp500.000.
- Nominal lainnya.

Status transaksi:
- Menunggu pembayaran.
- Berhasil.
- Gagal.
- Kedaluwarsa.

Alur:

```text
Donatur
→ Memilih jenis donasi
→ Memilih/mengisi nominal
→ Mengisi data donatur
→ Membuat transaksi
→ Payment Gateway
→ Pembayaran
→ Payment Gateway mengirim status
→ Sistem memperbarui transaksi
→ WhatsApp Gateway mengirim notifikasi
```

### 5.5 Modul Bantuan Barang

Fitur:
- Pengajuan bantuan barang.
- Data pemberi bantuan.
- Kategori bantuan.
- Jumlah barang.
- Satuan barang.
- Tanggal bantuan.
- Status bantuan.
- Riwayat bantuan.

Status:
- Diajukan.
- Dikonfirmasi.
- Diterima.
- Ditolak.

### 5.6 Modul Kunjungan

Fitur:
- Pendaftaran kunjungan online.
- Pencatatan tamu langsung oleh admin.
- Persetujuan kunjungan.
- Penjadwalan ulang.
- Penolakan kunjungan.
- Buku tamu digital.
- Riwayat kunjungan.
- Kalender kunjungan.
- Notifikasi WhatsApp.

Data:
- Nama penanggung jawab.
- Nomor WhatsApp.
- Instansi/komunitas.
- Jumlah peserta.
- Tanggal kunjungan.
- Jam kunjungan.
- Tujuan kunjungan.
- Jenis kegiatan.
- Catatan.

Status:
- Menunggu.
- Disetujui.
- Dijadwalkan ulang.
- Ditolak.
- Hadir.
- Selesai.

Jika pengunjung membawa bantuan, data bantuan dapat dikaitkan dengan data kunjungan.

### 5.7 Modul Payment Gateway

Tujuan:
Memfasilitasi pembayaran donasi secara digital.

Fungsi utama:
- Membuat transaksi pembayaran.
- Menghasilkan metode pembayaran.
- Menerima callback/webhook pembayaran.
- Memperbarui status transaksi secara otomatis.
- Menyimpan ID transaksi Payment Gateway.

Rencana layanan:
- Midtrans atau Payment Gateway lain yang sesuai.

### 5.8 Modul WhatsApp Gateway

Tujuan:
Mengirimkan notifikasi otomatis.

Jenis notifikasi:
- Donasi berhasil.
- Donasi gagal/kedaluwarsa jika diperlukan.
- Bantuan barang dikonfirmasi.
- Pengajuan kunjungan diterima.
- Pengajuan kunjungan ditolak.
- Jadwal kunjungan diubah.

### 5.9 Dashboard Admin

Informasi yang ditampilkan:
- Total donasi.
- Total donatur.
- Donasi bulan berjalan.
- Total bantuan barang.
- Total kebutuhan aktif.
- Kunjungan hari ini.
- Pengajuan kunjungan menunggu.
- Jumlah kegiatan.
- Grafik donasi bulanan.

### 5.10 Modul Laporan

Jenis laporan:
- Laporan donasi.
- Laporan bantuan.
- Laporan kunjungan.

Filter:
- Tanggal awal.
- Tanggal akhir.
- Bulan.
- Tahun.
- Status.

Output:
- Tampilan web.
- PDF.
- Excel jika diperlukan.

---

## 6. Struktur Menu Website Publik

```text
Beranda
├── Profil
│   ├── Tentang Kami
│   ├── Sejarah
│   ├── Visi & Misi
│   └── Struktur Organisasi
├── Kegiatan
├── Berita
├── Galeri
├── Kebutuhan Panti
├── Donasi
├── Bantuan Barang
├── Kunjungan
└── Kontak
```

---

## 7. Struktur Menu Dashboard Admin

```text
Dashboard

Master Data
├── Pengguna
├── Kategori Donasi
├── Kategori Bantuan
└── Jenis Kunjungan

Donasi & Bantuan
├── Data Donatur
├── Donasi Uang
├── Bantuan Barang
└── Kebutuhan Panti

Kunjungan
├── Pengajuan Kunjungan
├── Jadwal Kunjungan
├── Buku Tamu
└── Riwayat Kunjungan

Publikasi
├── Profil Panti
├── Struktur Organisasi
├── Kegiatan
├── Berita
└── Galeri

Laporan
├── Laporan Donasi
├── Laporan Bantuan
└── Laporan Kunjungan

Pengaturan
├── Payment Gateway
├── WhatsApp Gateway
└── Pengaturan Website
```

---

## 8. Rancangan Database Awal

```text
users
donatur
kategori_donasi
donasi
payment_transactions
kategori_bantuan
bantuan
kebutuhan
kunjungan
jenis_kunjungan
kegiatan
berita
galeri
pengurus
whatsapp_logs
settings
```

### Relasi Dasar

```text
DONATUR
├── DONASI
│   ├── KATEGORI_DONASI
│   └── PAYMENT_TRANSACTIONS
└── BANTUAN
    └── KATEGORI_BANTUAN

KUNJUNGAN
├── JENIS_KUNJUNGAN
└── BANTUAN (opsional)

KEGIATAN
└── GALERI

USERS
└── Mengelola data pada dashboard
```

---

## 9. Teknologi yang Digunakan

```text
Backend Framework : Laravel
Frontend           : Blade
CSS Framework      : Tailwind CSS
JavaScript         : Alpine.js
Database           : MySQL
Chart              : Chart.js
Calendar           : FullCalendar
Payment Gateway    : Midtrans / layanan sejenis
WhatsApp Gateway   : WhatsApp API / provider gateway
PDF                : DomPDF
Excel              : Laravel Excel
Version Control    : Git
```

---

## 10. Kebutuhan Fungsional Awal

### Admin

Sistem harus dapat:
1. Melakukan autentikasi admin.
2. Mengelola profil panti.
3. Mengelola struktur organisasi.
4. Mengelola kegiatan.
5. Mengelola berita.
6. Mengelola galeri.
7. Mengelola kebutuhan panti.
8. Mengelola data donatur.
9. Melihat transaksi donasi.
10. Melihat status pembayaran.
11. Mengelola bantuan barang.
12. Mengelola pengajuan kunjungan.
13. Mencatat tamu secara langsung.
14. Melihat kalender kunjungan.
15. Mengelola laporan.
16. Melihat dashboard statistik.

### Pengunjung/Donatur

Sistem harus dapat:
1. Menampilkan profil panti.
2. Menampilkan kegiatan.
3. Menampilkan galeri.
4. Menampilkan kebutuhan panti.
5. Memungkinkan pengguna melakukan donasi.
6. Memungkinkan pengguna memilih atau memasukkan nominal donasi.
7. Memproses pembayaran melalui Payment Gateway.
8. Memungkinkan pengguna mengajukan bantuan barang.
9. Memungkinkan pengguna mendaftar kunjungan.
10. Mengirimkan notifikasi WhatsApp sesuai proses yang dilakukan.

---

## 11. Kebutuhan Non-Fungsional

1. Sistem berbasis web.
2. Tampilan responsif untuk desktop dan perangkat mobile.
3. Password pengguna disimpan dalam bentuk hash.
4. Halaman admin hanya dapat diakses oleh pengguna yang telah login.
5. Data transaksi harus disimpan dengan aman.
6. Sistem harus melakukan validasi input.
7. Callback Payment Gateway harus diverifikasi.
8. Sistem memiliki pencatatan status transaksi.
9. Antarmuka dibuat sederhana dan mudah digunakan.
10. Data pribadi penerima manfaat tidak ditampilkan secara terbuka tanpa kebutuhan dan izin.

---

## 12. Prioritas Pengembangan

### Prioritas 1 — Fitur Inti
- Authentication admin.
- Profil panti.
- Donasi uang.
- Data donatur.
- Bantuan barang.
- Payment Gateway.
- WhatsApp Gateway.
- Laporan.

### Prioritas 2 — Fitur Penting
- Kegiatan.
- Galeri.
- Kebutuhan panti.
- Pendaftaran kunjungan.
- Buku tamu digital.

### Prioritas 3 — Fitur Pendukung
- Kalender kunjungan.
- Grafik dashboard.
- Statistik penerima manfaat.
- Export Excel.

---

## 13. Project Timeline

| Minggu | Tahap | Kegiatan | Output |
|---|---|---|---|
| 1 | Analisis Awal | Merapikan hasil wawancara, mengidentifikasi masalah dan ruang lingkup | Daftar masalah dan kebutuhan |
| 2 | Analisis Sistem | Menentukan aktor, kebutuhan fungsional dan non-fungsional | Dokumen kebutuhan sistem |
| 3 | Perancangan Sistem | Membuat Use Case dan Activity Diagram | Diagram sistem |
| 4 | Database | Membuat ERD, relasi dan struktur tabel | ERD dan database design |
| 5 | UI/UX | Membuat wireframe website dan dashboard | Wireframe/mockup |
| 6 | Setup | Setup Laravel, MySQL, authentication dan layout | Project dasar |
| 7 | Website Profil | Profil, kegiatan, berita, galeri dan kebutuhan | Website publik |
| 8 | Donasi & Bantuan | Donatur, donasi, bantuan dan kebutuhan | Modul donasi/bantuan |
| 9 | Kunjungan | Form kunjungan, buku tamu dan jadwal | Modul kunjungan |
| 10 | Integrasi | Payment Gateway dan WhatsApp Gateway | Integrasi eksternal |
| 11 | Laporan & Testing | Dashboard, laporan, Black Box Testing | Sistem siap uji |
| 12 | Finalisasi | UAT, revisi, deployment dan dokumentasi | Sistem final |

---

## 14. Urutan Implementasi

```text
1. Setup Laravel
2. Setup Database
3. Authentication Admin
4. Layout Dashboard
5. Master Data
6. Website Profil
7. Kegiatan & Galeri
8. Kebutuhan Panti
9. Data Donatur
10. Donasi
11. Payment Gateway
12. Bantuan Barang
13. Kunjungan
14. WhatsApp Gateway
15. Dashboard Statistik
16. Laporan
17. Black Box Testing
18. User Acceptance Testing
19. Deployment
```

---

## 15. Alur Utama Donasi

```text
Donatur
↓
Buka halaman donasi
↓
Pilih kategori donasi
↓
Pilih nominal / masukkan nominal sendiri
↓
Isi data donatur
↓
Sistem membuat transaksi
↓
Payment Gateway
↓
Donatur melakukan pembayaran
↓
Payment Gateway mengirim callback
↓
Sistem memperbarui status
↓
Donasi tersimpan sebagai berhasil
↓
WhatsApp Gateway mengirim notifikasi
```

---

## 16. Alur Utama Kunjungan

```text
Pengunjung
↓
Buka halaman kunjungan
↓
Isi formulir
↓
Pilih tanggal dan jam
↓
Kirim pengajuan
↓
Status: Menunggu
↓
Admin memeriksa
↓
Setujui / Jadwalkan Ulang / Tolak
↓
WhatsApp Gateway mengirim notifikasi
↓
Pengunjung datang
↓
Admin menandai Hadir
↓
Status: Selesai
```

---

## 17. Alur Bantuan Barang

```text
Donatur
↓
Isi form bantuan barang
↓
Masukkan jenis dan jumlah bantuan
↓
Kirim pengajuan
↓
Admin menerima data
↓
Admin melakukan konfirmasi
↓
Barang diserahkan
↓
Admin mengubah status menjadi Diterima
↓
Data masuk ke riwayat bantuan
```

---

## 18. Pengujian Sistem

### Black Box Testing

Digunakan untuk menguji fungsi:
- Login.
- CRUD profil.
- CRUD kegiatan.
- Donasi.
- Payment Gateway.
- Bantuan barang.
- Kunjungan.
- WhatsApp Gateway.
- Laporan.

### User Acceptance Testing (UAT)

Responden utama:
- Pengurus/admin Panti Asuhan NU An-Nuur 2 Kota Kediri.

Aspek yang dapat dinilai:
- Kemudahan penggunaan.
- Kesesuaian fitur.
- Kejelasan informasi.
- Kemudahan pengelolaan data.
- Manfaat sistem.

---

## 19. Batasan Sistem Awal

1. Sistem difokuskan pada profil, donasi, bantuan, kegiatan, dan kunjungan.
2. Sistem tidak menangani sistem akademik anak asuh.
3. Sistem tidak menangani penggajian pengurus.
4. Sistem tidak menjadi sistem akuntansi penuh.
5. Pembayaran digital bergantung pada layanan Payment Gateway yang digunakan.
6. Pengiriman WhatsApp bergantung pada layanan/API WhatsApp Gateway.
7. Data pribadi anak/penerima manfaat tidak dipublikasikan secara lengkap pada website.
8. Fitur kunjungan difokuskan pada pendaftaran, persetujuan, jadwal, dan buku tamu digital.

---

## 20. Target Hasil Akhir

Hasil akhir proyek berupa sistem informasi berbasis web yang terdiri dari:

1. Website publik Panti Asuhan NU An-Nuur 2 Kota Kediri.
2. Dashboard admin.
3. Sistem pengelolaan profil dan publikasi kegiatan.
4. Sistem donasi online.
5. Integrasi Payment Gateway.
6. Sistem bantuan barang.
7. Sistem kebutuhan panti.
8. Sistem pendaftaran dan pencatatan kunjungan.
9. Integrasi WhatsApp Gateway.
10. Dashboard statistik.
11. Laporan donasi, bantuan, dan kunjungan.

---

## 21. Catatan untuk AI Developer

Gunakan dokumen ini sebagai konteks utama dalam pengembangan project.

Ketika membuat fitur:
- Jangan menambahkan fitur di luar ruang lingkup tanpa instruksi.
- Gunakan struktur Laravel yang rapi.
- Gunakan migration untuk database.
- Gunakan model relationship Eloquent.
- Gunakan Form Request atau validation Laravel untuk input.
- Gunakan middleware untuk halaman admin.
- Gunakan Blade component/layout jika memungkinkan.
- Gunakan Tailwind CSS untuk styling.
- Pastikan desain responsif.
- Pisahkan service Payment Gateway dan WhatsApp Gateway dari controller.
- Gunakan webhook/callback untuk status pembayaran.
- Simpan log integrasi apabila diperlukan.
- Hindari menyimpan credential API langsung dalam source code; gunakan `.env`.
- Buat fitur secara bertahap berdasarkan prioritas project.
