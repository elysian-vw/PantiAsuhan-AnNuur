<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriDonasi;
use App\Services\ManualDonationService;
use Illuminate\Http\Request;

class ManualDonationController extends Controller
{
    public function create()
    {
        $categories = KategoriDonasi::orderBy('nama')->get();

        return view('admin.donations.manual-form', compact('categories'));
    }

    public function store(Request $request, ManualDonationService $service)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'whatsapp' => ['required', 'string', 'max:30'],
            'email' => 'nullable|email|max:200',
            'nominal' => 'required|numeric|min:5000|max:999999999999',
            'kategori_donasi_id' => 'required|exists:kategori_donasi,id',
            'metode' => 'required|in:tunai,transfer',
            'tanggal_diterima' => 'required|date|before_or_equal:today',
            'referensi_manual' => 'nullable|string|max:150',
            'catatan' => 'nullable|string|max:2000',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $donation = $service->record($data, $request->file('bukti'), $request->user());

        return redirect()->route('admin.transaction.show', ['donasi', $donation->id])
            ->with('success', "Penerimaan donasi manual ({$donation->kode}) sebesar Rp " . number_format($donation->nominal, 0, ',', '.') . " berhasil dicatat.");
    }
}
