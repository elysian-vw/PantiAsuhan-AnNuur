<?php

namespace Tests\Feature;

use App\Models\Bantuan;
use App\Models\Donasi;
use App\Models\JenisKunjungan;
use App\Models\KategoriBantuan;
use App\Models\KategoriDonasi;
use App\Models\Kegiatan;
use App\Models\Kunjungan;
use App\Models\User;
use App\Services\MidtransService;
use App\Support\AdminResources;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PantiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
        config(['panti.whatsapp_enabled' => false, 'panti.midtrans_key' => null, 'panti.simulated_fee' => null]);
        Http::preventStrayRequests();
    }

    private function donorData(): array
    {
        return ['nama' => 'Donatur Uji', 'whatsapp' => '081234567890', 'consent' => '1'];
    }

    private function visitData(): array
    {
        return $this->donorData() + [
            'jenis_kunjungan_id' => JenisKunjungan::first()->id,
            'peserta' => 5,
            'tanggal' => now()->addDay()->format('Y-m-d'),
            'jam' => '09:00',
            'jam_selesai' => '10:00',
            'tujuan' => 'Silaturahmi',
        ];
    }

    private function donation(): Donasi
    {
        $this->post(
            '/donasi',
            $this->donorData() + ['kategori_donasi_id' => KategoriDonasi::first()->id, 'nominal' => 25000],
        )->assertRedirect();

        return Donasi::latest('id')->firstOrFail();
    }

    private function notification(Donasi $donation, string $status = 'settlement', string $amount = '25000.00'): array
    {
        $data = [
            'order_id' => $donation->kode,
            'status_code' => '200',
            'gross_amount' => $amount,
            'transaction_status' => $status,
            'transaction_id' => 'test-transaction',
            'payment_type' => 'bank_transfer',
        ];
        $data['signature_key'] = hash('sha512', $data['order_id'].'200'.$amount.'test-server-key');

        return $data;
    }

    public function test_public_pages_and_admin_protection(): void
    {
        foreach (
            [
                '/',
                '/profil',
                '/kontak',
                '/kegiatan',
                '/berita',
                '/galeri',
                '/kebutuhan',
                '/donasi',
                '/bantuan',
                '/kunjungan',
                '/admin/login',
            ] as $url
        ) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/data/kategori-donasi', ['nama' => 'Unauthorized'])->assertRedirect('/admin/login');
    }

    public function test_donation_minimum_normalization_and_private_tracking(): void
    {
        $this->post(
            '/donasi',
            $this->donorData() + ['kategori_donasi_id' => KategoriDonasi::first()->id, 'nominal' => 4999],
        )->assertSessionHasErrors('nominal');
        $response = $this->post(
            '/donasi',
            $this->donorData() + ['kategori_donasi_id' => KategoriDonasi::first()->id, 'nominal' => 5000],
        );
        $response->assertSessionHasNoErrors();
        $url = $response->headers->get('Location');
        $this->get($url)->assertOk()->assertSee('5.000')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertDatabaseHas('donatur', ['whatsapp' => '6281234567890']);
        $this->get('/riwayat/donasi/'.str_repeat('x', 64))->assertNotFound();
        $this->donation();
        $this->assertDatabaseCount('donatur', 1);
    }

    public function test_multi_item_assistance_and_receipt_workflow(): void
    {
        $item = [
            'nama' => 'Beras',
            'kategori_bantuan_id' => KategoriBantuan::first()->id,
            'jumlah' => 10,
            'satuan' => 'kg',
        ];
        $this->post(
            '/bantuan',
            $this->donorData() + [
                'items' => [$item, array_replace($item, ['nama' => 'Minyak', 'jumlah' => 5, 'satuan' => 'liter'])],
            ],
        )->assertSessionHasNoErrors();
        $record = Bantuan::first();
        $this->assertCount(2, $record->items);
        $this->actingAs(User::factory()->create());
        $url = '/admin/transaksi/bantuan/'.$record->id;
        $this->put($url, [
            'status' => 'Diterima',
            'tanggal_diterima' => today()->format('Y-m-d'),
        ])->assertSessionHasErrors('status');
        $this->put($url, ['status' => 'Dikonfirmasi'])->assertSessionHasNoErrors();
        $this->put($url, [
            'status' => 'Diterima',
            'tanggal_diterima' => today()->format('Y-m-d'),
        ])->assertSessionHasNoErrors();
        $this->assertSame('Diterima', $record->fresh()->status);
        $this->get($url)->assertOk();
    }

    public function test_visit_hours_reschedule_confirmation_and_attendance(): void
    {
        $this->post('/kunjungan', array_replace($this->visitData(), ['jam' => '06:00']))->assertSessionHasErrors('jam');
        $this->post(
            '/kunjungan',
            array_replace($this->visitData(), ['jam_selesai' => '21:30']),
        )->assertSessionHasErrors('jam_selesai');
        $this->post('/kunjungan', $this->visitData())->assertSessionHasNoErrors();
        $record = Kunjungan::first();
        $this->actingAs(User::factory()->create());
        $url = '/admin/transaksi/kunjungan/'.$record->id;
        $this->put($url, [
            'status' => 'Dijadwalkan ulang',
            'tanggal' => now()->addDays(2)->format('Y-m-d'),
            'jam' => '10:00',
            'jam_selesai' => '11:00',
            'alasan' => 'Penyesuaian kegiatan',
        ])->assertSessionHasNoErrors();
        $this->put($url, ['status' => 'Disetujui'])->assertSessionHasErrors('konfirmasi');
        $this->put($url, ['status' => 'Disetujui', 'konfirmasi' => 1])->assertSessionHasNoErrors();
        $this->assertNotNull($record->fresh()->konfirmasi_ulang_pada);
        $this->put($url, ['status' => 'Hadir'])->assertSessionHasNoErrors();
        $this->put($url, ['status' => 'Selesai'])->assertSessionHasNoErrors();
        $this->get($url)->assertOk();
    }

    public function test_walk_in_is_recorded_as_present(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/tamu-langsung', array_replace($this->visitData(), ['tanggal' => today()->format('Y-m-d')]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('kunjungan', ['status' => 'Hadir', 'sumber' => 'langsung']);
    }

    public function test_webhook_checks_signature_amount_and_is_idempotent(): void
    {
        $donation = $this->donation();
        config(['panti.midtrans_key' => 'test-server-key', 'panti.simulated_fee' => '1000']);
        $payload = $this->notification($donation);
        $this->postJson(
            '/webhooks/midtrans',
            array_replace($payload, ['signature_key' => str_repeat('0', 128)]),
        )->assertForbidden();
        $this->postJson('/webhooks/midtrans', $this->notification($donation, 'settlement', '26000.00'))->assertStatus(
            422,
        );
        $this->postJson('/webhooks/midtrans', $payload)->assertOk();
        $this->postJson('/webhooks/midtrans', $payload)->assertOk();
        $this->postJson('/webhooks/midtrans', $this->notification($donation, 'pending'))->assertOk();
        $this->assertSame('Berhasil', $donation->fresh()->status);
        $this->assertEquals(1000, $donation->fresh()->biaya);
        $this->assertCount(2, $donation->histories);
        $this->assertDatabaseCount('whatsapp_logs', 1);
    }

    public function test_expired_payment_cannot_be_reopened_by_pending_notification(): void
    {
        $donation = $this->donation();
        config(['panti.midtrans_key' => 'test-server-key']);
        $this->postJson('/webhooks/midtrans', $this->notification($donation, 'expire'))->assertOk();
        $this->postJson('/webhooks/midtrans', $this->notification($donation, 'pending'))->assertOk();
        $this->assertSame('Kedaluwarsa', $donation->fresh()->status);
    }

    public function test_checkout_does_not_mark_a_donation_paid(): void
    {
        $donation = $this->donation();
        config(['panti.midtrans_key' => 'test-server-key']);
        Http::fake([
            'app.sandbox.midtrans.com/*' => Http::response(
                ['token' => 'test-token', 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/test'],
                201,
            ),
        ]);
        app(MidtransService::class)->checkout($donation, 'http://localhost/return');
        app(MidtransService::class)->checkout($donation->fresh(), 'http://localhost/return');
        Http::assertSentCount(1);
        $this->assertSame('Menunggu pembayaran', $donation->fresh()->status);
    }

    public function test_drafts_are_private_and_master_data_can_be_managed(): void
    {
        $draft = Kegiatan::create([
            'judul' => 'Draft rahasia',
            'tanggal' => today(),
            'deskripsi' => 'Isi',
            'status' => 'Draft',
        ]);
        $this->get('/kegiatan/'.$draft->id)->assertNotFound();
        $admin = User::factory()->create();
        $this->actingAs($admin);
        $this->post('/admin/data/kegiatan', [
            'judul' => 'Kegiatan terbit',
            'tanggal' => today()->format('Y-m-d'),
            'deskripsi' => 'Dokumentasi kegiatan.',
            'status' => 'Terbit',
        ])->assertSessionHasNoErrors();
        $this->get('/kegiatan')->assertSee('Kegiatan terbit')->assertDontSee('Draft rahasia');
        $this->delete('/admin/data/pengguna/'.$admin->id)->assertSessionHasErrors('hapus');
    }

    public function test_admin_pages_and_exports_work(): void
    {
        $donation = $this->donation();
        $this->actingAs(User::factory()->create());
        foreach (
            [
                '/admin',
                '/admin/pengaturan',
                '/admin/kalender',
                '/admin/tamu-langsung',
                '/admin/transaksi/donasi',
                '/admin/transaksi/bantuan',
                '/admin/transaksi/kunjungan',
                '/admin/transaksi/donasi/'.$donation->id,
            ] as $url
        ) {
            $this->get($url)->assertOk();
        }
        foreach (array_keys(AdminResources::all()) as $key) {
            $this->get('/admin/data/'.$key)->assertOk();
            $this->get('/admin/data/'.$key.'/tambah')->assertOk();
        }
        foreach (['donasi', 'bantuan', 'kunjungan'] as $kind) {
            $this->get('/admin/laporan/'.$kind)->assertOk();
            $this->get('/admin/laporan/'.$kind.'?format=pdf')
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
            $this->get('/admin/laporan/'.$kind.'?format=xlsx')->assertOk();
        }
    }

    public function test_authentication_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'Password-Uji-123']);
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'Password-Uji-123'])->assertRedirect(
            '/admin',
        );
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
