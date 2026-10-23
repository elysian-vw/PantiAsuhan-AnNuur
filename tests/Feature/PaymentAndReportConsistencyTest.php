<?php

namespace Tests\Feature;

use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\KategoriDonasi;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Services\CashflowService;
use App\Services\ManualDonationService;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentAndReportConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['panti.midtrans_key' => 'test-key', 'panti.whatsapp_enabled' => false, 'panti.simulated_fee' => 1000]);
        Http::preventStrayRequests();
    }

    private function donation(array $attributes = [], string $paymentStatus = 'requesting'): Donasi
    {
        $user = User::factory()->create(['role' => 'donatur']);
        $donor = Donatur::create(['nama' => $user->name, 'email' => $user->email, 'whatsapp' => '62812'.random_int(10000000, 99999999), 'jenis' => 'tetap', 'user_id' => $user->id]);
        $category = KategoriDonasi::firstOrCreate(['nama' => 'Umum']);
        $donation = Donasi::create(array_merge([
            'kode' => 'DON-'.Str::ulid(), 'token_hash' => hash('sha256', Str::random(64)),
            'donatur_id' => $donor->id, 'kategori_donasi_id' => $category->id,
            'nama' => $user->name, 'whatsapp' => $donor->whatsapp, 'nominal' => 100000,
            'status' => 'Menunggu pembayaran', 'sumber' => 'online',
            'owner_user_id' => $user->id, 'ownership_verified_at' => now(),
        ], $attributes));
        if ($donation->sumber === 'online') {
            $donation->payment()->create(['order_id' => $donation->kode, 'status' => $paymentStatus]);
        }
        $this->actingAs($user, 'sanctum');

        return $donation;
    }

    private function notification(Donasi $donation, string $status): array
    {
        $data = ['order_id' => $donation->kode, 'status_code' => '200', 'gross_amount' => '100000.00', 'transaction_status' => $status];
        $data['signature_key'] = hash('sha512', $data['order_id'].$data['status_code'].$data['gross_amount'].'test-key');

        return $data;
    }

    public function test_provider_failure_timeout_and_business_rejection_do_not_cancel_or_notify(): void
    {
        foreach (['http_error', 'timeout', 'business_error', 'wrong_order', 'no_key'] as $case) {
            config(['panti.midtrans_key' => $case === 'no_key' ? null : 'test-key']);
            $donation = $this->donation();
            Http::fake(function ($request) use ($case) {
                if (str_ends_with($request->url(), '/status')) {
                    return Http::response([], 503);
                }
                if ($case === 'timeout') {
                    throw new ConnectionException('test timeout');
                }

                return match ($case) {
                    'business_error' => Http::response(['status_code' => '412'], 200),
                    'wrong_order' => Http::response(['status_code' => '200', 'transaction_status' => 'cancel', 'order_id' => 'another-order'], 200),
                    default => Http::response([], 503),
                };
            });
            $this->postJson('/api/v1/donations/'.$donation->id.'/cancel')->assertStatus(503);
            $this->assertSame('Menunggu pembayaran', $donation->fresh()->status, $case);
            $this->assertSame('requesting', $donation->payment->fresh()->status, $case);
            $this->assertSame(0, $donation->histories()->count(), $case);
        }
        $this->assertSame(0, WhatsappLog::count());
    }

    public function test_never_submitted_order_can_cancel_locally_and_cannot_checkout_afterwards(): void
    {
        $donation = $this->donation([], 'created');
        $donation->load('payment'); // A checkout holding a stale snapshot must also lose the claim.
        Http::fake();
        $this->postJson('/api/v1/donations/'.$donation->id.'/cancel')->assertOk()->assertJsonPath('data.status', 'Dibatalkan');
        $this->assertNull(app(MidtransService::class)->checkout($donation, 'https://example.test/return'));
        Http::assertNothingSent();
    }

    public function test_success_notification_during_cancellation_is_never_overwritten(): void
    {
        $donation = $this->donation();
        Http::fake(function ($request) use ($donation) {
            if (str_ends_with($request->url(), '/status')) {
                return Http::response([], 503);
            }
            app(MidtransService::class)->notification($this->notification($donation, 'settlement'));

            return Http::response(['status_code' => '200', 'transaction_status' => 'cancel', 'order_id' => $donation->kode]);
        });
        $this->postJson('/api/v1/donations/'.$donation->id.'/cancel')->assertStatus(409);
        $this->assertSame('Berhasil', $donation->fresh()->status);
        $this->assertSame('settlement', $donation->payment->fresh()->status);
        $this->assertSame(1, $donation->histories()->count());
    }

    public function test_cancel_callback_and_api_response_are_consistent_and_idempotent(): void
    {
        $donation = $this->donation();
        Http::fake(function ($request) use ($donation) {
            if (str_ends_with($request->url(), '/status')) {
                return Http::response([], 503);
            }
            app(MidtransService::class)->notification($this->notification($donation, 'cancel'));

            return Http::response(['status_code' => '200', 'transaction_status' => 'cancel', 'order_id' => $donation->kode]);
        });
        $this->postJson('/api/v1/donations/'.$donation->id.'/cancel')->assertOk()->assertJsonPath('data.status', 'Dibatalkan');
        $service = app(MidtransService::class);
        $service->notification($this->notification($donation, 'cancel'));
        $service->notification($this->notification($donation, 'pending'));
        $this->assertSame('Dibatalkan', $donation->fresh()->status);
        $this->assertSame('cancel', $donation->payment->fresh()->status);
        $this->assertSame(1, $donation->histories()->count());
        $this->assertSame(1, WhatsappLog::count());
        $service->notification($this->notification($donation, 'settlement'));
        $service->notification($this->notification($donation, 'cancel'));
        $this->assertSame('Berhasil', $donation->fresh()->status);
        $this->assertSame('settlement', $donation->payment->fresh()->status);
    }

    public function test_timeout_can_reconcile_a_provider_confirmed_cancel(): void
    {
        $donation = $this->donation();
        $checks = 0;
        Http::fake(function ($request) use ($donation, &$checks) {
            if (str_ends_with($request->url(), '/status')) {
                return ++$checks === 1 ? Http::response([], 503) : Http::response($this->notification($donation, 'cancel'));
            }
            throw new ConnectionException('cancel response lost');
        });
        $this->postJson('/api/v1/donations/'.$donation->id.'/cancel')->assertOk()->assertJsonPath('data.status', 'Dibatalkan');
        $this->assertSame(1, $donation->histories()->count());
    }

    public function test_checkout_failure_does_not_overwrite_a_notification_received_during_the_request(): void
    {
        $donation = $this->donation([], 'created');
        Http::fake(function () use ($donation) {
            app(MidtransService::class)->notification($this->notification($donation, 'settlement'));
            throw new ConnectionException('response lost after payment notification');
        });
        $this->assertNull(app(MidtransService::class)->checkout($donation, 'https://example.test/return'));
        $this->assertSame('Berhasil', $donation->fresh()->status);
        $this->assertSame('settlement', $donation->payment->fresh()->status);
    }

    public function test_manual_legacy_and_online_totals_match_dashboard_reports_cashflow_and_api(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 22));
        $legacy = $this->donation(['sumber' => 'manual', 'metode' => 'tunai', 'status' => 'Berhasil', 'dibayar_pada' => now()]);
        $this->donation(['biaya' => 2000, 'status' => 'Berhasil', 'dibayar_pada' => now()]);
        $unknown = $this->donation(['status' => 'Berhasil', 'dibayar_pada' => now()]);
        $this->donation(['biaya' => 0, 'status' => 'Dibatalkan']);
        $admin = User::factory()->create(['role' => 'pengurus']);
        $this->actingAs($admin, 'web');
        $manual = app(ManualDonationService::class)->record([
            'nama' => 'Tunai', 'whatsapp' => '6281234567899', 'nominal' => 50000,
            'kategori_donasi_id' => $legacy->kategori_donasi_id, 'metode' => 'tunai',
        ], null, $admin);
        $this->assertSame('0.00', $manual->fresh()->recordedFee());
        $this->assertNull($legacy->fresh()->biaya); // No destructive backfill needed.
        $this->get('/admin')->assertOk()->assertViewHas('stats', fn ($stats) => $stats['Donasi bersih tercatat'] === 'Rp248.000')
            ->assertViewHas('unknownFees', 1)->assertViewHas('chart', fn ($chart) => $chart->last()['value'] === 248000.0);
        $this->get('/admin/laporan/donasi?status=Berhasil')->assertOk()->assertViewHas('net', 248000)
            ->assertViewHas('rows', fn ($rows) => collect($rows)->firstWhere(0, $legacy->kode)[6] === '100000.00');
        $cashflow = app(CashflowService::class)->calculate('2026-10-01', '2026-10-31');
        $this->assertSame('248000.00', $cashflow['penerimaan']['total']);
        $this->assertSame('350000.00', $cashflow['penerimaan']['bruto_total']);
        $this->assertTrue($cashflow['is_provisional']);
        $this->getJson('/api/v1/transparency/summary?month=10&year=2026')->assertOk()
            ->assertJsonPath('total_penerimaan', '248000.00')->assertJsonPath('saldo_akhir', '248000.00')
            ->assertJsonPath('is_provisional', true)->assertJsonPath('unknown_fee_count', 1);
        $this->get('/admin/laporan/arus-kas?bulan=10&tahun=2026')->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary['Total Penerimaan Periode (Rp)'] === 248000.0);
        $nextMonth = app(CashflowService::class)->calculate('2026-11-01', '2026-11-30');
        $this->assertSame('248000.00', $nextMonth['saldo_awal']);
        $this->assertSame(1, $nextMonth['unknown_fee_count']);
        $unknown->update(['biaya' => 1000]);
        $reconciled = app(CashflowService::class)->calculate('2026-11-01', '2026-11-30');
        $this->assertSame('347000.00', $reconciled['saldo_awal']);
        $this->assertFalse($reconciled['is_provisional']);
    }
}
