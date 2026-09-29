<?php

namespace Database\Seeders;

use App\Models\KategoriBantuan;
use App\Models\Pengurus;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhotoReferenceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $settings = [
                'tentang' => 'Panti Asuhan NU An-Nuur 2 merupakan Lembaga Kesejahteraan Sosial Anak (LKSA) di Kelurahan Sukorame, Kecamatan Mojoroto, Kota Kediri. Pelayanan mencakup penerima manfaat dalam panti dan nonpanti.',
                'alamat' => 'Jl. Anggraini Gg. IV No. 07, Kelurahan Sukorame, Kecamatan Mojoroto, Kota Kediri',
                'telepon' => '0856 354 4294 / 0813 3531 1626',
                'periode_pengurus' => '2025–2027',
                'pendiri' => "KH. M. Anwar Iskandar\nKH. Abd. Wahid Faqih (Alm.)\nDrs. H. Suud K., SH., MM (Alm.)\nKH. Halim Turmudzi (Alm.)\nKH. A. Mahin Toha\nH. Zaenal Arifin (Alm.)\nH. Miftahul Arifin, S.Ag (Alm.)",
                'penerima_manfaat' => '100',
                'periode_statistik' => '2026/2027 · sesuai papan rekap bertanggal 1 Juli 2026',
                'rekap_penerima' => "DALAM PANTI: 10 orang (9 laki-laki, 1 perempuan).\nPendidikan: SD 1 perempuan; SLTP 6 laki-laki; SLTA 3 laki-laki; kuliah ditandai — pada papan.\nStatus: yatim piatu 1 laki-laki; yatim 4 laki-laki; dhuafa 4 laki-laki dan 1 perempuan; piatu ditandai — pada papan.\n\nNONPANTI: 90 orang (47 laki-laki, 43 perempuan).\nBandar Lor: 20 (12 laki-laki, 8 perempuan). Koordinator: Ust. Nasrul.\nSukorame: 30 (15 laki-laki, 15 perempuan). Koordinator: Pak Samsul.\nPojok: 10 (5 laki-laki, 5 perempuan). Koordinator: Pak Arif.\nBujel: 5 (2 laki-laki, 3 perempuan). Koordinator: Pak Fauzi.\nWaung: 5 (3 laki-laki, 2 perempuan). Koordinator: Pak Anshori.\nCampurejo T.: 5 (3 laki-laki, 2 perempuan). Koordinator: Bu Nyai Shokib.\nCampurejo B.: 15 (7 laki-laki, 8 perempuan). Koordinator: Pak Ajik.\nKolom lansia ditandai — pada papan; tidak ditafsirkan sebagai jumlah terverifikasi.\n\nTotal dalam panti dan nonpanti: 100 orang. Angka ini merupakan rekap periode tersebut, bukan hitungan waktu nyata.",
                'tata_tertib' => "Ringkasan kewajiban anak panti dari papan tata tertib:\n1. Melaksanakan ibadah dan mengikuti salat berjamaah lima waktu.\n2. Mengikuti pendidikan sekolah umum dan madrasah diniyah.\n3. Mematuhi kebijakan pengurus serta menjaga nama baik panti.\n4. Menghormati pengurus, pengasuh, dan tamu serta menyayangi sesama warga panti.\n5. Menjaga keamanan, ketertiban, keindahan, kebersihan, kekeluargaan, dan kepedulian terhadap sesama.\n6. Berpamitan ketika berangkat sekolah; meminta izin dan mengisi buku absen ketika keluar atau meninggalkan panti.\n7. Berpakaian rapi dan berbusana muslim.\n8. Mengikuti program panti dengan tertib dan disiplin serta bertutur kata santun.\n9. Mematuhi jadwal istirahat; tidur di luar panti harus mendapat izin pengasuh.\n10. Melaksanakan tugas piket dengan disiplin.\nRingkasan ini bukan transkripsi lengkap dan berlaku bagi santri, bukan ketentuan pengajuan kunjungan.",
                'sumber_profil' => 'Disalin dari dokumentasi foto papan pengurus, pendiri, tata tertib, dan rekap penerima manfaat yang diberikan pada September 2026. Ejaan nama dan data dapat diperbarui oleh pengurus.',
                'tindak_lanjut' => 'Lengkapi alur persetujuan kunjungan/bantuan bersama pengurus: petugas pemeriksa dan pemberi persetujuan, syarat diterima/ditolak, penjadwalan ulang, bukti penerimaan bantuan, dan notifikasi. Konfirmasi juga ejaan nama hasil transkripsi foto serta lengkapi visi, misi, dan sejarah yang belum terbaca lengkap.',
            ];
            foreach ($settings as $key => $value) {
                // Preserve existing editorial content and make repeat imports safe.
                $setting = Setting::firstOrCreate(['key' => $key], ['value' => $value]);
                if (blank($setting->value)) {
                    $setting->update(['value' => $value]);
                }
            }
            $members = [
                ['H. M. Jauhari, S.Pd.I', 'Ketua'],
                ['H. Chairil A., ST, M.Eng', 'Wakil Ketua'],
                ['Ajik Muchtadi A., S.Pd.SD', 'Sekretaris'],
                ['H. M. Anshori M., S.Pd.I', 'Wakil Sekretaris'],
                ['Fauzi Fitriyantoro, M.Pd.I', 'Bendahara'],
                ['Andi Kelana P.S.H', 'Wakil Bendahara'],
                ['H. To’adi, S.Pd', 'Bidang Pendidikan'],
                ['H. Machrus Bajuri, S.Pd', 'Bidang Pendidikan'],
                ['H. Imam Subowo, S.KM', 'Bidang Kesehatan'],
                ['Dr. Fundhi K.A.P., SpKFR, MkedKlin', 'Bidang Kesehatan'],
                ['Hari Bagijo, S.Sos', 'Bidang Keterampilan & Wirausaha'],
                ['Totok Agus H., S.Sos', 'Bidang Keterampilan & Wirausaha'],
                ['Hj. Siti Amanah, S.Pd', 'Bidang Pengembangan Bakat & Minat'],
                ['Hj. Sumariatun, S.E', 'Bidang Pengembangan Bakat & Minat'],
                ['Herwidodo Cahyo W.', 'Bidang Perlengkapan & Pemeliharaan'],
                ['H. Samsudin', 'Bidang Perlengkapan & Pemeliharaan'],
                ['Moh. Munip', 'Pengasuh'],
                ['Siti Maslakah', 'Pengasuh'],
            ];
            // Import the board only once, even if names are subsequently corrected.
            if (! Setting::where('key', 'foto_pengurus_diimpor')->exists()) {
                foreach ($members as $i => [$nama, $jabatan]) {
                    Pengurus::firstOrCreate(['nama' => $nama], ['jabatan' => $jabatan, 'urutan' => $i + 1]);
                }
                Setting::create(['key' => 'foto_pengurus_diimpor', 'value' => '2025–2027']);
            }
            foreach (['Sembako', 'Makanan dan Minuman'] as $nama) {
                KategoriBantuan::firstOrCreate(['nama' => $nama]);
            }
        });
    }
}
