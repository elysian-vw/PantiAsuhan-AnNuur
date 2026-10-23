<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_NOTE = 'Lengkapi alur persetujuan kunjungan/bantuan bersama pengurus: petugas pemeriksa dan pemberi persetujuan, syarat diterima/ditolak, penjadwalan ulang, bukti penerimaan bantuan, dan notifikasi. Konfirmasi juga ejaan nama hasil transkripsi foto serta lengkapi visi, misi, dan sejarah yang belum terbaca lengkap.';

    private const NEW_NOTE = 'Lengkapi alur penerimaan bantuan bersama pengurus: petugas pemeriksa dan pemberi persetujuan, syarat diterima/ditolak, bukti penerimaan bantuan, dan notifikasi. Konfirmasi juga ejaan nama hasil transkripsi foto serta lengkapi visi, misi, dan sejarah yang belum terbaca lengkap.';

    public function up(): void
    {
        // Keep original records and IDs, including the private tracking hashes.
        // Guards allow retrying after an interrupted MySQL DDL operation.
        if (! Schema::hasTable('arsip_bantuan_kunjungan')) {
            Schema::create('arsip_bantuan_kunjungan', function (Blueprint $table) {
                $table->unsignedBigInteger('bantuan_id')->primary();
                $table->unsignedBigInteger('kunjungan_id');
            });
        }
        if (Schema::hasColumn('bantuan', 'kunjungan_id')) {
            DB::table('bantuan')->whereNotNull('kunjungan_id')->orderBy('id')->chunkById(500, function ($rows) {
                DB::table('arsip_bantuan_kunjungan')->insertOrIgnore($rows->map(fn ($row) => [
                    'bantuan_id' => $row->id,
                    'kunjungan_id' => $row->kunjungan_id,
                ])->all());
            });
            Schema::table('bantuan', function (Blueprint $table) {
                $table->dropConstrainedForeignId('kunjungan_id');
            });
        }
        if (Schema::hasTable('kunjungan')) {
            Schema::rename('kunjungan', 'arsip_kunjungan');
        }
        if (Schema::hasTable('jenis_kunjungan')) {
            Schema::rename('jenis_kunjungan', 'arsip_jenis_kunjungan');
        }

        // Old queue jobs only store log IDs; leave their logs available but cancel delivery.
        DB::table('whatsapp_logs')->where('jenis', 'like', 'kunjungan:%')
            ->where('status', 'Antrean')
            ->update([
                'status' => 'Dibatalkan',
                'keterangan' => 'Modul kunjungan telah dihapus.',
                'updated_at' => now(),
            ]);
        // Only replace the original seeded note; preserve any administrator edits.
        DB::table('settings')->where('key', 'tindak_lanjut')->where('value', self::OLD_NOTE)
            ->update(['value' => self::NEW_NOTE, 'updated_at' => now()]);
    }

    public function down(): void
    {
        if (Schema::hasTable('arsip_jenis_kunjungan')) {
            Schema::rename('arsip_jenis_kunjungan', 'jenis_kunjungan');
        }
        if (Schema::hasTable('arsip_kunjungan')) {
            Schema::rename('arsip_kunjungan', 'kunjungan');
        }
        if (! Schema::hasColumn('bantuan', 'kunjungan_id')) {
            Schema::table('bantuan', function (Blueprint $table) {
                $table->foreignId('kunjungan_id')->nullable()->constrained('kunjungan')->restrictOnDelete();
            });
        }
        if (Schema::hasTable('arsip_bantuan_kunjungan')) {
            DB::table('arsip_bantuan_kunjungan')->orderBy('bantuan_id')->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('bantuan')->where('id', $row->bantuan_id)->update([
                        'kunjungan_id' => $row->kunjungan_id,
                    ]);
                }
            });
            Schema::drop('arsip_bantuan_kunjungan');
        }
        // Cancelled notifications are deliberately not queued again on rollback.
    }
};
