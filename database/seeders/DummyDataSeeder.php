<?php

namespace Database\Seeders;

use App\Models\Bantuan;
use App\Models\Berita;
use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\KategoriBantuan;
use App\Models\KategoriDonasi;
use App\Models\Kebutuhan;
use App\Models\Kegiatan;
use App\Models\Record;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DummyDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Data dummy hanya boleh diisi di lingkungan local atau testing.');
        }

        DB::transaction(function () {
            $donationCategory = KategoriDonasi::firstOrCreate(['nama' => 'DUMMY - Donasi simulasi']);
            $goodsCategory = KategoriBantuan::firstOrCreate(['nama' => 'DUMMY - Barang simulasi']);

            $donors = collect(range(1, 12))->map(fn ($i) => Donatur::firstOrCreate(
                // Intentionally non-routable; these are not real WhatsApp contacts.
                ['whatsapp' => sprintf('DUMMY-%03d', $i)],
                ['nama' => sprintf('DUMMY - Donatur %02d', $i), 'email' => "donatur{$i}@example.invalid"],
            ));

            // Four donations per month, covering the six dashboard chart months.
            foreach (range(0, 23) as $i) {
                $date = now()->startOfMonth()->subMonths(intdiv($i, 4))->addHours(9 + $i % 4);
                $status = ['Berhasil', 'Berhasil', 'Menunggu pembayaran', 'Kedaluwarsa'][$i % 4];
                $donor = $donors[$i % 12];
                $record = Donasi::firstOrCreate(['kode' => sprintf('DUMMY-DON-%03d', $i + 1)], [
                    'token_hash' => hash('sha256', Str::random(64)),
                    'donatur_id' => $donor->id,
                    'kategori_donasi_id' => $donationCategory->id,
                    'nama' => $donor->nama,
                    'whatsapp' => $donor->whatsapp,
                    'email' => $donor->email,
                    'nominal' => 50000 + ($i % 7) * 75000,
                    'biaya' => $status === 'Berhasil' ? 1000 : null,
                    'sumber_biaya' => $status === 'Berhasil' ? 'DUMMY - Simulasi lokal' : null,
                    'status' => $status,
                    'dibayar_pada' => $status === 'Berhasil' ? $date->copy()->addHour() : null,
                    'created_at' => $date,
                    'updated_at' => $date->copy()->addHour(),
                ]);
                $this->history($record);
            }

            foreach (['Diajukan', 'Dikonfirmasi', 'Diterima', 'Ditolak', 'Diterima', 'Diajukan'] as $i => $status) {
                $record = Bantuan::firstOrCreate(['kode' => sprintf('DUMMY-BAN-%03d', $i + 1)], [
                    'token_hash' => hash('sha256', Str::random(64)),
                    'donatur_id' => $donors[$i]->id,
                    'nama' => $donors[$i]->nama,
                    'whatsapp' => $donors[$i]->whatsapp,
                    'catatan' => 'DUMMY - Simulasi bantuan barang untuk demonstrasi.',
                    'status' => $status,
                    'tanggal_diterima' => $status === 'Diterima' ? today()->subDays(7) : null,
                    'created_at' => today()->subDays(14),
                ]);
                if ($record->wasRecentlyCreated) {
                    foreach ([['Beras', 'kg', 25], ['Buku tulis', 'buah', 40]] as [$name, $unit, $amount]) {
                        $record->items()->create([
                            'kategori_bantuan_id' => $goodsCategory->id,
                            'nama' => 'DUMMY - '.$name,
                            'jumlah' => $amount + $i * 5,
                            'satuan' => $unit,
                        ]);
                    }
                }
                $this->history($record);
            }

            foreach ([['Beras', 'kg', 100, 25], ['Buku tulis', 'buah', 200, 100], ['Minyak goreng', 'liter', 40, 0], ['Perlengkapan mandi', 'paket', 30, 30], ['Seragam sekolah', 'stel', 20, 5], ['Alat kebersihan', 'paket', 10, 0]] as [$name, $unit, $target, $fulfilled]) {
                Kebutuhan::firstOrCreate(['nama' => 'DUMMY - '.$name], [
                    'kategori_bantuan_id' => $goodsCategory->id,
                    'deskripsi' => 'Contoh kebutuhan untuk demonstrasi, bukan kebutuhan aktual panti.',
                    'target' => $target,
                    'terpenuhi' => $fulfilled,
                    'satuan' => $unit,
                    'status' => $fulfilled >= $target ? 'Terpenuhi' : ($fulfilled > 0 ? 'Sebagian terpenuhi' : 'Dibutuhkan'),
                ]);
            }

            foreach ([Kegiatan::class, Berita::class] as $model) {
                foreach (['Belajar bersama', 'Kerja bakti lingkungan', 'Penyerahan perlengkapan sekolah'] as $i => $title) {
                    $model::firstOrCreate(['judul' => 'DUMMY - '.$title], [
                        'tanggal' => today()->subDays(7 * ($i + 1)),
                        'lokasi' => 'Lokasi simulasi',
                        'deskripsi' => 'DUMMY: Konten contoh untuk menguji tampilan sistem. Kegiatan ini bukan dokumentasi kegiatan nyata panti.',
                        'status' => $i === 2 ? 'Draft' : 'Terbit',
                    ]);
                }
            }
        });
    }

    private function history(Record $record): void
    {
        if ($record->wasRecentlyCreated) {
            $record->histories()->create([
                'ke' => $record->status,
                'sumber' => 'dummy-seeder',
                'catatan' => 'Status awal simulasi lokal; bukan transaksi atau persetujuan nyata.',
                'created_at' => $record->updated_at,
            ]);
        }
    }
}
