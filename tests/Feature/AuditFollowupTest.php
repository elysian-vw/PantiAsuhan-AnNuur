<?php

namespace Tests\Feature;

use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\KategoriDonasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditFollowupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['panti.midtrans_key' => null]);
        Http::preventStrayRequests();
    }

    private function donor(): User
    {
        $user = User::factory()->create(['role' => 'donatur']);
        Donatur::create(['nama' => $user->name, 'whatsapp' => '62812'.random_int(10000000, 99999999), 'email' => $user->email, 'jenis' => 'tetap', 'user_id' => $user->id]);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    private function payload(): array
    {
        return ['kategori_donasi_id' => KategoriDonasi::firstOrCreate(['nama' => 'Umum'])->id, 'nominal' => 5000, 'idempotency_key' => 'request-123'];
    }

    public function test_request_key_survives_timeout_window_and_terminal_status_without_new_order(): void
    {
        $this->donor();
        $data = $this->payload();
        $id = $this->postJson('/api/v1/donations', $data)->assertCreated()->json('data.id');
        $this->travel(45)->seconds();
        Donasi::findOrFail($id)->update(['status' => 'Berhasil']);
        $this->postJson('/api/v1/donations', $data)->assertOk()->assertJsonPath('data.id', $id)->assertJsonPath('data.status', 'Berhasil');
        $this->postJson('/api/v1/donations', array_replace($data, ['nominal' => 10000]))->assertStatus(409);
        $this->postJson('/api/v1/donations', array_replace($data, ['idempotency_key' => 'another-request']))->assertCreated();
        $this->assertDatabaseCount('donasi', 2);
        $this->assertDatabaseCount('payment_transactions', 2);
        $this->donor();
        $this->postJson('/api/v1/donations', $data)->assertCreated();
        $this->assertDatabaseCount('donasi', 3);
        Http::assertNothingSent();
    }

    public function test_integer_nominal_and_pagination_status_filter(): void
    {
        $this->donor();
        $data = $this->payload();
        foreach ([4999, 5000.5, 1000000000] as $invalid) {
            $this->postJson('/api/v1/donations', array_replace($data, ['nominal' => $invalid]))->assertUnprocessable()->assertJsonValidationErrors('nominal');
        }
        for ($i = 0; $i < 17; $i++) {
            $id = $this->postJson('/api/v1/donations', array_replace($data, ['idempotency_key' => 'request-'.$i]))->assertCreated()->json('data.id');
            if ($i === 0) {
                Donasi::findOrFail($id)->update(['status' => 'Berhasil']);
            }
        }
        $this->getJson('/api/v1/donations?page=2')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/donations?status=Berhasil')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.status', 'Berhasil');
    }

    public function test_zero_string_is_a_valid_idempotency_key(): void
    {
        $this->donor();
        $data = array_replace($this->payload(), ['idempotency_key' => '0']);
        $id = $this->postJson('/api/v1/donations', $data)->assertCreated()->json('data.id');
        $this->travel(60)->seconds();
        $this->postJson('/api/v1/donations', $data)->assertOk()->assertJsonPath('data.id', $id);
    }

    public function test_checkout_retry_uses_a_real_non_secret_return_page(): void
    {
        $this->donor();
        $id = $this->postJson('/api/v1/donations', $this->payload())->assertCreated()->json('data.id');
        config(['panti.midtrans_key' => 'test-key']);
        Http::fake(['*' => Http::response(['redirect_url' => 'https://app.sandbox.midtrans.com/snap/test', 'token' => 'token-test'])]);
        $this->postJson('/api/v1/donations/'.$id.'/pay')->assertOk();
        Http::assertSent(fn ($r) => $r['callbacks']['finish'] === route('mobile.payment.return'));
        $this->get(route('mobile.payment.return'))->assertOk()->assertSee('bukan bukti pembayaran berhasil');
    }

    public function test_register_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/register', [])->assertUnprocessable();
        }
        $this->postJson('/api/v1/auth/register', [])->assertStatus(429);
    }

    public function test_manual_proof_stays_private_and_is_available_to_admin_only(): void
    {
        Storage::fake('local');
        $user = $this->donor();
        $id = $this->postJson('/api/v1/donations', $this->payload())->assertCreated()->json('data.id');
        $donation = Donasi::findOrFail($id);
        $donation->update(['sumber' => 'manual', 'bukti_transfer' => 'manual-donations/proof.pdf']);
        Storage::disk('local')->put('manual-donations/proof.pdf', 'private receipt');
        $url = route('admin.donation.manual.proof', $donation);
        $this->actingAs($user, 'web')->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'pengurus']), 'web')->get($url)->assertDownload('bukti-'.$donation->kode.'.pdf');
        Storage::disk('local')->delete($donation->bukti_transfer);
        $this->get($url)->assertNotFound();
    }
}
