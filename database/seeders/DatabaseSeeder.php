<?php

namespace Database\Seeders;

use App\Models\JenisKunjungan;
use App\Models\KategoriBantuan;
use App\Models\KategoriDonasi;
use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (['Donasi Umum', 'Pendidikan', 'Kebutuhan Harian'] as $name) {
            KategoriDonasi::firstOrCreate(['nama' => $name]);
        }
        foreach (['Bahan Pangan', 'Perlengkapan Pendidikan', 'Pakaian', 'Kebutuhan Lainnya'] as $name) {
            KategoriBantuan::firstOrCreate(['nama' => $name]);
        }
        foreach (['Silaturahmi', 'Penyerahan Bantuan', 'Kegiatan Bersama'] as $name) {
            JenisKunjungan::firstOrCreate(['nama' => $name]);
        }
        Setting::firstOrCreate(['key' => 'nama_panti'], ['value' => 'Panti Asuhan NU An-Nuur 2']);
        \App\Models\User::firstOrCreate(
            ['email' => 'pengurus@annuur2.id'],
            ['name' => 'Pengurus An-Nuur 2', 'password' => 'admin123']
        );
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@annuur2.id'],
            ['name' => 'Administrator', 'password' => 'admin123']
        );
    }
}
