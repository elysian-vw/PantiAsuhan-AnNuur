<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SubmissionController;
use App\Models\Pengeluaran;
use App\Services\CashflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request, string $kind = 'donasi')
    {
        abort_unless(in_array($kind, ['donasi', 'bantuan', 'pengeluaran', 'arus-kas']), 404);

        $data = $request->validate([
            'awal' => 'nullable|date',
            'akhir' => 'nullable|date|after_or_equal:awal',
            'bulan' => 'nullable|integer|min:1|max:12',
            'tahun' => 'nullable|integer|min:2000|max:2200',
            'status' => 'nullable|string|max:40',
            'format' => 'nullable|in:pdf,xlsx',
        ]);

        if ($kind === 'arus-kas') {
            return $this->cashflowReport($data);
        }

        $query = match ($kind) {
            'donasi', 'bantuan' => SubmissionController::model($kind)::query(),
            'pengeluaran' => Pengeluaran::query()->with('category'),
        };

        $column = match ($kind) {
            'donasi' => ($data['status'] ?? '') === 'Berhasil' ? 'dibayar_pada' : 'created_at',
            'bantuan' => ($data['status'] ?? '') === 'Diterima' ? 'tanggal_diterima' : 'created_at',
            'pengeluaran' => 'tanggal',
        };

        if (! empty($data['awal'])) {
            $query->whereDate($column, '>=', $data['awal']);
        }
        if (! empty($data['akhir'])) {
            $query->whereDate($column, '<=', $data['akhir']);
        }
        if (! empty($data['bulan'])) {
            $query->whereMonth($column, $data['bulan']);
        }
        if (! empty($data['tahun'])) {
            $query->whereYear($column, $data['tahun']);
        }
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if ($kind === 'bantuan') {
            $query->with('items');
        }

        $records = $query->orderBy($column)->get();

        $headers = match ($kind) {
            'donasi' => ['Kode', 'Nama', 'Tanggal acuan', 'Status', 'Bruto', 'Biaya', 'Bersih', 'Sumber biaya'],
            'bantuan' => ['Kode', 'Nama', 'Tanggal acuan', 'Status', 'Rincian barang'],
            'pengeluaran' => ['Kode', 'Tanggal', 'Kategori', 'Keperluan', 'Penerima', 'Status', 'Nominal', 'Publikasi Bukti'],
        };

        $rows = $records
            ->map(
                fn ($r) => match ($kind) {
                    'donasi' => [
                        $r->kode,
                        $r->nama,
                        (string) $r->$column,
                        $r->status,
                        $r->nominal,
                        $r->recordedFee() ?? 'Belum tersedia',
                        $r->netAmount() ?? 'Belum dihitung',
                        $r->sumber_biaya ?? ($r->isManual() ? 'Manual ('.strtoupper($r->metode).')' : '—'),
                    ],
                    'bantuan' => [
                        $r->kode,
                        $r->nama,
                        (string) $r->$column,
                        $r->status,
                        $r->items->map(fn ($i) => "$i->nama: $i->jumlah $i->satuan")->implode('; '),
                    ],
                    'pengeluaran' => [
                        $r->kode,
                        (string) $r->tanggal->format('Y-m-d'),
                        $r->category?->nama ?? '—',
                        $r->keperluan,
                        $r->penerima ?? '—',
                        $r->status,
                        $r->nominal,
                        $r->is_public ? 'Tampil' : 'Privat',
                    ],
                },
            )
            ->all();

        $net = $kind === 'donasi'
            ? $records
                ->where('status', 'Berhasil')
                ->sum(fn ($r) => (float) ($r->netAmount() ?? 0))
            : null;

        $unknown = $kind === 'donasi'
            ? $records->where('status', 'Berhasil')->where('sumber', 'online')->whereNull('biaya')->count()
            : 0;

        $summary = ['Jumlah catatan' => $records->count()];
        if ($kind === 'donasi') {
            $summary['Donasi berhasil'] = $records->where('status', 'Berhasil')->count();
            $summary['Total bruto berhasil (Rp)'] = $records->where('status', 'Berhasil')->sum('nominal');
            $summary['Total bersih tercatat (Rp)'] = $net;
            $summary['Berhasil, biaya belum tersedia'] = $unknown;
        } elseif ($kind === 'bantuan') {
            $received = $records->where('status', 'Diterima');
            $summary['Pengajuan diterima'] = $received->count();
            foreach (
                $received->flatMap->items->groupBy(
                    fn ($item) => mb_strtolower(trim($item->nama)).'|'.mb_strtolower(trim($item->satuan)),
                ) as $group
            ) {
                $summary[$group->first()->nama.' ('.$group->first()->satuan.')'] = $group->sum('jumlah');
            }
        } elseif ($kind === 'pengeluaran') {
            $booked = $records->where('status', 'Dibukukan');
            $summary['Total nominal dibukukan (Rp)'] = $booked->sum('nominal');
            $summary['Jumlah dibukukan'] = $booked->count();
            $summary['Total nominal draft (Rp)'] = $records->where('status', 'Draft')->sum('nominal');
            $summary['Total nominal dibatalkan (Rp)'] = $records->where('status', 'Dibatalkan')->sum('nominal');
        }

        $context = compact('kind', 'headers', 'rows', 'net', 'unknown', 'column', 'data', 'summary');

        if (($data['format'] ?? '') === 'pdf') {
            return Pdf::loadView('admin.reports.pdf', $context)
                ->setPaper('a4', 'landscape')
                ->download("laporan-$kind.pdf");
        }
        if (($data['format'] ?? '') === 'xlsx') {
            return Excel::download(new ReportExport($rows, $headers, $kind, $summary, $column, $data), "laporan-$kind.xlsx");
        }

        return view('admin.reports.index', $context);
    }

    private function cashflowReport(array $data)
    {
        $awal = $data['awal'] ?? null;
        $akhir = $data['akhir'] ?? null;

        if (! empty($data['bulan']) && ! empty($data['tahun'])) {
            $c = Carbon::create($data['tahun'], $data['bulan'], 1);
            $awal = $c->copy()->startOfMonth()->format('Y-m-d');
            $akhir = $c->copy()->endOfMonth()->format('Y-m-d');
        } elseif (! empty($data['tahun'])) {
            $c = Carbon::create($data['tahun'], 1, 1);
            $awal = $c->copy()->startOfYear()->format('Y-m-d');
            $akhir = $c->copy()->endOfYear()->format('Y-m-d');
        }

        $cf = app(CashflowService::class)->calculate($awal, $akhir);

        $headers = ['Pos Aliran Dana', 'Nominal (Rp)', 'Keterangan'];
        $rows = [
            ['[1] Saldo Awal Periode', (float) $cf['saldo_awal'], 'Saldo kas pada awal periode pembukuan'],
            ['[2] Penerimaan: Donasi Online Bersih (Midtrans)', (float) $cf['penerimaan']['online_total'], $cf['penerimaan']['online_count'].' transaksi berhasil'],
            ['[2] Penerimaan: Donasi Manual (Tunai)', (float) $cf['penerimaan']['manual_cash_total'], $cf['penerimaan']['manual_cash_count'].' transaksi'],
            ['[2] Penerimaan: Donasi Manual (Transfer)', (float) $cf['penerimaan']['manual_transfer_total'], $cf['penerimaan']['manual_transfer_count'].' transaksi'],
            ['[2] Total Penerimaan Periode', (float) $cf['penerimaan']['total'], 'Penerimaan bersih setelah biaya gateway yang tercatat'],
        ];

        foreach ($cf['pengeluaran']['by_category'] as $cat) {
            if ((float) $cat['total'] > 0) {
                $rows[] = ['[3] Pengeluaran: '.$cat['nama'], (float) $cat['total'], $cat['count'].' transaksi dibukukan'];
            }
        }

        $rows[] = ['[3] Total Pengeluaran Periode', (float) $cf['pengeluaran']['total'], $cf['pengeluaran']['count'].' pengeluaran dibukukan'];
        $rows[] = ['[4] Saldo Akhir Kas Periode', (float) $cf['saldo_akhir'], 'Saldo awal + Penerimaan - Pengeluaran'];

        $summary = [
            'Saldo Awal Periode (Rp)' => (float) $cf['saldo_awal'],
            'Total Penerimaan Periode (Rp)' => (float) $cf['penerimaan']['total'],
            'Total Pengeluaran Periode (Rp)' => (float) $cf['pengeluaran']['total'],
            'Saldo Akhir Kas (Rp)' => (float) $cf['saldo_akhir'],
            'Online belum masuk bersih: biaya belum tersedia (termasuk sebelum periode)' => $cf['unknown_fee_count'],
        ];

        $net = null;
        $unknown = $cf['unknown_fee_count'];
        $column = 'periode';
        $kind = 'arus-kas';

        $context = compact('kind', 'headers', 'rows', 'net', 'unknown', 'column', 'data', 'summary');

        if (($data['format'] ?? '') === 'pdf') {
            return Pdf::loadView('admin.reports.pdf', $context)
                ->setPaper('a4', 'landscape')
                ->download('laporan-arus-kas.pdf');
        }
        if (($data['format'] ?? '') === 'xlsx') {
            return Excel::download(new ReportExport($rows, $headers, $kind, $summary, $column, $data), 'laporan-arus-kas.xlsx');
        }

        return view('admin.reports.index', $context);
    }
}
