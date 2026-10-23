# Perbaikan konsistensi pembayaran dan laporan

Tanggal: 22 Oktober 2026.

## Pembayaran

- Pembatalan order yang pernah diminta ke provider hanya dikonfirmasi setelah respons sukses dengan status bisnis `200`, `transaction_status=cancel`, dan order yang sesuai, atau setelah sinkronisasi status terverifikasi.
- Kegagalan HTTP, penolakan bisnis, timeout, dan konfigurasi provider yang tidak tersedia tidak lagi membatalkan donasi lokal secara otomatis. API mengembalikan 503 dengan pesan pembatalan belum terkonfirmasi.
- Order yang belum pernah diminta (`created`, tanpa URL/token pembayaran) bisa dibatalkan lokal. Penguncian dan perubahan status atomik mencegah checkout yang bersamaan membuat order tersebut.
- Panggilan jaringan dilakukan di luar lock database. Setelah respons, payment dan donasi dikunci dan dibaca ulang dengan urutan sama seperti webhook. Pembayaran yang sudah berhasil menghasilkan konflik 409, tanpa ditimpa.
- Callback `cancel` dipetakan ke `Dibatalkan`. Callback duplikat tidak menggandakan riwayat/notifikasi; callback pending terlambat tidak membuka lagi pembatalan. Bukti sukses terverifikasi tetap dapat merekonsiliasi status sebelumnya menjadi Berhasil.
- Kegagalan request checkout tidak lagi menimpa status provider yang sudah berubah melalui callback.
- Filter transaksi dan laporan web sekarang menyertakan Dibatalkan.

Validasi respons mengikuti [dokumentasi pembatalan Midtrans](https://docs.midtrans.com/reference/cancel-transaction). Integrasi langsung provider tidak dijalankan; pengujian menggunakan HTTP fake dan callback bertanda tangan dengan key pengujian.

## Perhitungan laporan

- Satu aturan bersih dipakai dashboard, grafik, laporan donasi, dan arus kas: manual = nominal penuh; online dengan biaya diketahui = nominal dikurangi biaya; online tanpa biaya = belum dihitung dalam bersih.
- Data manual lama dengan biaya null dihitung benar saat dibaca, tanpa perubahan massal database. Donasi manual baru mencatat biaya gateway nol secara eksplisit.
- Total bruto tetap memasukkan seluruh donasi berhasil. Total bersih tidak memasukkan transaksi gagal, batal, atau menunggu.
- Saldo awal periode memakai aturan sama untuk penerimaan sebelum periode. Jumlah transaksi biaya belum diketahui mencakup rentang sejak saldo pembukaan hingga akhir periode.
- Keterangan saldo sementara ditambahkan ke arus kas, laporan web/PDF, ringkasan Excel, API transparansi, beranda mobile, dan halaman transparansi mobile.
- Endpoint API transparansi menambah `is_provisional`, `unknown_fee_count`, dan `calculation_note`. Field nominal yang sudah ada tetap berupa string desimal; mobile dapat membaca respons backend lama dengan default untuk field baru.

Contoh regresi: manual lama Rp100.000 + manual baru Rp50.000 + online Rp100.000 dengan biaya Rp2.000 + online Rp100.000 tanpa biaya menghasilkan bruto Rp350.000 dan bersih Rp248.000 di seluruh modul. Setelah biaya transaksi terakhir dilengkapi Rp1.000, bersih menjadi Rp347.000. Donasi dibatalkan tidak ikut total.

## Validasi

Hasil lokal: seluruh 61 tes backend lulus (575 assertion), 17 tes Flutter lulus, analisis Flutter tanpa masalah. Setelah penyesuaian representasi biaya manual pada API, 23 tes API/pembayaran terkait dijalankan ulang dan lulus (198 assertion). Pemeriksaan whitespace diff juga bersih.

- Tes backend mencakup kegagalan provider, timeout, penolakan bisnis HTTP 200, order tidak cocok, key tidak tersedia, pembatalan lokal, callback sukses/batal yang masuk di tengah request, callback berulang, dan konsistensi jumlah lintas modul.
- Tes Flutter mencakup parsing flag saldo sementara serta layout beranda dengan catatan saldo pada lebar 320/390 px dan skala teks 1,0/1,5.
- Jalankan `php artisan test` dari web dan `flutter analyze --no-pub`, `flutter test --no-pub --concurrency=1` dari proyek mobile.

Tidak diperlukan migrasi database. APK mobile perlu dibangun ulang agar UI catatan saldo tampil pada perangkat. Riwayat pembayaran lama yang sudah salah dibatalkan tidak diubah massal: penentuan status sebenarnya memerlukan rekonsiliasi provider per order. Pencegahan donasi ganda/idempotensi, pagination mobile, serta masalah lain di audit tetap di luar perbaikan ini.
