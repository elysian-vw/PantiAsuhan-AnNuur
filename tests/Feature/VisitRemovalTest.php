<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsapp;
use App\Models\User;
use App\Models\WhatsappLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VisitRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_visit_endpoints_are_unavailable_even_to_admins(): void
    {
        $this->withoutVite();
        $this->get('/kunjungan')->assertNotFound();
        $this->post('/kunjungan', [])->assertNotFound();
        $this->get('/riwayat/kunjungan/'.str_repeat('a', 64))->assertNotFound();
        $this->actingAs(User::factory()->create());
        foreach ([
            '/admin/kalender', '/admin/tamu-langsung',
            '/admin/transaksi/kunjungan', '/admin/transaksi/kunjungan/1',
            '/admin/data/jenis-kunjungan', '/admin/data/jenis-kunjungan/tambah',
            '/admin/laporan/kunjungan', '/admin/laporan/kunjungan?format=pdf',
            '/admin/laporan/kunjungan?format=xlsx',
        ] as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->post('/admin/tamu-langsung', [])->assertNotFound();
        $this->put('/admin/transaksi/kunjungan/1', ['status' => 'Hadir'])->assertNotFound();
        $this->post('/admin/data/jenis-kunjungan', ['nama' => 'Tidak boleh dibuat'])->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee('href="http://localhost/kunjungan"', false);
        $this->get('/admin')->assertOk()->assertDontSee('Buku Tamu')->assertDontSee('Jadwal Kunjungan');
    }

    public function test_archive_preserves_old_records_links_and_histories_and_can_be_restored(): void
    {
        $migration = require database_path('migrations/2026_10_20_000100_archive_visit_module.php');
        $migration->down();
        $categoryId = DB::table('jenis_kunjungan')->insertGetId(['nama' => 'Silaturahmi lama']);
        $visitId = DB::table('kunjungan')->insertGetId([
            'kode' => 'KUN-LAMA', 'token_hash' => hash('sha256', 'old-secret'),
            'jenis_kunjungan_id' => $categoryId, 'nama' => 'Tamu lama', 'whatsapp' => '6281234567890',
            'peserta' => 3, 'tanggal' => '2026-09-01', 'jam' => '09:00:00',
            'jam_selesai' => '10:00:00', 'tujuan' => 'Penyerahan barang', 'status' => 'Selesai',
        ]);
        $donorId = DB::table('donatur')->insertGetId(['nama' => 'Donatur lama', 'whatsapp' => '6281234567890']);
        $assistanceId = DB::table('bantuan')->insertGetId([
            'kode' => 'BAN-LAMA', 'token_hash' => hash('sha256', 'assistance-secret'),
            'donatur_id' => $donorId, 'kunjungan_id' => $visitId,
            'nama' => 'Donatur lama', 'whatsapp' => '6281234567890', 'status' => 'Diterima',
        ]);
        DB::table('status_histories')->insert([
            'subject_type' => 'App\\Models\\Kunjungan', 'subject_id' => $visitId,
            'ke' => 'Selesai', 'sumber' => 'admin', 'catatan' => 'Catatan historis',
        ]);
        $log = $this->visitLog();

        $migration->up();
        $migration->up(); // Retrying must not duplicate or discard the archive.
        $this->assertFalse(Schema::hasTable('kunjungan'));
        $this->assertFalse(Schema::hasTable('jenis_kunjungan'));
        $this->assertFalse(Schema::hasColumn('bantuan', 'kunjungan_id'));
        $this->assertDatabaseHas('arsip_kunjungan', [
            'id' => $visitId, 'kode' => 'KUN-LAMA', 'token_hash' => hash('sha256', 'old-secret'),
        ]);
        $this->assertDatabaseHas('arsip_jenis_kunjungan', ['id' => $categoryId, 'nama' => 'Silaturahmi lama']);
        $this->assertDatabaseHas('arsip_bantuan_kunjungan', ['bantuan_id' => $assistanceId, 'kunjungan_id' => $visitId]);
        $this->assertDatabaseCount('arsip_bantuan_kunjungan', 1);
        $this->assertDatabaseHas('status_histories', ['subject_id' => $visitId, 'catatan' => 'Catatan historis']);
        $this->assertSame('Dibatalkan', $log->fresh()->status);
        $this->assertDatabaseHas('bantuan', ['id' => $assistanceId, 'status' => 'Diterima']);

        $migration->down();
        $this->assertDatabaseHas('kunjungan', ['id' => $visitId, 'jenis_kunjungan_id' => $categoryId]);
        $this->assertDatabaseHas('bantuan', ['id' => $assistanceId, 'kunjungan_id' => $visitId]);
        $this->assertSame('Dibatalkan', $log->fresh()->status);
        $migration->up();
    }

    public function test_previously_queued_visit_notification_is_never_sent(): void
    {
        Http::fake();
        config(['panti.whatsapp_enabled' => true, 'panti.fonnte_token' => 'test']);
        $log = $this->visitLog();
        (new SendWhatsapp($log->id))->handle();
        $this->assertSame('Dibatalkan', $log->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_archive_updates_only_the_original_seeded_follow_up_note(): void
    {
        $migration = require database_path('migrations/2026_10_20_000100_archive_visit_module.php');
        $oldNote = 'Lengkapi alur persetujuan kunjungan/bantuan bersama pengurus: petugas pemeriksa dan pemberi persetujuan, syarat diterima/ditolak, penjadwalan ulang, bukti penerimaan bantuan, dan notifikasi. Konfirmasi juga ejaan nama hasil transkripsi foto serta lengkapi visi, misi, dan sejarah yang belum terbaca lengkap.';
        DB::table('settings')->insert(['key' => 'tindak_lanjut', 'value' => $oldNote]);
        $migration->up();
        $this->assertStringNotContainsString('kunjungan', DB::table('settings')->where('key', 'tindak_lanjut')->value('value'));
        DB::table('settings')->where('key', 'tindak_lanjut')->update(['value' => 'Catatan khusus pengurus']);
        $migration->up();
        $this->assertDatabaseHas('settings', ['key' => 'tindak_lanjut', 'value' => 'Catatan khusus pengurus']);
    }

    private function visitLog(): WhatsappLog
    {
        return WhatsappLog::create([
            'dedup_key' => 'kunjungan:1:1', 'tujuan' => '6281234567890',
            'jenis' => 'kunjungan: Disetujui', 'pesan' => 'Pesan lama', 'status' => 'Antrean',
        ]);
    }
}
