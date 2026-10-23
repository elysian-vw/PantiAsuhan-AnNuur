<?php

namespace App\Services;

use App\Models\Donasi;
use App\Models\KategoriPengeluaran;
use App\Models\Pengeluaran;
use App\Models\SaldoPembukaan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CashflowService
{
    public function getOpeningBalance(): ?SaldoPembukaan
    {
        return SaldoPembukaan::with('ratifier')->latest('tanggal_mulai')->first();
    }

    public function saveOpeningBalance(array $data, User $actor): SaldoPembukaan
    {
        return DB::transaction(function () use ($data, $actor) {
            return SaldoPembukaan::updateOrCreate(
                ['tanggal_mulai' => $data['tanggal_mulai']],
                [
                    'nominal' => $data['nominal'],
                    'keterangan' => $data['keterangan'] ?? null,
                    'disahkan_oleh' => $actor->id,
                ]
            );
        });
    }

    public function calculate(?string $startDate = null, ?string $endDate = null): array
    {
        $boundary = $startDate ?? $endDate;
        $opening = SaldoPembukaan::with('ratifier')
            ->when($boundary, fn ($q) => $q->whereDate('tanggal_mulai', '<=', $boundary))
            ->latest('tanggal_mulai')->first();
        // If bookkeeping begins inside this period, start at that first opening.
        if (! $opening && $startDate && $endDate) {
            $opening = SaldoPembukaan::with('ratifier')
                ->whereBetween('tanggal_mulai', [$startDate, $endDate])
                ->oldest('tanggal_mulai')->first();
        }
        $bookkeepingStart = $opening ? $opening->tanggal_mulai->format('Y-m-d') : null;

        $effectiveStart = $startDate;
        if ($bookkeepingStart && (! $effectiveStart || $effectiveStart < $bookkeepingStart)) {
            $effectiveStart = $bookkeepingStart;
        }

        $saldoAwal = $opening ? (string) $opening->nominal : '0.00';
        $priorUnknownFees = 0;
        if ($startDate && (! $bookkeepingStart || $startDate > $bookkeepingStart)) {
            $priorQuery = Donasi::query()
                ->where('status', 'Berhasil')
                ->when($bookkeepingStart, fn ($q) => $q->whereDate('dibayar_pada', '>=', $bookkeepingStart))
                ->whereDate('dibayar_pada', '<', $startDate);
            $priorReceipts = (string) (clone $priorQuery)->sum(DB::raw(Donasi::NET_SQL));
            $priorUnknownFees = (clone $priorQuery)->online()->whereNull('biaya')->count();

            $priorExpenses = (string) Pengeluaran::query()
                ->where('status', 'Dibukukan')
                ->when($bookkeepingStart, fn ($q) => $q->whereDate('tanggal', '>=', $bookkeepingStart))
                ->whereDate('tanggal', '<', $startDate)
                ->sum('nominal');

            $saldoAwal = bcadd(bcsub($saldoAwal, $priorExpenses, 2), $priorReceipts, 2);
        }

        $receiptsQuery = Donasi::query()->where('status', 'Berhasil');
        if ($effectiveStart) {
            $receiptsQuery->whereDate('dibayar_pada', '>=', $effectiveStart);
        }
        if ($endDate) {
            $receiptsQuery->whereDate('dibayar_pada', '<=', $endDate);
        }
        $receipts = $receiptsQuery->get();

        $onlineReceipts = $receipts->where('sumber', 'online');
        $manualCashReceipts = $receipts->where('sumber', 'manual')->where('metode', 'tunai');
        $manualTransferReceipts = $receipts->where('sumber', 'manual')->where('metode', 'transfer');

        $totalPenerimaan = number_format((float) $receipts->sum('nominal'), 2, '.', '');
        $totalBiayaGateway = number_format((float) $onlineReceipts->whereNotNull('biaya')->sum('biaya'), 2, '.', '');
        $totalBruto = $totalPenerimaan;
        $totalPenerimaan = $receipts->reduce(fn ($total, $receipt) => bcadd($total, $receipt->netAmount() ?? '0.00', 2), '0.00');
        $unknownFeeCount = $onlineReceipts->whereNull('biaya')->count();

        $expensesQuery = Pengeluaran::query()->where('status', 'Dibukukan')->with('category');
        if ($effectiveStart) {
            $expensesQuery->whereDate('tanggal', '>=', $effectiveStart);
        }
        if ($endDate) {
            $expensesQuery->whereDate('tanggal', '<=', $endDate);
        }
        $expenses = $expensesQuery->get();

        $totalPengeluaran = number_format((float) $expenses->sum('nominal'), 2, '.', '');

        $categories = KategoriPengeluaran::orderBy('nama')->get();
        $expensesByCategory = $categories->map(function ($cat) use ($expenses) {
            $items = $expenses->where('kategori_pengeluaran_id', $cat->id);

            return [
                'id' => $cat->id,
                'nama' => $cat->nama,
                'count' => $items->count(),
                'total' => number_format((float) $items->sum('nominal'), 2, '.', ''),
            ];
        });

        $saldoAkhir = bcadd(bcsub($saldoAwal, $totalPengeluaran, 2), $totalPenerimaan, 2);

        return [
            'opening_balance' => $opening,
            'bookkeeping_start' => $bookkeepingStart,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'effective_start' => $effectiveStart,
            'saldo_awal' => number_format((float) $saldoAwal, 2, '.', ''),
            'unknown_fee_count' => $priorUnknownFees + $unknownFeeCount,
            'is_provisional' => ($priorUnknownFees + $unknownFeeCount) > 0,
            'penerimaan' => [
                'total' => $totalPenerimaan,
                'bruto_total' => $totalBruto,
                'online_count' => $onlineReceipts->count(),
                'online_total' => $onlineReceipts->reduce(fn ($total, $receipt) => bcadd($total, $receipt->netAmount() ?? '0.00', 2), '0.00'),
                'manual_cash_count' => $manualCashReceipts->count(),
                'manual_cash_total' => number_format((float) $manualCashReceipts->sum('nominal'), 2, '.', ''),
                'manual_transfer_count' => $manualTransferReceipts->count(),
                'manual_transfer_total' => number_format((float) $manualTransferReceipts->sum('nominal'), 2, '.', ''),
                'biaya_gateway_total' => $totalBiayaGateway,
                'unknown_fee_count' => $unknownFeeCount,
            ],
            'pengeluaran' => [
                'total' => $totalPengeluaran,
                'count' => $expenses->count(),
                'by_category' => $expensesByCategory,
            ],
            'saldo_akhir' => number_format((float) $saldoAkhir, 2, '.', ''),
        ];
    }
}
