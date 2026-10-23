<?php

namespace Tests\Feature;

use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\KategoriDonasi;
use App\Models\KategoriPengeluaran;
use App\Models\Pengeluaran;
use App\Models\SaldoPembukaan;
use App\Models\User;
use App\Services\CashflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseAndCashflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'pengurus', 'account_status' => 'aktif']);
    }

    private function donor(): User
    {
        return User::factory()->create(['role' => 'donatur', 'account_status' => 'aktif', 'email_verified_at' => now()]);
    }

    public function test_cashflow_uses_net_receipts_and_period_appropriate_opening(): void
    {
        $admin = $this->admin();
        $category = KategoriDonasi::create(['nama' => 'Umum']);
        $donor = Donatur::create(['nama' => 'Donor', 'whatsapp' => '6281234567890']);
        foreach (['2026-01-01' => 1000, '2026-11-01' => 999999] as $date => $amount) {
            SaldoPembukaan::create(['tanggal_mulai' => $date, 'nominal' => $amount, 'disahkan_oleh' => $admin->id]);
        }
        foreach (['2026-09-10', '2026-10-10'] as $date) {
            Donasi::create([
                'kode' => 'DON-'.$date, 'token_hash' => hash('sha256', $date),
                'donatur_id' => $donor->id, 'kategori_donasi_id' => $category->id,
                'nama' => 'Donor', 'whatsapp' => $donor->whatsapp,
                'nominal' => 100000, 'biaya' => 2000, 'sumber' => 'online',
                'status' => 'Berhasil', 'dibayar_pada' => $date,
            ]);
        }
        $service = app(CashflowService::class);
        $october = $service->calculate('2026-10-01', '2026-10-31');
        $this->assertSame('99000.00', $october['saldo_awal']);
        $this->assertSame('98000.00', $october['penerimaan']['total']);
        $this->assertSame('100000.00', $october['penerimaan']['bruto_total']);
        $this->assertSame('2000.00', $october['penerimaan']['biaya_gateway_total']);
        $this->assertSame('197000.00', $october['saldo_akhir']);
        $this->assertSame('2026-01-01', $october['bookkeeping_start']);
        $before = $service->calculate('2025-12-01', '2025-12-31');
        $this->assertNull($before['opening_balance']);
        $this->assertSame('0.00', $before['saldo_akhir']);
        $november = $service->calculate('2026-11-01', '2026-11-30');
        $this->assertSame('999999.00', $november['saldo_akhir']);
        SaldoPembukaan::query()->delete();
        $this->assertSame('98000.00', $service->calculate('2026-10-01', '2026-10-31')['saldo_awal']);
    }

    public function test_expense_draft_lifecycle_and_posting_reduces_cashflow(): void
    {
        $this->actingAs($admin = $this->admin());
        $category = KategoriPengeluaran::create(['nama' => 'Pangan Santri']);

        $proof = UploadedFile::fake()->create('kuitansi.jpg', 200, 'image/jpeg');

        // 1. Create Draft
        $response = $this->post(route('admin.expenses.store'), [
            'tanggal' => '2026-10-20',
            'kategori_pengeluaran_id' => $category->id,
            'keperluan' => 'Beras 50 kg dan telur',
            'nominal' => 650000,
            'penerima' => 'Toko Barokah',
            'keterangan' => 'Kebutuhan makan santri minggu ke-3',
            'bukti' => $proof,
            'is_public' => '1',
        ]);
        $response->assertRedirect();

        $expense = Pengeluaran::firstOrFail();
        $this->assertSame('Draft', $expense->status);
        $this->assertSame('650000.00', (string) $expense->nominal);
        $this->assertTrue($expense->is_public);
        $this->assertNotNull($expense->bukti_path);
        Storage::assertExists($expense->bukti_path);

        // Initial cashflow: draft does NOT reduce balance
        $cashflow = app(CashflowService::class)->calculate('2026-10-01', '2026-10-31');
        $this->assertSame('0.00', $cashflow['pengeluaran']['total']);
        $this->assertSame('0.00', $cashflow['saldo_akhir']);

        // 2. Edit Draft
        $this->put(route('admin.expenses.update', $expense->id), [
            'tanggal' => '2026-10-20',
            'kategori_pengeluaran_id' => $category->id,
            'keperluan' => 'Beras 60 kg dan telur',
            'nominal' => 700000,
            'penerima' => 'Toko Barokah',
        ])->assertRedirect();
        $this->assertSame('700000.00', (string) $expense->fresh()->nominal);

        // 3. Post (Dibukukan)
        $this->post(route('admin.expenses.post', $expense->id))->assertRedirect();
        $expense->refresh();
        $this->assertSame('Dibukukan', $expense->status);
        $this->assertSame($admin->id, $expense->posted_by);
        $this->assertNotNull($expense->posted_at);

        // Cashflow now reflects the expense
        $cashflow = app(CashflowService::class)->calculate('2026-10-01', '2026-10-31');
        $this->assertSame('700000.00', $cashflow['pengeluaran']['total']);
        $this->assertSame('-700000.00', $cashflow['saldo_akhir']);

        // 4. Booked expense cannot be edited or deleted directly
        $this->get(route('admin.expenses.edit', $expense->id))->assertForbidden();
        $this->put(route('admin.expenses.update', $expense->id), [
            'tanggal' => '2026-10-20',
            'kategori_pengeluaran_id' => $category->id,
            'keperluan' => 'Diubah paksa',
            'nominal' => 100000,
        ])->assertForbidden();
        $this->delete(route('admin.expenses.destroy', $expense->id))->assertStatus(409);
    }

    public function test_booked_expense_can_be_cancelled_with_reason_reverting_cashflow(): void
    {
        $this->actingAs($admin = $this->admin());
        $category = KategoriPengeluaran::create(['nama' => 'Kesehatan Santri']);

        $expense = Pengeluaran::create([
            'kode' => 'PGL-TEST001',
            'tanggal' => '2026-10-20',
            'kategori_pengeluaran_id' => $category->id,
            'keperluan' => 'Obat batuk & vitamin',
            'nominal' => 150000,
            'status' => 'Dibukukan',
            'created_by' => $admin->id,
            'posted_by' => $admin->id,
            'posted_at' => now(),
        ]);

        $cashflowBefore = app(CashflowService::class)->calculate('2026-10-01', '2026-10-31');
        $this->assertSame('150000.00', $cashflowBefore['pengeluaran']['total']);

        // Cancel with reason
        $this->post(route('admin.expenses.cancel', $expense->id), [
            'alasan' => 'Nota diretur karena barang diganti',
        ])->assertRedirect();

        $expense->refresh();
        $this->assertSame('Dibatalkan', $expense->status);
        $this->assertSame($admin->id, $expense->cancelled_by);
        $this->assertNotNull($expense->cancelled_at);
        $this->assertSame('Nota diretur karena barang diganti', $expense->cancellation_reason);

        // Cashflow calculation reverts the expense effect
        $cashflowAfter = app(CashflowService::class)->calculate('2026-10-01', '2026-10-31');
        $this->assertSame('0.00', $cashflowAfter['pengeluaran']['total']);

        // Cannot cancel again
        $this->post(route('admin.expenses.cancel', $expense->id), [
            'alasan' => 'Batal dua kali',
        ])->assertStatus(409);
    }

    public function test_draft_can_be_deleted(): void
    {
        $this->actingAs($admin = $this->admin());
        $category = KategoriPengeluaran::create(['nama' => 'Operasional']);
        $proof = UploadedFile::fake()->create('draft.jpg', 100, 'image/jpeg');

        $this->post(route('admin.expenses.store'), [
            'tanggal' => '2026-10-20',
            'kategori_pengeluaran_id' => $category->id,
            'keperluan' => 'Draft salah buat',
            'nominal' => 50000,
            'bukti' => $proof,
        ]);

        $expense = Pengeluaran::firstOrFail();
        $filePath = $expense->bukti_path;
        Storage::assertExists($filePath);

        $this->delete(route('admin.expenses.destroy', $expense->id))->assertRedirect(route('admin.expenses.index'));
        $this->assertDatabaseMissing('pengeluaran', ['id' => $expense->id]);
        Storage::assertMissing($filePath);
    }

    public function test_manual_donation_recording_and_cashflow_integration(): void
    {
        $this->actingAs($admin = $this->admin());
        $category = KategoriDonasi::create(['nama' => 'Donasi Umum']);
        $proof = UploadedFile::fake()->create('transfer.jpg', 200, 'image/jpeg');

        $this->post(route('admin.donation.manual.store'), [
            'nama' => 'Hamba Allah',
            'whatsapp' => '081234567899',
            'email' => 'hambaallah@example.test',
            'nominal' => 500000,
            'kategori_donasi_id' => $category->id,
            'metode' => 'transfer',
            'tanggal_diterima' => '2026-10-20',
            'referensi_manual' => 'TRF-BCA-987654',
            'bukti' => $proof,
            'catatan' => 'Titipan zakat mal',
        ])->assertRedirect();

        $donation = Donasi::where('sumber', 'manual')->firstOrFail();
        $this->assertSame('Berhasil', $donation->status);
        $this->assertSame('transfer', $donation->metode);
        $this->assertSame('TRF-BCA-987654', $donation->referensi_manual);
        $this->assertSame($admin->id, $donation->pencatat_user_id);
        $this->assertSame('6281234567899', $donation->whatsapp);
        $this->assertNotNull($donation->bukti_transfer);
        Storage::assertExists($donation->bukti_transfer);

        // Included in cashflow
        $cashflow = app(CashflowService::class)->calculate('2026-10-01', '2026-10-31');
        $this->assertSame('500000.00', $cashflow['penerimaan']['total']);
        $this->assertSame('500000.00', $cashflow['penerimaan']['manual_transfer_total']);
        $this->assertSame(1, $cashflow['penerimaan']['manual_transfer_count']);
        $this->assertSame('500000.00', $cashflow['saldo_akhir']);
    }

    public function test_opening_balance_and_cashflow_reconciliation_formula(): void
    {
        $this->actingAs($admin = $this->admin());
        $donasiCat = KategoriDonasi::create(['nama' => 'Umum']);
        $expenseCat = KategoriPengeluaran::create(['nama' => 'Operasional']);

        // Set Saldo Pembukaan: Rp5.000.000 per 2026-01-01
        $this->post(route('admin.cashflow.opening'), [
            'tanggal_mulai' => '2026-01-01',
            'nominal' => 5000000,
            'keterangan' => 'Saldo awal kas panti 2026',
        ])->assertRedirect();

        $this->assertDatabaseHas('saldo_pembukaan', [
            'nominal' => 5000000,
            'disahkan_oleh' => $admin->id,
        ]);

        $donor = Donatur::create(['nama' => 'Donatur Test', 'whatsapp' => '6281234567890']);

        // Online donation in October 2026: Rp200.000
        Donasi::create([
            'kode' => 'DON-ONLINE01',
            'token_hash' => hash('sha256', 'tok1'),
            'donatur_id' => $donor->id,
            'kategori_donasi_id' => $donasiCat->id,
            'nama' => 'Donatur Online',
            'whatsapp' => '628111111111',
            'nominal' => 200000,
            'biaya' => 0,
            'status' => 'Berhasil',
            'sumber' => 'online',
            'metode' => 'midtrans',
            'dibayar_pada' => '2026-10-10',
        ]);

        // Manual donation in October 2026: Rp300.000
        Donasi::create([
            'kode' => 'DON-MANUAL01',
            'token_hash' => hash('sha256', 'tok2'),
            'donatur_id' => $donor->id,
            'kategori_donasi_id' => $donasiCat->id,
            'nama' => 'Donatur Tunai',
            'whatsapp' => '628222222222',
            'nominal' => 300000,
            'status' => 'Berhasil',
            'sumber' => 'manual',
            'metode' => 'tunai',
            'dibayar_pada' => '2026-10-15',
        ]);

        // Booked expense in October 2026: Rp150.000
        Pengeluaran::create([
            'kode' => 'PGL-OCT01',
            'tanggal' => '2026-10-18',
            'kategori_pengeluaran_id' => $expenseCat->id,
            'keperluan' => 'Listrik & WiFi',
            'nominal' => 150000,
            'status' => 'Dibukukan',
            'created_by' => $admin->id,
            'posted_by' => $admin->id,
            'posted_at' => now(),
        ]);

        // Cashflow calculation: 5.000.000 + 500.000 - 150.000 = 5.350.000
        $cashflow = app(CashflowService::class)->calculate('2026-10-01', '2026-10-31');
        $this->assertSame('5000000.00', $cashflow['saldo_awal']);
        $this->assertSame('500000.00', $cashflow['penerimaan']['total']);
        $this->assertSame('150000.00', $cashflow['pengeluaran']['total']);
        $this->assertSame('5350000.00', $cashflow['saldo_akhir']);
    }

    public function test_expense_proof_privacy_and_authorization(): void
    {
        $admin = $this->admin();
        $donor = $this->donor();
        $category = KategoriPengeluaran::create(['nama' => 'Utilitas']);
        $file = UploadedFile::fake()->create('secret-receipt.pdf', 150, 'application/pdf');

        $path = $file->store('expenses');
        $expense = Pengeluaran::create([
            'kode' => 'PGL-PRIV001',
            'tanggal' => '2026-10-20',
            'kategori_pengeluaran_id' => $category->id,
            'keperluan' => 'Kuitansi privat',
            'nominal' => 250000,
            'status' => 'Dibukukan',
            'bukti_path' => $path,
            'is_public' => false,
            'created_by' => $admin->id,
        ]);

        // Guest is redirected to login
        $this->get(route('admin.expenses.download', $expense->id))->assertRedirect(route('login'));

        // Donor cannot access admin download
        $this->actingAs($donor);
        $this->get(route('admin.expenses.download', $expense->id))->assertForbidden();

        // Admin can download
        $this->actingAs($admin);
        $this->get(route('admin.expenses.download', $expense->id))->assertOk();
    }

    public function test_report_exports_for_expenses_and_cashflow(): void
    {
        $this->actingAs($admin = $this->admin());
        $category = KategoriPengeluaran::create(['nama' => 'Pendidikan']);

        Pengeluaran::create([
            'kode' => 'PGL-RPT01',
            'tanggal' => '2026-10-15',
            'kategori_pengeluaran_id' => $category->id,
            'keperluan' => 'Buku pelajaran santri',
            'nominal' => 200000,
            'status' => 'Dibukukan',
            'created_by' => $admin->id,
            'posted_by' => $admin->id,
            'posted_at' => now(),
        ]);

        // Web view reports
        $this->get(route('admin.reports', 'pengeluaran'))->assertOk()->assertSee('Buku pelajaran santri');
        $this->get(route('admin.reports', 'arus-kas'))->assertOk()->assertSee('Saldo Akhir Kas');

        // PDF reports
        $this->get(route('admin.reports', ['kind' => 'pengeluaran', 'format' => 'pdf']))->assertOk();
        $this->get(route('admin.reports', ['kind' => 'arus-kas', 'format' => 'pdf']))->assertOk();

        // Excel reports
        $this->get(route('admin.reports', ['kind' => 'pengeluaran', 'format' => 'xlsx']))->assertOk();
        $this->get(route('admin.reports', ['kind' => 'arus-kas', 'format' => 'xlsx']))->assertOk();
    }
}
