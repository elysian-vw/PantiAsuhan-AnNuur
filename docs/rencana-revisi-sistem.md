# Rencana Revisi Sistem Donasi Web dan Mobile

Tanggal penyusunan: 20 Oktober 2026 (WIB)  
Objek: Panti Asuhan NU An-Nuur 2 Kota Kediri  
Status: Rencana bertahap; penghapusan modul kunjungan telah diimplementasikan pada 20 Oktober 2026. Lihat catatan pelaksanaan pada bagian 16.

## 1. Tujuan dan kedudukan dokumen

Dokumen ini merinci revisi setelah ujian kelayakan judul: penambahan aplikasi mobile bagi donatur tetap, transparansi penerimaan dan penggunaan dana, serta penghapusan seluruh modul kunjungan.

Dokumen sumber `project_plan_ta_an_nuur_2.md` dipertahankan sebagai catatan rancangan awal. Untuk bagian yang bertentangan, keputusan revisi yang disepakati dalam dokumen ini menjadi acuan implementasi berikutnya. Usulan yang belum disepakati diberi penanda tersendiri.

## 2. Keputusan yang sudah disepakati

1. Sistem tersedia melalui web dan aplikasi mobile dengan backend dan database bersama.
2. Donatur dibagi menjadi donatur biasa dan donatur tetap.
3. Donatur biasa berdonasi melalui formulir web yang sudah tersedia, tanpa kewajiban memiliki akun.
4. Donatur tetap menggunakan aplikasi mobile dan dapat berdonasi langsung dari aplikasi tersebut.
5. Donatur tetap dapat melihat riwayat, total sumbangan pribadi, serta penerimaan dan pengeluaran dana panti.
6. Mobile menggunakan Flutter. Web menggunakan Laravel, Blade, Tailwind CSS, dan Alpine.js yang sudah ada.
7. Antarmuka Flutter menggunakan widget dan tema Flutter. Warna, tipografi, jarak, dan bentuk komponen diselaraskan dengan web; Tailwind CSS tidak digunakan langsung untuk menata widget Flutter.
8. Pendataan pengeluaran ditambahkan pada web admin untuk mendukung transparansi dana.
9. Seluruh fitur kunjungan dihapus, termasuk permintaan kunjungan, penjadwalan, kalender kunjungan, dan buku tamu.
10. Pemulihan akun donatur tetap menggunakan email.

## 3. Kondisi awal berdasarkan kode yang tersedia

| Bagian | Kondisi awal | Arah revisi |
|---|---|---|
| Web | Laravel dengan Blade, Tailwind CSS, dan Alpine.js | Dipertahankan dan disesuaikan |
| Autentikasi | Akun pengurus; belum ada pemisahan peran donatur | Tambahkan akses donatur dengan pembatasan peran |
| Donatur | Data kontak di tabel `donatur`; pengelompokan menggunakan WhatsApp | Tambahkan jenis donatur dan hubungan akun |
| Donasi uang | Formulir tanpa akun, pembayaran Midtrans sandbox, tracking rahasia per transaksi | Pertahankan web dan tambahkan transaksi dari mobile |
| Riwayat | Tautan pribadi per transaksi | Tambahkan riwayat lintas transaksi milik akun terverifikasi |
| Bantuan barang | Pengajuan beberapa item dan penerimaan oleh pengurus | Dipertahankan tanpa hubungan kunjungan |
| Pengeluaran | Belum ada modul pencatatan khusus | Tambahkan pencatatan, bukti, dan jejak perubahan |
| Laporan | Donasi, bantuan, dan kunjungan | Hapus kunjungan; tambahkan pengeluaran dan arus kas |
| API mobile | Belum tersedia rute khusus mobile | Tambahkan API terautentikasi |
| Mobile | Belum ada aplikasi Flutter di struktur project yang diperiksa | Buat aplikasi setelah kontrak API siap |

Kondisi ini merupakan hasil pemeriksaan sumber, bukan hasil pengujian aplikasi berjalan. Perubahan lokal yang sudah ada harus dipertahankan selama implementasi.

## 4. Aktor dan hak akses

| Kemampuan | Pengunjung / donatur biasa | Donatur tetap | Pengurus |
|---|---|---|---|
| Melihat informasi publik panti | Web | Web; konten tambahan di mobile bersifat opsional | Kelola melalui web |
| Donasi melalui form web tanpa akun | Ya | Form publik tetap tersedia; pengaitan ke akun perlu verifikasi | Pantau transaksi |
| Login mobile | Tidak | Ya | Tidak termasuk lingkup mobile |
| Membuat donasi melalui mobile | Tidak | Ya | Tidak termasuk lingkup mobile |
| Melihat status transaksi | Tautan rahasia per transaksi | Transaksi milik akun | Semua transaksi sesuai akses admin |
| Melihat seluruh riwayat pribadi | Tidak tersedia sebagai dashboard akun | Ya | Kelola sesuai kebutuhan operasional |
| Melihat transparansi dana | Belum termasuk lingkup web publik | Ya | Kelola dan periksa |
| Mencatat pengeluaran | Tidak | Tidak | Ya |
| Mengelola donatur tetap | Tidak | Tidak | Ya, mekanisme aktivasi masih usulan |
| Mengajukan bantuan barang | Web seperti saat ini | Dapat menggunakan web; form mobile belum termasuk lingkup | Kelola penerimaan |

Jenis donatur merupakan klasifikasi hubungan dengan panti, sedangkan peran akun menentukan izin akses. Donatur tetap tidak otomatis berarti memiliki tagihan, nominal wajib, atau pembayaran berulang otomatis.

## 5. Fitur per platform

### 5.1 Web publik

- Pertahankan profil, struktur organisasi, kontak, kegiatan, berita, galeri, dan kebutuhan panti.
- Pertahankan formulir donasi, pilihan nominal, pembayaran, dan tracking per transaksi.
- Pertahankan pengajuan bantuan barang dan tracking tanpa opsi kunjungan.
- Hapus menu, tautan, formulir, dan informasi yang menawarkan layanan kunjungan.
- Dashboard transparansi untuk masyarakat umum belum termasuk lingkup revisi.

### 5.2 Web admin

- Klasifikasi donatur biasa/tetap dan pengelolaan hubungan akun donatur.
- Pengelolaan donasi dari web dan mobile pada daftar transaksi bersama, dengan filter asal platform.
- Pencatatan penerimaan di luar gateway, apabila disetujui dalam lingkup operasional.
- Master kategori pengeluaran, transaksi pengeluaran, dan lampiran bukti.
- Ringkasan penerimaan, pengeluaran, dan saldo berdasarkan periode.
- Ekspor laporan web/PDF/Excel mengikuti kemampuan laporan yang sudah tersedia.
- Jejak pembukuan, koreksi, pembatalan, dan pengaitan transaksi ke akun.
- Tidak ada menu maupun proses pengelolaan kunjungan atau buku tamu.

### 5.3 Mobile Flutter

| Halaman | Isi minimum |
|---|---|
| Login dan pemulihan akses | Masuk akun, keluar, dan pemulihan password melalui email terdaftar; mekanisme aktivasi masih usulan |
| Beranda | Ringkasan donasi pribadi, donasi terbaru, akses donasi dan transparansi |
| Donasi | Kategori, nominal, identitas dari akun, ringkasan sebelum pembayaran |
| Pembayaran | Membuka checkout penyedia pembayaran dan menampilkan hasil pembacaan status dari backend |
| Riwayat saya | Daftar transaksi, filter periode/status, detail, dan total donasi berhasil |
| Transparansi dana | Saldo awal periode, penerimaan, pengeluaran, saldo akhir, dan rincian penggunaan dana |
| Profil | Data akun serta perubahan data yang diizinkan |

Navigasi awal yang diusulkan: Beranda, Donasi, Riwayat, Transparansi, Profil. Riwayat bantuan barang di mobile dapat ditambahkan setelah kepemilikan data terverifikasi; jumlah barang tetap dipisahkan dari rupiah.

## 6. Alur utama

### 6.1 Donatur biasa

Web → isi form donasi → sistem membuat transaksi → buka pembayaran → backend menerima konfirmasi terverifikasi → donatur melihat status melalui tautan pribadi.

### 6.2 Donatur tetap

Login mobile → pilih kategori dan nominal → periksa ringkasan → sistem membuat transaksi atas akun → buka pembayaran → backend menerima konfirmasi terverifikasi → status dan ringkasan riwayat diperbarui.

Identitas dan pemilik transaksi diambil dari akun yang login. ID donatur yang dikirim klien tidak boleh menjadi dasar pemberian akses atau penetapan kepemilikan.

### 6.3 Pendaftaran akun donatur tetap — usulan awal

Pengurus memverifikasi data → menetapkan jenis donatur tetap → menghubungkan akun → memberikan undangan aktivasi dengan masa berlaku → donatur mengaktifkan akun dan menetapkan kredensial sendiri.

Pendaftaran akun mandiri, kanal pengiriman undangan, dan bukti verifikasi belum diputuskan. Penetapan jenis donatur tidak langsung memberikan akses terhadap seluruh transaksi lama.

### 6.4 Pemulihan akun melalui email

Donatur memilih lupa password → memasukkan email terdaftar → sistem mengirim tautan reset → donatur membuka tautan dan menetapkan password baru → donatur login kembali.

- Akun donatur tetap wajib memiliki email unik yang kepemilikannya diverifikasi.
- Token reset memiliki masa berlaku dan hanya dapat digunakan sekali. Password tidak dikirim melalui email.
- Respons permintaan reset tidak mengungkap apakah suatu email terdaftar; permintaan dibatasi untuk mencegah pengiriman berlebihan.
- Setelah reset berhasil, sesi/token login lama dicabut.
- Perubahan email memerlukan verifikasi alamat baru sebelum dipakai sebagai tujuan pemulihan.
- Layanan pengiriman email perlu dikonfigurasi dan diuji sebelum fitur digunakan.
- Keputusan ini menetapkan kanal pemulihan akun; kanal undangan aktivasi awal belum diputuskan.

### 6.5 Pengeluaran

Pengurus membuat draft → melengkapi kategori, nominal, tanggal, dan bukti → membukukan → transaksi memengaruhi laporan dan tampil pada transparansi mobile.

Draft boleh diperbaiki. Transaksi yang sudah dibukukan tidak dihapus atau ditimpa tanpa jejak; gunakan pembatalan/koreksi dengan alasan dan pencatat. Koreksi menghasilkan pembalikan atau penggantian yang dapat ditelusuri, tanpa menghitung transaksi dua kali.

## 7. Aturan transaksi dan transparansi

1. Hanya donasi berhasil yang menambah penerimaan serta total sumbangan pribadi. Transaksi menunggu, gagal, dan kedaluwarsa tetap terlihat di riwayat.
2. Status berhasil berasal dari konfirmasi pembayaran yang diverifikasi backend. Redirect atau pesan sukses pada mobile tidak cukup untuk menetapkannya.
3. Callback berulang tidak boleh menggandakan penerimaan, biaya, riwayat, atau notifikasi. Permintaan pembuatan transaksi mobile yang diulang karena gangguan jaringan juga perlu perlindungan duplikasi.
4. Riwayat pribadi hanya menampilkan transaksi yang kepemilikannya telah terverifikasi. Form publik saat ini mengelompokkan kontak berdasarkan WhatsApp; hubungan `donatur_id` tersebut belum boleh otomatis dianggap bukti kepemilikan akun.
5. Transaksi web baru yang menggunakan nomor donatur tetap tidak otomatis muncul di riwayat pribadinya. Gunakan klaim/verifikasi yang dicatat atau arahkan donasi terautentikasi melalui mobile.
6. Penerimaan transparansi mencakup donatur biasa dan tetap. Nama, kontak, dan riwayat pribadi donatur lain tidak ditampilkan.
7. Rumus: **saldo akhir periode = saldo awal periode + penerimaan periode − pengeluaran periode**. Saldo awal periode berikutnya diturunkan dari saldo sebelumnya, bukan dimasukkan berulang setiap bulan.
8. Tentukan tanggal mulai pembukuan dan saldo awal yang disahkan pengurus. Transaksi sebelum tanggal tersebut tidak dihitung kembali apabila sudah tercakup pada saldo pembukaan.
9. Usulan dasar pembukuan: penerimaan bruto dan biaya gateway sebagai pengeluaran terpisah. Biaya gateway yang dicatat otomatis harus ditautkan ke pembayaran dan tidak boleh dicatat kembali secara manual.
10. Biaya yang belum diketahui tidak diasumsikan nol. Saldo yang masih memiliki biaya belum tersedia ditandai sementara. Biaya simulasi sandbox harus dibedakan dari biaya nyata.
11. Saldo adalah saldo dana tercatat, bukan bukti saldo rekening bank secara langsung. Klaim cakupannya mengikuti penerimaan dan pengeluaran yang benar-benar dimasukkan.
12. Penerimaan tunai/transfer langsung memerlukan mekanisme pencatatan dan verifikasi agar cakupan dana lengkap. Jika belum diimplementasikan, batas cakupan harus ditampilkan pada laporan.
13. Nilai uang disimpan dan dihitung secara presisi menggunakan decimal atau satuan rupiah integer; hindari perhitungan keuangan berbasis float.
14. Bantuan barang dilaporkan berdasarkan jumlah dan satuan, terpisah dari saldo uang. Tidak ada konversi otomatis barang ke rupiah.
15. Rincian penggunaan dana minimal berisi tanggal, kategori, keperluan, dan nominal. Bukti asli tersedia bagi pengurus; versi yang dipublikasikan harus disiapkan terpisah bila memuat data pribadi.
16. Penerimaan memakai tanggal pembayaran terverifikasi; pengeluaran memakai tanggal pengeluaran yang dibukukan. Tampilan periode menggunakan WIB dan aturan batas tanggal yang sama pada web, API, serta ekspor.
17. Pembatalan/koreksi harus konsisten di laporan, saldo, dan mobile. Jika pengembalian dana dari gateway dapat terjadi, alur dan pengurang penerimaannya wajib ditentukan sebelum penggunaan pembayaran produksi.

## 8. Rancangan perubahan data

Nama kolom/tabel berikut adalah usulan; detail akhir ditetapkan saat implementasi migration.

| Entitas | Perubahan yang direncanakan |
|---|---|
| `users` | Tambahkan peran pengurus/donatur dan status akses; akun pengurus lama tetap menjadi pengurus |
| `donatur` | Tambahkan `jenis` dengan nilai biasa/tetap dan `user_id` nullable yang unik untuk hubungan satu akun ke satu profil donatur |
| `donasi` | Tambahkan asal platform dan metadata kepemilikan terverifikasi; pertahankan snapshot identitas serta tautan tracking lama |
| Verifikasi kepemilikan | Simpan akun pemilik terverifikasi per transaksi atau tabel pengaitan, metode verifikasi, waktu, dan pengurus pemverifikasi |
| `payment_transactions` | Pertahankan relasi pembayaran; sesuaikan referensi kembali ke aplikasi dan perlindungan permintaan berulang |
| Kategori pengeluaran | Nama, deskripsi, dan status aktif |
| Pengeluaran | Kode, tanggal, kategori, keperluan, nominal, penerima, keterangan, status, pencatat, pembuku, serta relasi koreksi/pembatalan |
| Bukti pengeluaran | Referensi file privat dan metadata; pisahkan versi publik bila tersedia |
| Saldo pembukaan | Tanggal mulai pembukuan, nominal, keterangan, dan pencatat/pengesah |
| Penerimaan manual | Jika disetujui: metode, tanggal terima, nominal, referensi, bukti, verifikasi, serta hubungan donatur opsional |
| Autentikasi mobile | Penyimpanan sesi/token, masa berlaku, pencabutan, dan undangan aktivasi/pemulihan |
| Audit | Catatan tindakan pengurus dan perubahan keuangan penting; evaluasi perluasan `status_histories` atau audit terpisah |
| `bantuan` | Lepaskan relasi `kunjungan_id` setelah data lama diarsipkan dan dependensi kode dibersihkan |
| `kunjungan`, `jenis_kunjungan` | Pindahkan tabel ke `arsip_kunjungan` dan `arsip_jenis_kunjungan`; simpan relasi bantuan di `arsip_bantuan_kunjungan`, lalu lepaskan kolom relasi pada tabel aktif |

Gunakan migration baru untuk perubahan database yang sudah pernah dijalankan. Jangan hanya mengubah migration awal. Jenis donatur lama diasumsikan biasa sampai pengurus memverifikasi klasifikasinya; jangan otomatis membuat akun dari kontak lama.

## 9. Penghapusan modul kunjungan

Penghapusan mencakup seluruh lapisan berikut:

- Rute publik dan admin, validasi pengajuan, controller, model, layanan status, dan notifikasi kunjungan.
- Form kunjungan, field kunjungan pada bantuan, halaman tracking, menu, buku tamu, dan kalender kunjungan.
- Master jenis kunjungan, statistik dashboard, grafik, filter, laporan, serta ekspor kunjungan.
- Relasi database dan kode bantuan terhadap kunjungan.
- Seeder standar/dummy, pengujian, pemeriksaan browser, dan dokumentasi yang masih menawarkan kunjungan.

Sebelum menghapus tabel, buat arsip yang dapat dipulihkan beserta relasi dan riwayat terkait. Lepaskan foreign key secara berurutan. Data historis pada log/audit boleh disimpan sebagai arsip tanpa menyediakan kembali fitur kunjungan. Kalender atau tanggal pada konten kegiatan tidak ikut dihapus karena berbeda dari modul kunjungan.

## 10. Kontrak API mobile yang direncanakan

API menggunakan prefix versi, misalnya `/api/v1`. Daftar ini merupakan rancangan kemampuan, belum endpoint yang tersedia.

| Kelompok | Kemampuan |
|---|---|
| Autentikasi | Login, aktivasi, pemulihan akses, logout/pencabutan sesi |
| Profil | Baca profil dan ubah data yang diizinkan |
| Referensi | Kategori donasi dan pilihan nominal |
| Donasi | Buat transaksi, daftar milik akun, detail milik akun, dan ringkasan total |
| Pembayaran | Buat/ambil checkout untuk transaksi milik akun dan baca status backend |
| Transparansi | Ringkasan periode, agregat penerimaan, daftar/detail pengeluaran yang dipublikasikan |

Respons memiliki format konsisten, validasi, pagination, filter, dan penanganan error. Semua endpoint pribadi memeriksa kepemilikan di server. Endpoint admin memeriksa peran, bukan sekadar status login. Rahasia pembayaran hanya disimpan di backend. Metode membuka checkout dan kembali ke Flutter ditentukan lewat pengujian sandbox sebelum dipakai pada keseluruhan aplikasi.

## 11. Tahapan pelaksanaan dan kriteria selesai

| Tahap | Pekerjaan | Kriteria selesai | Status |
|---|---|---|---|
| 1. Rancangan revisi | Dokumen ini, keputusan terbuka, pemetaan dependensi, rancangan data dan hak akses | Lingkup disepakati; keputusan penghambat tahap berikutnya diselesaikan | Selesai |
| 2. Penghapusan kunjungan | Arsip data, hapus fitur dan dependensi, migration, sesuaikan dokumentasi | Tidak ada rute/menu/proses kunjungan aktif; bantuan, donasi, dan konten tetap berfungsi | Selesai |
| 3. Akun donatur tetap | Peran, jenis donatur, aktivasi, pengaitan akun/transaksi, pembatasan akses | Donatur tidak bisa masuk admin atau membaca transaksi orang lain; form web tanpa akun tetap berfungsi | Selesai |
| 4. Pengeluaran dan laporan | Kategori, bukti, pembukuan, koreksi, saldo pembukaan, kebijakan biaya, dan penerimaan manual | Angka laporan dapat direkonsiliasi; hanya transaksi sah memengaruhi saldo | Selesai |
| 5. API | Implementasi kontrak, autentikasi Sanctum, transaksi, checkout, riwayat, dan transparansi | API teruji termasuk akses, callback berulang, dan penolakan transaksi liar | Selesai |
| 6. Flutter | Struktur aplikasi, tema, halaman, checkout WebView, dan integrasi API | Donatur tetap dapat login, berdonasi, melihat status, riwayat, dan transparansi | Selesai |
| 7. Integrasi dan UAT | Uji lintas platform, migrasi, laporan, dokumen TA, serta validasi pengurus | Kriteria penerimaan terpenuhi dan materi skripsi TA diperbarui | Berjalan (Finalisasi) |

Estimasi waktu dibuat setelah target platform, mekanisme akun, dan cakupan keuangan ditetapkan. Setiap tahap ditinjau sebelum perubahan yang bergantung padanya dikerjakan.

## 12. Skenario pengujian penerimaan

- Donatur biasa tetap dapat berdonasi melalui web tanpa akun dan melihat tracking miliknya.
- Donatur tetap dapat login dan membuat donasi mobile tanpa mengisi ulang identitas.
- Donatur biasa tidak memperoleh akses API donatur tetap hanya dengan mengirim ID atau nomor kontak.
- Donatur tetap tidak dapat mengakses admin maupun transaksi akun lain melalui perubahan parameter API.
- Transaksi lama/baru dari form publik tidak bocor ke akun hanya karena nomor WhatsApp sama.
- Total pribadi hanya menghitung transaksi berhasil yang dimiliki akun secara terverifikasi.
- Redirect sukses palsu tidak mengubah status pembayaran; callback sah memperbarui transaksi sekali.
- Pengulangan permintaan mobile karena jaringan tidak membuat transaksi/penerimaan ganda.
- Pengeluaran draft tidak mengurangi saldo; pembukuan mengurangi sekali; koreksi/pembatalan menjaga jejak dan hasil perhitungan.
- Saldo awal, penerimaan, biaya, pengeluaran, dan saldo akhir cocok antara admin, API, mobile, PDF, dan Excel pada periode yang sama.
- Biaya belum diketahui ditandai; biaya simulasi tidak dilaporkan sebagai biaya nyata; biaya tidak terhitung dua kali.
- Batas periode WIB dan transaksi sekitar pergantian bulan menghasilkan angka yang konsisten.
- Bukti privat tidak dapat dibuka tanpa izin; transparansi tidak menampilkan kontak donatur atau dokumen sensitif.
- Semua rute kunjungan/buku tamu sudah tidak aktif; bantuan barang tetap dapat diajukan dan diterima.
- Migration diuji pada database baru dan salinan data lama; arsip dapat diperiksa tanpa membuka kembali fitur kunjungan.
- Mobile menangani sesi kedaluwarsa, jaringan terputus, pembayaran dibatalkan, dan kembali dari checkout tanpa kehilangan riwayat transaksi.
- Pemulihan mengirim tautan ke email terdaftar; token kedaluwarsa atau yang telah dipakai ditolak. Reset berhasil mencabut sesi lama, dan respons permintaan tidak membocorkan keberadaan akun.
- Pengujian provider memakai sandbox; keberhasilan tes lokal tidak dianggap bukti pembayaran produksi siap.

## 13. Dokumen TA yang perlu diperbarui

- Judul dan jenis sistem: mencerminkan web/mobile serta transparansi dana; redaksi final mengikuti persetujuan pembimbing.
- Latar belakang, tujuan, rumusan masalah, dan batasan masalah.
- Aktor dan use case: pisahkan donatur biasa, donatur tetap, serta pengurus; hapus kunjungan.
- Diagram aktivitas/sekuens untuk pembayaran mobile, pembukuan pengeluaran, dan pemantauan dana.
- ERD, kamus data, arsitektur web–API–Flutter, serta rancangan antarmuka.
- Skenario pengujian, hasil UAT, panduan pengguna, dan petunjuk menjalankan aplikasi.
- README dan panduan operasional agar sesuai kemampuan akhir.

## 14. Keputusan terbuka sebelum tahap terkait

| Keputusan | Usulan awal | Harus dipastikan sebelum |
|---|---|---|
| Target Flutter | Android dahulu; iOS belum termasuk komitmen | Persiapan project mobile dan pengujian perangkat |
| Penetapan donatur tetap | Pengurus memverifikasi dan mengundang aktivasi | Tahap akun |
| Kanal aktivasi awal | Belum diputuskan; pemulihan akun sudah ditetapkan menggunakan email | Tahap akun |
| Kepemilikan transaksi lama | Verifikasi per transaksi/kelompok transaksi yang dapat dibuktikan, dengan audit | Migrasi riwayat ke akun |
| Penerimaan tunai/transfer langsung | Dicatat dan diverifikasi pengurus, terpisah dari konfirmasi otomatis gateway | Tahap laporan dana |
| Saldo awal dan biaya gateway | Saldo pembukaan disahkan; penerimaan bruto dan biaya terpisah | Tahap laporan dana |
| Pembukuan pengeluaran | Pengurus dapat membukukan dengan audit; persetujuan bendahara terpisah belum termasuk | Tahap pengeluaran |
| Publikasi bukti | Rincian pengeluaran tampil; bukti asli privat, versi publik opsional | API transparansi |
| Pengembalian dana | Tetapkan aturan status dan koreksi penerimaan bila didukung provider | Penggunaan pembayaran produksi |
| Penonaktifan akun/perubahan jenis | Cabut sesi saat akses dicabut; pertahankan transaksi dan audit | Tahap akun |

## 15. Batas lingkup awal

Belum termasuk pembayaran berlangganan otomatis, kewajiban nominal bulanan, sistem akuntansi lengkap, rekonsiliasi bank otomatis, penelusuran setiap rupiah donatur ke pengeluaran tertentu, pengajuan bantuan barang melalui mobile, serta push notification mobile. Notifikasi WhatsApp untuk donasi/bantuan yang sudah ada dipertahankan sesuai konfigurasi; notifikasi kunjungan dihapus.

Deployment, kunci provider produksi, publikasi toko aplikasi, dan dukungan iOS membutuhkan pekerjaan tersendiri jika dipilih.

## 16. Catatan pelaksanaan revisi — 20 Oktober 2026
 
### A. Tahap 1 & 2: Penghapusan Modul Kunjungan dan Pengarsipan Data
- Formulir, tracking, master jenis kunjungan, persetujuan, kalender, buku tamu, laporan, dan pengaitan kunjungan pada bantuan telah dihapus dari fitur aktif.
- Navigasi publik/admin, beranda, kontak, FAQ, panduan berbagi, dan metadata halaman disesuaikan.
- Dashboard menampilkan bantuan yang menunggu konfirmasi; dependensi FullCalendar dan tampilan kalender telah dihapus.
- Migration baru mengarsipkan tabel dan hubungan lama (`arsip_kunjungan`, `arsip_jenis_kunjungan`, `arsip_bantuan_kunjungan`) tanpa mengubah migration awal. Riwayat status historis tetap tersimpan.
- Antrean notifikasi kunjungan dibatalkan; job memiliki pengaman agar pesan lama tidak terkirim.

### B. Tahap 3: Akun dan Klasifikasi Donatur Tetap
- Menambahkan kolom `jenis` (`biasa`/`tetap`) pada tabel `donaturs` dan relasi akun login `users` (`role = donatur`, `account_status = aktif/nonaktif`).
- Menambahkan `owner_user_id`, `ownership_verified_at`, `ownership_note`, dan `sumber` (`online`/`manual`) pada tabel `donasis`.
- Implementasi `DonorAccountService` untuk aktivasi donatur via email token, reset password, pencabutan token, dan audit log pemindahan transaksi.
- Donatur tetap tidak dapat mengakses panel admin (`role:admin/pengurus` guard), dan donatur biasa tetap bisa berdonasi tanpa akun via web publik.

### C. Tahap 4: Pengeluaran Kas, Saldo Pembukaan, dan Transparansi Dana
- Menambahkan modul master kategori pengeluaran (`kategori_pengeluarans`) dan transaksi pengeluaran (`pengeluarans`) dengan status draft vs dibukukan, jejak audit, dan unggah bukti kuitansi fisik (`is_public`).
- Menambahkan pencatatan saldo awal/pembukaan kas (`saldo_awals`) per bulan/tahun dengan persetujuan pengurus.
- Menambahkan pencatatan donasi manual (kas tunai / transfer bank langsung panti) untuk menjamin akurasi saldo riil.
- Menambahkan `CashflowService` yang menghitung rekonsiliasi arus kas: `Saldo Akhir = Saldo Awal + Total Donasi Berhasil (Online Bersih + Manual) - Total Pengeluaran Dibukukan`.
- Ekspor laporan arus kas dan pengeluaran dalam format PDF dan Excel.

### D. Tahap 5: Kontrak API Mobile Terautentikasi (Laravel Sanctum)
- Memasang Laravel Sanctum dan mengonfigurasi Bearer token API.
- Menyediakan 19 endpoint pada `/api/v1` (`/auth`, `/profile`, `/reference`, `/donations`, `/payment`, `/transparency`).
- Endpoint transaksi otomatis mengunci identitas pemilik dari token user dan menolak payload liar klien.
- Pengujian Feature Laravel (`MobileApiTest`, `ExpenseAndCashflowTest`, dll): 50 tes lulus dengan 457 assertions.

### E. Tahap 6: Aplikasi Mobile Flutter (`annuur_mobile`)
- Lokasi source dipisahkan dari web: `D:\apps\laragon\www\annuur_mobile`, sejajar dengan `projectTA`. Hasil review integrasi terbaru ada di `review-revisi-2026-10-20.md`; daftar fitur di bawah belum berarti seluruh alur sudah teruji end-to-end.
- Inisialisasi Flutter Android project dengan arsitektur Clean/Provider: `AuthProvider`, `DonationProvider`, `TransparencyProvider`.
- Navigasi modular dengan `go_router` dan proteksi rute terautentikasi / unauthenticated.
- Fitur UI lengkap:
  1. Splash Screen & Login Donatur Tetap.
  2. Beranda/Dashboard: Ringkasan donasi pribadi & ringkasan saldo kas panti berjalan.
  3. Transparansi Kas Panti: Filter bulan/tahun, rincian arus kas, daftar pengeluaran riil, dan modal penampil bukti nota/kuitansi publik.
  4. Riwayat Donasi: Filter status, infinite scroll pagination, dan detail donasi.
  5. Salurkan Donasi Baru: Pilihan kategori, preset nominal (Rp 25k - 1jt) / custom nominal, dan redirect otomatis ke Midtrans WebView.
  6. Checkout WebView: Intersepsi callback pembayaran Midtrans dan konfirmasi real-time.
  7. Profil Donatur: Edit profil, ganti kata sandi, dan logout.
- Pengujian Flutter: `flutter analyze` 0 issues (bersih), dan 7 unit/widget tests lulus 100%.
