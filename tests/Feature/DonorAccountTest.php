<?php

namespace Tests\Feature;

use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\KategoriDonasi;
use App\Models\User;
use App\Notifications\DonorAccountLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class DonorAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
    }

    private function donor(string $email = 'donor@example.test', string $status = 'aktif'): Donatur
    {
        $user = User::factory()->create([
            'email' => $email, 'role' => 'donatur', 'account_status' => $status,
            'password' => 'Password-Uji-123', 'email_verified_at' => $status === 'menunggu' ? null : now(),
        ]);

        return Donatur::create(['nama' => 'Donatur Tetap', 'email' => $email, 'whatsapp' => '62812'.random_int(10000000, 99999999), 'jenis' => 'tetap', 'user_id' => $user->id]);
    }

    private function donation(Donatur $donor, string $code, string $status = 'Berhasil'): Donasi
    {
        $category = KategoriDonasi::firstOrCreate(['nama' => 'Umum']);

        return Donasi::create([
            'kode' => $code, 'token_hash' => hash('sha256', $code), 'donatur_id' => $donor->id,
            'kategori_donasi_id' => $category->id, 'nama' => $donor->nama, 'whatsapp' => $donor->whatsapp,
            'nominal' => 50000, 'status' => $status,
        ]);
    }

    public function test_existing_data_migrates_without_creating_donor_accounts_or_claiming_history(): void
    {
        $migration = require database_path('migrations/2026_10_20_000200_add_donor_accounts.php');
        $requests = require database_path('migrations/2026_10_22_000100_add_donation_idempotency.php');
        $requests->down();
        $migration->down();
        $id = DB::table('users')->insertGetId(['name' => 'Admin lama', 'email' => 'old-admin@example.test', 'password' => Hash::make('Existing-password-123')]);
        $donorId = DB::table('donatur')->insertGetId(['nama' => 'Donatur lama', 'whatsapp' => '6281234567890']);
        $migration->up();
        $requests->up();
        $this->assertDatabaseHas('users', ['id' => $id, 'role' => 'pengurus', 'account_status' => 'aktif']);
        $this->assertDatabaseHas('donatur', ['id' => $donorId, 'jenis' => 'biasa', 'user_id' => null]);
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('Existing-password-123', User::findOrFail($id)->password));
    }

    public function test_admin_can_create_classify_and_invite_a_donor_but_not_reuse_an_existing_account_email(): void
    {
        $this->actingAs($admin = User::factory()->create());
        $data = ['nama' => 'Donatur baru', 'whatsapp' => '081234567890', 'jenis' => 'tetap', 'email' => 'NEW@example.test'];
        $this->post('/admin/data/donatur', array_replace($data, ['email' => '']))->assertSessionHasErrors('email');
        $this->post('/admin/data/donatur', array_replace($data, ['email' => $admin->email]))->assertSessionHasErrors('email');
        $this->post('/admin/data/donatur', $data)->assertRedirect();
        $donor = Donatur::firstOrFail();
        $this->assertSame('tetap', $donor->jenis);
        $this->assertSame('new@example.test', $donor->user->email);
        $this->assertSame('donatur', $donor->user->role);
        $this->assertSame('menunggu', $donor->user->account_status);
        $this->assertNull($donor->user->email_verified_at);
        Notification::assertNothingSent();
        $this->post(route('admin.donor.invite', $donor))->assertSessionHas('success');
        Notification::assertSentTo($donor->user, DonorAccountLink::class, fn ($notification) => $notification->activation);
        $this->get(route('admin.donor.account', $donor))->assertOk()->assertSee('Setujui & Kirim Aktivasi');
        $this->assertDatabaseHas('donor_account_audits', ['donatur_id' => $donor->id, 'action' => 'undangan_aktivasi_dibuat']);
        $this->delete('/admin/data/donatur/'.$donor->id)->assertSessionHasErrors('hapus');
    }

    public function test_activation_requires_a_valid_unused_token_and_verifies_email(): void
    {
        $donor = $this->donor(status: 'menunggu');
        $token = Password::broker('donors')->createToken($donor->user);
        $data = ['email' => $donor->email, 'token' => $token, 'password' => 'Activated-Password-123', 'password_confirmation' => 'Activated-Password-123'];
        $this->post('/donatur/login', ['email' => $donor->email, 'password' => 'Password-Uji-123'])->assertSessionHasErrors('email');
        $this->post('/donatur/reset-password', array_replace($data, ['token' => 'wrong']))->assertSessionHasErrors('email');
        $this->post('/donatur/reset-password', $data)->assertRedirect(route('donor.login'));
        $this->assertSame('aktif', $donor->user->fresh()->account_status);
        $this->assertNotNull($donor->user->fresh()->email_verified_at);
        $this->assertTrue(Hash::check($data['password'], $donor->user->fresh()->password));
        $this->post('/donatur/reset-password', $data)->assertSessionHasErrors('email');
        $this->post('/donatur/login', ['email' => $donor->email, 'password' => $data['password']])->assertRedirect(route('donor.account'));
        $this->get('/donatur/akun')->assertOk();
        $this->get('/admin')->assertForbidden();
        $this->post('/admin/data/donatur', ['jenis' => 'tetap'])->assertForbidden();
    }

    public function test_recovery_response_is_generic_and_only_active_verified_donors_receive_mail(): void
    {
        $active = $this->donor();
        $pending = $this->donor('pending@example.test', 'menunggu');
        $disabled = $this->donor('disabled@example.test', 'nonaktif');
        $admin = User::factory()->create();
        $responses = [];
        foreach ([$active->email, $pending->email, $disabled->email, $admin->email, 'missing@example.test'] as $email) {
            $this->post('/donatur/lupa-password', ['email' => $email])->assertSessionHas('success');
            $responses[] = session('success');
        }
        $this->assertCount(1, array_unique($responses));
        Notification::assertSentTo($active->user, DonorAccountLink::class, fn ($notification) => ! $notification->activation);
        Notification::assertNotSentTo($pending->user, DonorAccountLink::class);
        Notification::assertNotSentTo($disabled->user, DonorAccountLink::class);
        Notification::assertNotSentTo($admin, DonorAccountLink::class);
        Notification::assertCount(1);
    }

    public function test_reset_expires_and_revokes_existing_sessions_after_success(): void
    {
        $donor = $this->donor();
        $user = $donor->user;
        $token = Password::broker('donors')->createToken($user);
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'Recovered-Password-123', 'password_confirmation' => 'Recovered-Password-123'];
        $this->travel(61)->minutes();
        $this->post('/donatur/reset-password', $payload)->assertSessionHasErrors('email');
        $this->travelBack();
        $payload['token'] = Password::broker('donors')->createToken($user);
        DB::table('sessions')->insert(['id' => 'old-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->post('/donatur/reset-password', $payload)->assertRedirect(route('donor.login'));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-device']);
        $this->assertSame(1, $user->fresh()->auth_version);
        $this->actingAs($user->fresh())->withSession(['auth_version' => 0])->get('/donatur/akun')->assertRedirect(route('donor.login'));
        $this->assertGuest();
    }

    public function test_email_change_and_downgrade_revoke_tokens_and_require_new_activation(): void
    {
        $donor = $this->donor();
        $oldToken = Password::broker('donors')->createToken($donor->user);
        $this->actingAs($admin = User::factory()->create());
        $data = ['nama' => $donor->nama, 'whatsapp' => $donor->whatsapp, 'email' => 'changed@example.test', 'jenis' => 'tetap'];
        $this->put('/admin/data/donatur/'.$donor->id, $data)->assertSessionHasNoErrors();
        $user = $donor->user->fresh();
        $this->assertSame('menunggu', $user->account_status);
        $this->assertNull($user->email_verified_at);
        $this->assertFalse(Password::broker('donors')->tokenExists($user, $oldToken));
        $token = Password::broker('donors')->createToken($user);
        $this->put('/admin/data/donatur/'.$donor->id, array_replace($data, ['jenis' => 'biasa']))->assertSessionHasNoErrors();
        $this->assertSame('nonaktif', $user->fresh()->account_status);
        $this->assertFalse(Password::broker('donors')->tokenExists($user->fresh(), $token));
        $this->post(route('admin.donor.access', $donor), ['access' => 'aktif'])->assertStatus(409);
    }

    public function test_admin_can_suspend_and_restore_verified_access_and_user_management_cannot_edit_donors(): void
    {
        $donor = $this->donor();
        $this->actingAs(User::factory()->create());
        $this->get('/admin/data/pengguna')->assertOk()->assertDontSee($donor->email);
        $this->get('/admin/data/pengguna/'.$donor->user_id.'/edit')->assertNotFound();
        $this->put('/admin/data/pengguna/'.$donor->user_id, ['role' => 'pengurus'])->assertNotFound();
        $this->delete('/admin/data/pengguna/'.$donor->user_id)->assertNotFound();
        $this->post(route('admin.donor.access', $donor), ['access' => 'nonaktif'])->assertSessionHasNoErrors();
        $this->assertFalse($donor->user->fresh()->canUseDonorAccount());
        $this->post(route('admin.donor.access', $donor), ['access' => 'aktif'])->assertSessionHasNoErrors();
        $this->assertTrue($donor->user->fresh()->canUseDonorAccount());
    }

    public function test_history_is_not_auto_claimed_and_only_verified_owned_successes_are_totalled(): void
    {
        $donor = $this->donor();
        $another = $this->donor('other@example.test');
        $old = $this->donation($donor, 'OLD-PAID');
        $pending = $this->donation($donor, 'OWN-PENDING', 'Menunggu pembayaran');
        $foreign = $this->donation($another, 'FOREIGN-SECRET');
        $this->actingAs($donor->user)->withSession(['auth_version' => 0])->get('/donatur/akun')
            ->assertOk()->assertDontSee('OLD-PAID')->assertDontSee('FOREIGN-SECRET');
        $this->actingAs($admin = User::factory()->create())->withSession(['auth_version' => 0]);
        $this->post(route('admin.donor.verify', [$donor, $old]), [])->assertSessionHasErrors(['confirmed', 'reason']);
        $confirmation = ['confirmed' => 1, 'reason' => 'Bukti pembayaran diperiksa bersama donatur.'];
        $this->post(route('admin.donor.verify', [$donor, $foreign]), $confirmation)->assertStatus(409);
        $this->post(route('admin.donor.verify', [$donor, $old]), $confirmation)->assertSessionHasNoErrors();
        $this->post(route('admin.donor.verify', [$donor, $pending]), $confirmation)->assertSessionHasNoErrors();
        $this->post(route('admin.donor.verify', [$donor, $old]), $confirmation)->assertStatus(409);
        $this->actingAs($donor->user)->withSession(['auth_version' => 0])->get('/donatur/akun')
            ->assertOk()->assertSee('OLD-PAID')->assertSee('OWN-PENDING')->assertDontSee('FOREIGN-SECRET')->assertViewHas('total', 50000);
        $this->assertDatabaseHas('donasi', ['id' => $old->id, 'owner_user_id' => $donor->user_id, 'ownership_verified_by' => $admin->id]);
    }

    public function test_public_donations_remain_without_account_and_cannot_overwrite_donor_classification(): void
    {
        $donor = $this->donor();
        $category = KategoriDonasi::create(['nama' => 'Donasi Umum']);
        $this->post('/donasi', [
            'nama' => 'Nama dari form publik', 'whatsapp' => $donor->whatsapp, 'consent' => 1,
            'email' => 'spoofed@example.test', 'nominal' => 10000, 'kategori_donasi_id' => $category->id,
            'jenis' => 'tetap', 'user_id' => $donor->user_id, 'owner_user_id' => $donor->user_id,
        ])->assertSessionHasNoErrors();
        $this->assertNull(Donasi::firstOrFail()->owner_user_id);
        $this->assertSame($donor->email, $donor->fresh()->email);
        $this->assertDatabaseCount('users', 1);
        $this->post('/donasi', ['nama' => 'Donatur biasa', 'whatsapp' => '081212345678', 'consent' => 1, 'nominal' => 10000, 'kategori_donasi_id' => $category->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('donatur', ['whatsapp' => '6281212345678', 'jenis' => 'biasa', 'user_id' => null]);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_donor_cannot_log_in_as_admin_and_admin_cannot_reset_through_donor_route(): void
    {
        $donor = $this->donor();
        $this->post('/admin/login', ['email' => $donor->email, 'password' => 'Password-Uji-123'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $admin = User::factory()->create();
        $token = Password::broker('donors')->createToken($admin);
        $this->post('/donatur/reset-password', ['email' => $admin->email, 'token' => $token, 'password' => 'Not-Allowed-123', 'password_confirmation' => 'Not-Allowed-123'])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
        $this->get('/donatur/login')->assertOk();
        $this->get('/donatur/lupa-password')->assertOk();
        $this->get(route('donor.password.reset', ['token' => 'sample', 'email' => $donor->email]))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }
}
