<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SubmissionController;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request, string $kind = 'donasi')
    {
        $data = $request->validate([
            'awal' => 'nullable|date',
            'akhir' => 'nullable|date|after_or_equal:awal',
            'bulan' => 'nullable|integer|min:1|max:12',
            'tahun' => 'nullable|integer|min:2000|max:2200',
            'status' => 'nullable|string|max:40',
            'format' => 'nullable|in:pdf,xlsx',
        ]);
        $query = SubmissionController::model($kind)::query();
        $column = match ($kind) {
            'donasi' => ($data['status'] ?? '') === 'Berhasil' ? 'dibayar_pada' : 'created_at',
            'bantuan' => ($data['status'] ?? '') === 'Diterima' ? 'tanggal_diterima' : 'created_at',
            'kunjungan' => 'tanggal',
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
            'kunjungan' => ['Kode', 'Nama', 'Tanggal', 'Jam', 'Peserta', 'Status'],
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
                        $r->biaya ?? 'Belum tersedia',
                        $r->status === 'Berhasil' && $r->biaya !== null
                            ? bcsub($r->nominal, $r->biaya, 2)
                            : 'Belum dihitung',
                        $r->sumber_biaya ?? '—',
                    ],
                    'bantuan' => [
                        $r->kode,
                        $r->nama,
                        (string) $r->$column,
                        $r->status,
                        $r->items->map(fn ($i) => "$i->nama: $i->jumlah $i->satuan")->implode('; '),
                    ],
                    'kunjungan' => [
                        $r->kode,
                        $r->nama,
                        $r->tanggal,
                        substr($r->jam, 0, 5).'–'.substr($r->jam_selesai, 0, 5),
                        $r->peserta,
                        $r->status,
                    ],
                },
            )
            ->all();
        $net =
            $kind === 'donasi'
                ? $records
                    ->where('status', 'Berhasil')
                    ->whereNotNull('biaya')
                    ->sum(fn ($r) => (float) bcsub($r->nominal, $r->biaya, 2))
                : null;
        $unknown = $kind === 'donasi' ? $records->where('status', 'Berhasil')->whereNull('biaya')->count() : 0;
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
        } else {
            $summary['Peserta kunjungan hadir / selesai'] = $records
                ->whereIn('status', ['Hadir', 'Selesai'])
                ->sum('peserta');
        }
        $context = compact('kind', 'headers', 'rows', 'net', 'unknown', 'column', 'data', 'summary');
        if (($data['format'] ?? '') === 'pdf') {
            return Pdf::loadView('admin.reports.pdf', $context)
                ->setPaper('a4', 'landscape')
                ->download("laporan-$kind.pdf");
        }
        if (($data['format'] ?? '') === 'xlsx') {
            $exportRows = $rows;
            $exportRows[] = [];
            $exportRows[] = ['Ringkasan'];
            foreach ($summary as $label => $value) {
                $exportRows[] = [$label, $value];
            }
            $exportRows[] = ['Tanggal acuan', $column];
            $exportRows[] = ['Filter', json_encode($data, JSON_UNESCAPED_UNICODE)];

            return Excel::download(new ReportExport($exportRows, $headers), "laporan-$kind.xlsx");
        }

        return view('admin.reports.index', $context);
    }
}
