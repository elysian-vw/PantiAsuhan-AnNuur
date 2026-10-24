# Verifikasi APK — 24 Oktober 2026

Menuntaskan pemasangan perbaikan pesan login dan label tombol pembayaran dari [uji perangkat sebelumnya](uji-perangkat-sandbox-2026-10-23.md).

- Build APK debug berhasil dalam 268,4 detik. Proses memakai override memori Java khusus sesi: heap 512 MB, metaspace 384 MB, code cache 64 MB, satu prosesor, Serial GC. Tidak mengubah konfigurasi build permanen atau menutup proses IDE pengguna. Daemon build milik sesi dihentikan setelah selesai untuk membebaskan memori.
- APK terbaru dipasang dengan `adb install -r` pada Infinix X6833B; hasil Success. Data aplikasi tidak dihapus.
- Uji login dengan USB forwarding diputus menampilkan pesan ramah pengguna: **Tidak dapat terhubung ke server. Periksa koneksi dan coba lagi.** Detail exception/alamat internal tidak muncul. Menggunakan input uji, tanpa membuat akun atau transaksi.
- USB forwarding port 8000 dipulihkan. Aplikasi dibuka ulang pada layar login dengan input uji dibersihkan dari memori tampilan.
- Seluruh **28 tes Flutter lulus** pada source terkini. Perubahan transparansi yang sudah ada di workspace dipertahankan dan ikut dalam build ini.
- `flutter analyze --no-pub` selesai tanpa masalah.
- Label dialog pembayaran pada kode yang dibangun adalah **Selesai**. Tidak mengulang pembayaran sandbox pada sesi ini; alur settlement dan dialog sukses sudah diuji di sesi 23 Oktober.

APK: `D:\apps\laragon\www\annuur_mobile\build\app\outputs\flutter-apk\app-debug.apk`.

Bukti lokal: `.runtime/apk-followup-20261024.log`, `.runtime/flutter-20261024.log`, dan `.runtime/screenshots/device-friendly-error-20261024.png`.

Konfigurasi email nyata, identitas merchant Midtrans, pembatasan kanal pembayaran, serta keystore release tetap mengikuti catatan pengujian sebelumnya. APK ini adalah build debug.
