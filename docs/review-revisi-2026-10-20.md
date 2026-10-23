# Pemeriksaan revisi — 20 Oktober 2026

## Status perbaikan

Kelima temuan di bawah sudah ditangani. Uraian temuan dipertahankan sebagai
catatan kondisi sebelum perbaikan.

- Flutter membaca respons root login, profil, paginator donasi, dan ringkasan sesuai
  Laravel. Payload profil memakai `nama`; respons update memuat status akun.
- URL kategori, ringkasan kas, dan status pembayaran diselaraskan. Navigasi pembayaran
  membawa ID donasi, bukan order ID. Model pengeluaran mengikuti kategori berbentuk
  objek, `keperluan`, dan `has_public_proof`; seluruh halaman pengeluaran dimuat.
- `PUT /api/v1/profile/password` memeriksa password lama, konfirmasi, dan minimal
  12 karakter. Perubahan mencabut token perangkat lain, sesi web, dan token reset,
  serta mencatat audit. Perangkat yang mengganti password tetap masuk.
- Redirect pembayaran hanya memicu pemeriksaan status backend. Pesan berhasil
  ditampilkan ketika backend menyatakan `Berhasil`; kegagalan request tidak dianggap sukses.
- Penerimaan kas memakai nominal online dikurangi biaya gateway yang tercatat.
  Donasi manual tidak dipotong biaya gateway. Perlakuan yang sama berlaku untuk
  saldo bawaan sebelum periode. Biaya gateway tersebut jangan dicatat lagi sebagai
  pengeluaran manual. Biaya yang belum diketahui membuat saldo masih sementara.
- Saldo pembukaan dibatasi tanggal awal periode (atau tanggal akhir bila awal tidak
  diisi). Bila pembukuan baru dimulai di dalam periode, digunakan pembukaan pertama
  di periode tersebut. Saldo pembukaan masa depan tidak diterapkan ke laporan lampau.
- Folder Flutter tetap terpisah di `D:\apps\laragon\www\annuur_mobile`.

Verifikasi setelah perbaikan: **52 tes Laravel / 481 assertions lulus**, **10 tes
Flutter lulus**, `flutter analyze` tanpa masalah, dan build Vite berhasil.
Tes tambahan mencakup kontrak HTTP mobile, pagination pengeluaran, profil/password
dan pencabutan sesi, biaya gateway, saldo bawaan, serta saldo pembukaan lintas periode.
Integrasi pembayaran nyata dan UAT di perangkat belum dijalankan.

## Lingkup dan pemisahan folder

Memeriksa source Laravel/API, kontrak repository Flutter, akun, dan perhitungan kas.
Ini pemeriksaan kode dan tes otomatis, belum UAT perangkat atau transaksi provider nyata.

- Web/API tetap di `D:\apps\laragon\www\projectTA`.
- Mobile dipindah dari `projectTA\annuur_mobile` ke `D:\apps\laragon\www\annuur_mobile`.
- Seluruh 95 file pada saat pemindahan dipertahankan; dependensi Flutter diregenerasi dari lokasi baru.
- Mobile semula untracked di repository web. Setelah dipisah, source mobile berada di luar cakupan Git web.
- Petunjuk menjalankan aplikasi diperbarui. Integrasi tetap melalui URL API, tanpa ketergantungan path source web.

## Temuan yang perlu diperbaiki

### P1 — Login, pemulihan sesi, dan riwayat mobile gagal membaca respons API

`annuur_mobile/lib/data/repositories/auth_repository.dart:18` membaca `body['data']`,
sedangkan `app/Http/Controllers/Api/V1/AuthController.php` mengirim `token`, `user`,
dan `donor` pada root JSON. ApiClient mengembalikan body tanpa membungkusnya.
Login sukses di server berakhir dengan kesalahan cast di mobile.
`AuthProvider.initialize/updateProfile` juga mengharapkan pembungkus yang tidak ada.
Repository donasi membaca paginator dan ringkasan dari `data`, sementara backend
mengirim paginator dan ringkasan di root; `data` paginator sendiri berupa list.
Samakan kontrak dan tambah tes dengan JSON respons backend yang sebenarnya.

### P1 — Sejumlah URL mobile tidak tersedia di Laravel

Bandingkan repository mobile dengan `routes/api.php`:

| Mobile memanggil | Backend tersedia |
| --- | --- |
| `/reference/donation-categories` | `/references/donation-categories` |
| `/payment/status/{orderId}` | `/donations/{id}/status` |
| `/transparency/cashflow` | `/transparency/summary?month=...&year=...` |
| `PUT /profile/password` | Belum ada endpoint |

Akibatnya kategori donasi, pengecekan pembayaran, arus kas bulanan, dan ganti password
tidak dapat berjalan sesuai UI. Sesuaikan juga parameter ID serta model JSON,
bukan hanya mengganti string URL.

### P2 — Edit profil ditolak dan respons belum cocok dengan model

`auth_repository.dart:43` mengirim `name`, tetapi `ProfileController::update`
mewajibkan `nama`. Permintaan valid dari UI mendapat 422.
Respons update juga tidak menyertakan `user.account_status`, yang diwajibkan
oleh `UserModel.fromJson`. Perbaikan perlu mencakup payload dan respons.

### P1 — Saldo kas belum mengurangi biaya gateway

`app/Services/CashflowService.php:74` menghitung biaya gateway, tetapi saldo akhir
pada baris 100 menggunakan seluruh nominal bruto. Perhitungan penerimaan sebelum
periode juga menggunakan nominal bruto. Ini berbeda dari rencana penerimaan online
bersih. Contoh saldo awal 0, donasi 100.000, biaya 2.000, tanpa pengeluaran akan
menampilkan 100.000 meskipun kas bersih 98.000. Tentukan perlakuan biaya yang
konsisten dan hindari pengurangan ganda bila biaya dicatat sebagai pengeluaran.

### P2 — Saldo pembukaan terbaru diterapkan ke periode lampau

`CashflowService::getOpeningBalance` mengambil tanggal pembukaan paling baru
tanpa batas periode laporan. Ketika terdapat saldo pembukaan lebih baru daripada
bulan yang ditinjau, saldo tersebut tetap digunakan dan effective_start bisa
melewati end_date. Pilih saldo pembukaan yang relevan dengan periode serta uji
laporan sebelum dan sesudah tanggal pembukaan.

## Hasil verifikasi sebelum perbaikan

- Laravel: 50 tes lulus, 457 assertions.
- Vite: build berhasil setelah pemisahan folder.
- Flutter di lokasi baru: `pub get` berhasil, `flutter analyze` tanpa masalah,
  dan seluruh 7 tes unit/widget lulus.

Tes yang lulus saat ini belum mencakup ketidakcocokan kontrak Flutter–Laravel di atas.
Pada pemeriksaan awal, perubahan berfokus pada pemisahan folder dan dokumentasi.
Perbaikan lanjutan dan hasil verifikasi terbaru tercantum pada bagian Status perbaikan.
