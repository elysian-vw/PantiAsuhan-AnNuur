<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CashflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashflowController extends Controller
{
    public function index(Request $request, CashflowService $service)
    {
        $data = $request->validate([
            'awal' => 'nullable|date',
            'akhir' => 'nullable|date|after_or_equal:awal',
            'bulan' => 'nullable|integer|min:1|max:12',
            'tahun' => 'nullable|integer|min:2000|max:2200',
        ]);

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

        $summary = $service->calculate($awal, $akhir);
        $opening = $service->getOpeningBalance();

        return view('admin.cashflow.index', compact('summary', 'data', 'opening', 'awal', 'akhir'));
    }

    public function saveOpeningBalance(Request $request, CashflowService $service)
    {
        $data = $request->validate([
            'tanggal_mulai' => 'required|date',
            'nominal' => 'required|numeric|min:0|max:999999999999',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $service->saveOpeningBalance($data, $request->user());

        return back()->with('success', 'Saldo awal pembukuan berhasil disimpan dan disahkan.');
    }
}
