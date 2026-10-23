<?php

namespace Tests\Feature;

use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\KategoriDonasi;
use App\Models\KategoriPengeluaran;
use App\Models\Pengeluaran;
use App\Models\User;
use App\Notifications\DonorAccountLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        Notification::fake();
    }

    private function createDonor(
        string $email = 'donatur.tetap@example.test',
        string $status = 'aktif',
        bool $verified = true,
        string $role = 'donatur',
        string $jenis = 'tetap',
        string $password = 'Rahasia-Kuat-123'
    ): array {
        $user = User::factory()->create([
            'name' => 'Ahmad Donatur',
            'email' => $email,
            'role' => $role,
            'account_status' => $status,
            'password' => $password,
            'email_verified_at' => $verified ? now() : null,
        ]);

        $donor = Donatur::create([
            'nama' => $user->name,
            'email' => $email,
            'whatsapp' => '62812'.random_int(10000000, 99999999),
            'jenis' => $jenis,
            'user_id' => $user->id,
        ]);

        return [$user, $donor];
    }

    public function test_profile_and_password_contract_and_session_revocation(): void
    {
        [$user] = $this->createDonor();
        $token = $user->createToken('current')->plainTextToken;
        $other = $user->createToken('other')->accessToken;
        $reset = Password::broker('donors')->createToken($user);
        $this->withToken($token)->putJson('/api/v1/profile', [
            'nama' => 'Nama Baru', 'whatsapp' => '081234567899',
        ])->assertOk()->assertJsonPath('user.account_status', 'aktif')->assertJsonPath('user.name', 'Nama Baru');
        $this->withToken($token)->putJson('/api/v1/profile/password', [
            'current_password' => 'wrong', 'password' => 'Password-Baru-123',
            'password_confirmation' => 'Password-Baru-123',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $other->id]);
        $this->withToken($token)->putJson('/api/v1/profile/password', [
            'current_password' => 'Rahasia-Kuat-123', 'password' => 'Password-Baru-123',
            'password_confirmation' => 'Password-Baru-123',
        ])->assertOk();
        $this->assertTrue(Hash::check('Password-Baru-123', $user->fresh()->password));
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $other->id]);
        $this->assertSame(1, $user->tokens()->count());
        $this->assertFalse(Password::broker('donors')->tokenExists($user, $reset));
        $this->assertSame(1, $user->fresh()->auth_version);
        $this->withToken($token)->getJson('/api/v1/profile')->assertOk();
    }

    public function test_donor_registration_approval_and_login_lifecycle(): void
    {
        // 1. Mobile donor registration
        $registerResponse = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Calon Donatur Baru',
            'whatsapp' => '081298765432',
            'email' => 'calon.donatur@example.test',
            'password' => 'PasswordKuat123',
            'password_confirmation' => 'PasswordKuat123',
        ]);

        $registerResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'menunggu')
            ->assertJsonPath('data.email', 'calon.donatur@example.test');

        $user = User::where('email', 'calon.donatur@example.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('menunggu', $user->account_status);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('donatur', $user->role);

        $donor = Donatur::where('user_id', $user->id)->first();
        $this->assertNotNull($donor);
        $this->assertSame('tetap', $donor->jenis);
        $this->assertSame('6281298765432', $donor->whatsapp);

        $this->assertDatabaseHas('donor_account_audits', [
            'donatur_id' => $donor->id,
            'action' => 'pendaftaran_mobile_mandiri',
        ]);

        // 2. Cannot login while waiting for approval
        $loginWhilePending = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'calon.donatur@example.test',
            'password' => 'PasswordKuat123',
        ]);
        $loginWhilePending->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // 3. Admin verifies and approves the registration
        $admin = User::factory()->create([
            'role' => 'pengurus',
            'account_status' => 'aktif',
            'email_verified_at' => now(),
        ]);

        $approveResponse = $this->actingAs($admin)
            ->post(route('admin.donor.approve', $donor));

        $approveResponse->assertRedirect();
        $this->assertSame('menunggu', $user->fresh()->account_status);
        $this->assertNull($user->fresh()->email_verified_at);

        $this->assertDatabaseHas('donor_account_audits', [
            'donatur_id' => $donor->id,
            'actor_user_id' => $admin->id,
            'action' => 'undangan_aktivasi_dibuat',
        ]);

        // Email ownership is verified by redeeming the invitation, not by approval.
        $invitation = Notification::sent($user, DonorAccountLink::class)->first();
        $this->assertNotNull($invitation);
        $this->post(route('donor.password.update'), [
            'email' => $user->email, 'token' => $invitation->token,
            'password' => 'PasswordKuat123', 'password_confirmation' => 'PasswordKuat123',
        ])->assertRedirect(route('donor.login'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        // 4. Now donor can successfully login via mobile
        $loginAfterApproval = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'calon.donatur@example.test',
            'password' => 'PasswordKuat123',
            'device_name' => 'Flutter-Mobile',
        ]);

        $loginAfterApproval->assertOk()
            ->assertJsonPath('user.account_status', 'aktif')
            ->assertJsonPath('donor.jenis', 'tetap')
            ->assertJsonStructure(['token']);
    }

    public function test_active_verified_donor_can_login_and_receive_sanctum_token(): void
    {
        [$user, $donor] = $this->createDonor('tetap@example.test', 'aktif', true, 'donatur', 'tetap', 'PasswordTetap123');

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'tetap@example.test',
            'password' => 'PasswordTetap123',
            'device_name' => 'Flutter-Pixel-7',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role', 'account_status'],
                'donor' => ['id', 'nama', 'whatsapp', 'email', 'jenis'],
            ]);

        $this->assertSame('Bearer', $response->json('token_type'));
        $this->assertSame($user->id, $response->json('user.id'));
        $this->assertSame($donor->id, $response->json('donor.id'));
    }

    public function test_admin_and_unverified_or_inactive_accounts_cannot_login_to_mobile_api(): void
    {
        // 1. Admin attempt
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'role' => 'pengurus',
            'account_status' => 'aktif',
            'password' => 'PasswordAdmin123',
            'email_verified_at' => now(),
        ]);

        $this->postJson(route('api.v1.auth.login'), [
            'email' => 'admin@example.test',
            'password' => 'PasswordAdmin123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // 2. Inactive donor
        $this->createDonor('inactive@example.test', 'nonaktif', true, 'donatur', 'tetap', 'Pass123');
        $this->postJson(route('api.v1.auth.login'), [
            'email' => 'inactive@example.test',
            'password' => 'Pass123',
        ])->assertStatus(422);

        // 3. Unverified donor (menunggu aktivasi)
        $this->createDonor('pending@example.test', 'menunggu', false, 'donatur', 'tetap', 'Pass123');
        $this->postJson(route('api.v1.auth.login'), [
            'email' => 'pending@example.test',
            'password' => 'Pass123',
        ])->assertStatus(422);

        // 4. Regular donor without permanent status
        $this->createDonor('biasa@example.test', 'aktif', true, 'donatur', 'biasa', 'Pass123');
        $this->postJson(route('api.v1.auth.login'), [
            'email' => 'biasa@example.test',
            'password' => 'Pass123',
        ])->assertStatus(422);
    }

    public function test_authenticated_donor_can_view_me_and_logout_revoking_token(): void
    {
        [$user, $donor] = $this->createDonor();
        $token = $user->createToken('test-device')->plainTextToken;

        // Unauthenticated attempt
        $this->getJson(route('api.v1.auth.me'))->assertStatus(401);

        // Authenticated me
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.auth.me'));

        $response->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('donor.whatsapp', $donor->whatsapp);

        // Logout
        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.v1.auth.logout'));

        $logoutResponse->assertOk()
            ->assertJson(['message' => 'Sesi mobile berhasil diakhiri.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Token revoked
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.auth.me'))
            ->assertStatus(401);
    }

    public function test_forgot_password_returns_generic_message_and_queues_notification(): void
    {
        [$user, $donor] = $this->createDonor('donor.recovery@example.test');

        $response = $this->postJson(route('api.v1.auth.forgot-password'), [
            'email' => 'donor.recovery@example.test',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Jika email terdaftar pada akun donatur aktif, tautan pemulihan akan diproses. Periksa kotak masuk dan folder spam.']);

        Notification::assertSentTo($user, DonorAccountLink::class);

        // Unknown email returns the same message without leaking user existence
        $unknownResponse = $this->postJson(route('api.v1.auth.forgot-password'), [
            'email' => 'unknown.stranger@example.test',
        ]);

        $unknownResponse->assertOk()
            ->assertJson(['message' => 'Jika email terdaftar pada akun donatur aktif, tautan pemulihan akan diproses. Periksa kotak masuk dan folder spam.']);
    }

    public function test_donor_profile_can_be_viewed_and_updated_with_validation(): void
    {
        [$user, $donor] = $this->createDonor();
        $token = $user->createToken('test')->plainTextToken;

        // View profile
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.profile.show'))
            ->assertOk()
            ->assertJsonPath('user.name', 'Ahmad Donatur')
            ->assertJsonPath('donor.whatsapp', $donor->whatsapp);

        // Update profile
        $updateResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson(route('api.v1.profile.update'), [
                'nama' => 'Ahmad Surya M.Pd',
                'whatsapp' => '085712345678', // will be normalized to 6285712345678
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('user.name', 'Ahmad Surya M.Pd')
            ->assertJsonPath('donor.nama', 'Ahmad Surya M.Pd')
            ->assertJsonPath('donor.whatsapp', '6285712345678');

        $this->assertDatabaseHas('donatur', [
            'id' => $donor->id,
            'nama' => 'Ahmad Surya M.Pd',
            'whatsapp' => '6285712345678',
        ]);

        // Invalid whatsapp format rejected
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson(route('api.v1.profile.update'), [
                'nama' => 'Ahmad',
                'whatsapp' => 'invalid-phone-123',
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['whatsapp']);
    }

    public function test_public_references_endpoints_return_data(): void
    {
        KategoriDonasi::create(['nama' => 'Pendidikan Santri', 'deskripsi' => 'Beasiswa']);

        $resCat = $this->getJson(route('api.v1.references.donation-categories'));
        $resCat->assertOk()
            ->assertJsonFragment(['nama' => 'Pendidikan Santri']);

        $resPresets = $this->getJson(route('api.v1.references.nominal-presets'));
        $resPresets->assertOk()
            ->assertJsonStructure(['presets', 'minimum_nominal', 'currency'])
            ->assertJson(['minimum_nominal' => 5000]);
    }

    public function test_donor_can_create_donation_with_automatic_identity_and_payment(): void
    {
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'mock-snap-token-abc-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/mock-snap-token-abc-123',
            ], 201),
        ]);

        [$user, $donor] = $this->createDonor();
        $token = $user->createToken('test')->plainTextToken;
        $category = KategoriDonasi::create(['nama' => 'Operasional']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.v1.donations.store'), [
                'kategori_donasi_id' => $category->id,
                'nominal' => 150000,
                'catatan' => 'Semoga berkah untuk panti',
                // Client attempts to spoof donor_id & ownership - must be ignored!
                'donatur_id' => 9999,
                'owner_user_id' => 9999,
                'nama' => 'Spoofed Attacker',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nominal', '150000.00')
            ->assertJsonPath('data.status', 'Menunggu pembayaran')
            ->assertJsonPath('data.kategori.nama', 'Operasional')
            ->assertJsonPath('data.payment.snap_token', 'mock-snap-token-abc-123')
            ->assertJsonPath('data.payment.payment_url', 'https://app.sandbox.midtrans.com/snap/v2/vtweb/mock-snap-token-abc-123');

        $donation = Donasi::firstOrFail();
        $this->assertSame($user->id, $donation->owner_user_id);
        $this->assertSame($donor->id, $donation->donatur_id);
        $this->assertSame($user->name, $donation->nama);
        $this->assertSame($donor->whatsapp, $donation->whatsapp);
        $this->assertSame('online', $donation->sumber);
        $this->assertNotNull($donation->ownership_verified_at);
    }

    public function test_rapid_duplicate_donation_request_returns_existing_pending_transaction(): void
    {
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'mock-snap-token-1',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/mock-snap-token-1',
            ], 201),
        ]);

        [$user, $donor] = $this->createDonor();
        $token = $user->createToken('test')->plainTextToken;
        $category = KategoriDonasi::create(['nama' => 'Kesehatan']);

        // First creation
        $res1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.v1.donations.store'), [
                'kategori_donasi_id' => $category->id,
                'nominal' => 200000,
            ]);
        $res1->assertStatus(201);
        $firstId = $res1->json('data.id');

        // Immediate duplicate request within 15 seconds
        $res2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.v1.donations.store'), [
                'kategori_donasi_id' => $category->id,
                'nominal' => 200000,
            ]);

        $res2->assertOk()
            ->assertJsonPath('data.id', $firstId)
            ->assertJson(['message' => 'Transaksi serupa sedang diproses. Silakan lanjutkan transaksi yang sudah ada.']);

        $this->assertSame(1, Donasi::count());
    }

    public function test_donor_cannot_view_or_access_another_donors_donation(): void
    {
        [$userA, $donorA] = $this->createDonor('donor.a@example.test');
        [$userB, $donorB] = $this->createDonor('donor.b@example.test');

        $tokenA = $userA->createToken('testA')->plainTextToken;
        $tokenB = $userB->createToken('testB')->plainTextToken;

        $category = KategoriDonasi::create(['nama' => 'Umum']);

        $donationA = Donasi::create([
            'kode' => 'DON-TESTA',
            'token_hash' => hash('sha256', 'tokenA'),
            'donatur_id' => $donorA->id,
            'kategori_donasi_id' => $category->id,
            'nama' => $userA->name,
            'whatsapp' => $donorA->whatsapp,
            'nominal' => 100000,
            'status' => 'Berhasil',
            'owner_user_id' => $userA->id,
            'ownership_verified_at' => now(),
            'sumber' => 'online',
        ]);

        // User B attempts to view User A's donation
        $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->getJson(route('api.v1.donations.show', $donationA->id))
            ->assertStatus(403);

        // User B's donation index does not list User A's donation
        $indexB = $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->getJson(route('api.v1.donations.index'));
        $indexB->assertOk();
        $this->assertCount(0, $indexB->json('data'));

        // User A can view their own donation
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->getJson(route('api.v1.donations.show', $donationA->id))
            ->assertOk()
            ->assertJsonPath('data.id', $donationA->id);
    }

    public function test_donations_summary_totals_only_verified_success_transactions(): void
    {
        [$user, $donor] = $this->createDonor();
        $token = $user->createToken('test')->plainTextToken;
        $category = KategoriDonasi::create(['nama' => 'Infaq']);

        // 1. Success donation (should count)
        Donasi::create([
            'kode' => 'DON-1',
            'token_hash' => hash('sha256', 't1'),
            'donatur_id' => $donor->id,
            'kategori_donasi_id' => $category->id,
            'nama' => $user->name,
            'whatsapp' => $donor->whatsapp,
            'nominal' => 100000,
            'status' => 'Berhasil',
            'owner_user_id' => $user->id,
            'ownership_verified_at' => now(),
            'sumber' => 'online',
        ]);

        // 2. Another success donation (should count)
        Donasi::create([
            'kode' => 'DON-2',
            'token_hash' => hash('sha256', 't2'),
            'donatur_id' => $donor->id,
            'kategori_donasi_id' => $category->id,
            'nama' => $user->name,
            'whatsapp' => $donor->whatsapp,
            'nominal' => 250000,
            'status' => 'Berhasil',
            'owner_user_id' => $user->id,
            'ownership_verified_at' => now(),
            'sumber' => 'online',
        ]);

        // 3. Pending donation (should not count in total nominal)
        Donasi::create([
            'kode' => 'DON-3',
            'token_hash' => hash('sha256', 't3'),
            'donatur_id' => $donor->id,
            'kategori_donasi_id' => $category->id,
            'nama' => $user->name,
            'whatsapp' => $donor->whatsapp,
            'nominal' => 500000,
            'status' => 'Menunggu pembayaran',
            'owner_user_id' => $user->id,
            'ownership_verified_at' => now(),
            'sumber' => 'online',
        ]);

        $summary = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.v1.donations.summary'));

        $summary->assertOk()
            ->assertJson([
                'total_nominal_berhasil' => '350000.00',
                'count_berhasil' => 2,
                'count_menunggu' => 1,
                'count_total' => 3,
            ]);
    }

    public function test_transparency_summary_and_expenses_filter_and_hide_private_proofs(): void
    {
        $admin = User::factory()->create(['role' => 'pengurus']);
        $catPengeluaran = KategoriPengeluaran::create(['nama' => 'Konsumsi Santri']);

        // Booked expense with public proof
        Storage::disk('local')->put('pengeluaran/bukti-publik.jpg', 'public-content');
        $expPublic = Pengeluaran::create([
            'kode' => 'EXP-202610-001',
            'tanggal' => '2026-10-15',
            'kategori_pengeluaran_id' => $catPengeluaran->id,
            'keperluan' => 'Beras 100kg',
            'nominal' => 1200000,
            'penerima' => 'Toko Barokah',
            'status' => 'Dibukukan',
            'is_public' => true,
            'bukti_path' => 'pengeluaran/bukti-publik.jpg',
            'created_by' => $admin->id,
        ]);

        // Booked expense with private proof
        Storage::disk('local')->put('pengeluaran/bukti-privat.pdf', 'private-sensitive-content');
        $expPrivate = Pengeluaran::create([
            'kode' => 'EXP-202610-002',
            'tanggal' => '2026-10-16',
            'kategori_pengeluaran_id' => $catPengeluaran->id,
            'keperluan' => 'Biaya Pengobatan Santri',
            'nominal' => 450000,
            'penerima' => 'Klinik NU',
            'status' => 'Dibukukan',
            'is_public' => false,
            'bukti_path' => 'pengeluaran/bukti-privat.pdf',
            'created_by' => $admin->id,
        ]);

        // Draft expense (must NEVER appear in transparency)
        Pengeluaran::create([
            'kode' => 'EXP-202610-DRAFT',
            'tanggal' => '2026-10-17',
            'kategori_pengeluaran_id' => $catPengeluaran->id,
            'keperluan' => 'Draft belum disahkan',
            'nominal' => 999999,
            'status' => 'Draft',
            'is_public' => true,
            'created_by' => $admin->id,
        ]);

        // Check transparency summary
        $summary = $this->getJson(route('api.v1.transparency.summary', ['month' => 10, 'year' => 2026]));
        $summary->assertOk()
            ->assertJsonPath('total_pengeluaran', '1650000.00'); // 1200000 + 450000

        // Check transparency expenses list
        $expenses = $this->getJson(route('api.v1.transparency.expenses', ['month' => 10, 'year' => 2026]));
        $expenses->assertOk();

        $data = collect($expenses->json('data'));
        $this->assertCount(2, $data); // Only 2 booked expenses, draft is excluded!

        $publicItem = $data->firstWhere('id', $expPublic->id);
        $this->assertTrue($publicItem['has_public_proof']);
        $this->assertNotNull($publicItem['bukti_url']);

        $privateItem = $data->firstWhere('id', $expPrivate->id);
        $this->assertFalse($privateItem['has_public_proof']);
        $this->assertNull($privateItem['bukti_url']);
    }

    public function test_private_expense_proof_cannot_be_downloaded_via_public_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'pengurus']);
        $cat = KategoriPengeluaran::create(['nama' => 'Kesehatan']);
        Storage::disk('local')->put('pengeluaran/private.pdf', 'confidential-medical-receipt');

        $privateExp = Pengeluaran::create([
            'kode' => 'EXP-PRIVAT',
            'tanggal' => '2026-10-18',
            'kategori_pengeluaran_id' => $cat->id,
            'keperluan' => 'Periksa Dokter',
            'nominal' => 300000,
            'status' => 'Dibukukan',
            'is_public' => false,
            'bukti_path' => 'pengeluaran/private.pdf',
            'created_by' => $admin->id,
        ]);

        // Downloading private proof must be rejected with 403 Forbidden!
        $response = $this->get(route('api.v1.transparency.expense.proof', $privateExp->id));
        $response->assertStatus(403);
    }

    public function test_public_expense_proof_can_be_downloaded(): void
    {
        $admin = User::factory()->create(['role' => 'pengurus']);
        $cat = KategoriPengeluaran::create(['nama' => 'Pendidikan']);
        Storage::disk('local')->put('pengeluaran/nota-buku.jpg', 'fake-image-bytes-123');

        $publicExp = Pengeluaran::create([
            'kode' => 'EXP-PUBLIK',
            'tanggal' => '2026-10-19',
            'kategori_pengeluaran_id' => $cat->id,
            'keperluan' => 'Buku Pelajaran',
            'nominal' => 200000,
            'status' => 'Dibukukan',
            'is_public' => true,
            'bukti_path' => 'pengeluaran/nota-buku.jpg',
            'created_by' => $admin->id,
        ]);

        $response = $this->get(route('api.v1.transparency.expense.proof', $publicExp->id));
        $response->assertOk();
    }

    public function test_donor_can_cancel_pending_donation(): void
    {
        config(['panti.midtrans_key' => 'test-key']);
        Http::fake([
            'https://api.sandbox.midtrans.com/v2/*/cancel' => Http::response(['status_code' => '200', 'transaction_status' => 'cancel', 'order_id' => 'DON-CANCEL-1'], 200),
            'https://api.sandbox.midtrans.com/v2/*/status' => Http::response(['status_code' => '404', 'transaction_status' => 'not_found'], 404),
        ]);

        [$user, $donor] = $this->createDonor('donor.cancel@example.test');
        $token = $user->createToken('test')->plainTextToken;
        $category = KategoriDonasi::create(['nama' => 'Pendidikan Khusus']);

        $donation = Donasi::create([
            'kode' => 'DON-CANCEL-1',
            'token_hash' => hash('sha256', 'token-cancel-1'),
            'donatur_id' => $donor->id,
            'kategori_donasi_id' => $category->id,
            'nama' => $user->name,
            'whatsapp' => $donor->whatsapp,
            'nominal' => 75000,
            'status' => 'Menunggu pembayaran',
            'owner_user_id' => $user->id,
            'ownership_verified_at' => now(),
            'sumber' => 'online',
        ]);
        $donation->payment()->create([
            'order_id' => $donation->kode,
            'status' => 'requesting',
        ]);

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.v1.donations.cancel', $donation->id));

        $res->assertOk()
            ->assertJsonPath('data.status', 'Dibatalkan')
            ->assertJsonPath('data.payment.status', 'cancel');

        $this->assertSame('Dibatalkan', $donation->fresh()->status);
        $this->assertSame('cancel', $donation->payment->fresh()->status);

        // Cannot cancel again (409 Conflict)
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.v1.donations.cancel', $donation->id))
            ->assertStatus(409);
    }
}
