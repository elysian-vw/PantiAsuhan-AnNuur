<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengeluaran;
use App\Models\Pengeluaran;
use App\Services\CashflowService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TransparencyController extends Controller
{
    public function summary(Request $request, CashflowService $cashflowService): JsonResponse
    {
        $month = $request->integer('month', (int) now()->format('m'));
        $year = $request->integer('year', (int) now()->format('Y'));

        if ($month < 1 || $month > 12) {
            $month = (int) now()->format('m');
        }

        if ($year < 2020 || $year > 2099) {
            $year = (int) now()->format('Y');
        }

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $summary = $cashflowService->calculate($startDate, $endDate);
        $monthLabel = Carbon::createFromDate($year, $month, 1)->locale('id')->isoFormat('MMMM YYYY');

        return response()->json([
            'periode' => [
                'bulan' => $month,
                'tahun' => $year,
                'label' => $monthLabel,
                'tanggal_mulai' => $startDate,
                'tanggal_selesai' => $endDate,
            ],
            'saldo_awal' => $summary['saldo_awal'],
            'saldo_awal_formatted' => 'Rp '.number_format((float) $summary['saldo_awal'], 0, ',', '.'),
            'total_penerimaan' => $summary['penerimaan']['total'],
            'total_penerimaan_formatted' => 'Rp '.number_format((float) $summary['penerimaan']['total'], 0, ',', '.'),
            'total_pengeluaran' => $summary['pengeluaran']['total'],
            'total_pengeluaran_formatted' => 'Rp '.number_format((float) $summary['pengeluaran']['total'], 0, ',', '.'),
            'saldo_akhir' => $summary['saldo_akhir'],
            'is_provisional' => $summary['is_provisional'],
            'unknown_fee_count' => $summary['unknown_fee_count'],
            'calculation_note' => 'Donasi manual dihitung penuh. Donasi online tanpa biaya tercatat belum masuk penerimaan bersih dan saldo, termasuk sebelum periode ini.',
            'saldo_akhir_formatted' => 'Rp '.number_format((float) $summary['saldo_akhir'], 0, ',', '.'),
            'opening_balance' => $summary['opening_balance'] ? [
                'tanggal_mulai' => $summary['opening_balance']->tanggal_mulai->toDateString(),
                'nominal' => (string) $summary['opening_balance']->nominal,
                'keterangan' => $summary['opening_balance']->keterangan,
            ] : null,
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = KategoriPengeluaran::select('id', 'nama', 'deskripsi')
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    public function expenses(Request $request): JsonResponse
    {
        $query = Pengeluaran::with('category')
            ->where('status', 'Dibukukan');

        if ($month = $request->integer('month')) {
            if ($month >= 1 && $month <= 12) {
                $query->whereMonth('tanggal', $month);
            }
        }

        if ($year = $request->integer('year')) {
            if ($year >= 2020 && $year <= 2099) {
                $query->whereYear('tanggal', $year);
            }
        }

        if ($categoryId = $request->integer('kategori_id')) {
            $query->where('kategori_pengeluaran_id', $categoryId);
        }

        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $paginator = $query->orderByDesc('tanggal')->orderByDesc('id')->paginate($perPage);

        $paginator->getCollection()->transform(fn ($exp) => $this->formatExpense($exp));

        return response()->json($paginator);
    }

    public function expenseDetail(int $id): JsonResponse
    {
        $expense = Pengeluaran::with('category')
            ->where('status', 'Dibukukan')
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatExpense($expense),
        ]);
    }

    public function downloadPublicProof(int $id)
    {
        $expense = Pengeluaran::where('status', 'Dibukukan')->findOrFail($id);

        if (! $expense->is_public || ! $expense->bukti_path) {
            abort(403, 'Bukti fisik pengeluaran ini bersifat privat atau tidak dipublikasikan.');
        }

        if (! Storage::disk('local')->exists($expense->bukti_path)) {
            abort(404, 'Berkas bukti fisik tidak ditemukan.');
        }

        return Storage::disk('local')->response($expense->bukti_path);
    }

    private function formatExpense(Pengeluaran $exp): array
    {
        $hasPublicProof = (bool) ($exp->is_public && $exp->bukti_path);

        return [
            'id' => $exp->id,
            'kode' => $exp->kode,
            'tanggal' => $exp->tanggal->format('Y-m-d'),
            'kategori' => $exp->category ? [
                'id' => $exp->category->id,
                'nama' => $exp->category->nama,
            ] : null,
            'keperluan' => $exp->keperluan,
            'nominal' => (string) $exp->nominal,
            'nominal_formatted' => 'Rp '.number_format((float) $exp->nominal, 0, ',', '.'),
            'penerima' => $exp->penerima,
            'keterangan' => $exp->keterangan,
            'has_public_proof' => $hasPublicProof,
            'bukti_url' => $hasPublicProof ? route('api.v1.transparency.expense.proof', $exp->id) : null,
        ];
    }
}
