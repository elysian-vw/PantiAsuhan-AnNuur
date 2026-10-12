<?php

namespace Tests\Feature;

use App\Models\Donasi;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DummyDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DummyDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_dummy_data_is_repeatable_preserves_edits_and_never_contacts_providers(): void
    {
        Http::fake();
        Queue::fake();
        config(['panti.whatsapp_enabled' => true, 'panti.fonnte_token' => 'test-token']);
        Setting::create(['key' => 'tentang', 'value' => 'Profil asli']);
        $this->seed(DummyDataSeeder::class);
        $donation = Donasi::where('kode', 'DUMMY-DON-001')->firstOrFail();
        $token = $donation->token_hash;
        $donation->update(['nama' => 'DUMMY - Nama disunting']);
        $this->seed(DummyDataSeeder::class);

        foreach (['donatur' => 12, 'donasi' => 24, 'kunjungan' => 8, 'bantuan' => 6, 'bantuan_items' => 12, 'kebutuhan' => 6, 'kegiatan' => 3, 'berita' => 3, 'status_histories' => 38, 'users' => 0, 'whatsapp_logs' => 0, 'payment_transactions' => 0] as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertSame('DUMMY - Nama disunting', $donation->fresh()->nama);
        $this->assertSame($token, $donation->fresh()->token_hash);
        $this->assertDatabaseHas('settings', ['key' => 'tentang', 'value' => 'Profil asli']);
        $this->assertSame(6, Donasi::where('status', 'Berhasil')->get()->groupBy(fn ($row) => $row->dibayar_pada->format('Y-m'))->count());
        Http::assertNothingSent();
        Queue::assertNothingPushed();

        $this->withoutVite();
        $this->get('/')->assertOk()->assertSee('DUMMY');
        $this->actingAs(User::factory()->create())->get('/admin')->assertOk();
    }

    public function test_dummy_data_is_rejected_in_production(): void
    {
        $this->app->instance('env', 'production');
        try {
            app(DummyDataSeeder::class)->run();
            $this->fail('Production seeding should be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('local atau testing', $exception->getMessage());
            $this->assertDatabaseCount('donatur', 0);
            $this->assertDatabaseCount('kategori_donasi', 0);
        }
    }
}
