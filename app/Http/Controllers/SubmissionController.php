<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmissionRequest;
use App\Models\Bantuan;
use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\Kunjungan;
use App\Services\MidtransService;
use App\Services\StatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubmissionController extends Controller
{
    public static function model(string $kind): string
    {
        return match ($kind) {
            'donasi' => Donasi::class,
            'bantuan' => Bantuan::class,
            'kunjungan' => Kunjungan::class,
            default => abort(404),
        };
    }

    public function store(SubmissionRequest $request, string $kind)
    {
        $model = self::model($kind);
        $data = $request->validated();
        $token = Str::random(64);
        DB::transaction(function () use ($data, $model, $kind, $token) {
            $common = [
                'kode' => strtoupper(substr($kind, 0, 3)).'-'.strtoupper((string) Str::ulid()),
                'token_hash' => hash('sha256', $token),
                'nama' => $data['nama'],
                'whatsapp' => $data['whatsapp'],
            ];
            if ($kind !== 'kunjungan') {
                // Public submissions never overwrite a donor's master identity.
                $donor = Donatur::firstOrCreate(
                    ['whatsapp' => $data['whatsapp']],
                    ['nama' => $data['nama'], 'email' => $data['email'] ?? null],
                );
                $common['donatur_id'] = $donor->id;
            }
            $values = match ($kind) {
                'donasi' => [
                    'kategori_donasi_id' => $data['kategori_donasi_id'],
                    'nominal' => $data['nominal'],
                    'email' => $data['email'] ?? null,
                    'status' => 'Menunggu pembayaran',
                ],
                'bantuan' => ['catatan' => $data['catatan'] ?? null, 'status' => 'Diajukan'],
                'kunjungan' => collect($data)
                    ->only([
                        'jenis_kunjungan_id',
                        'instansi',
                        'peserta',
                        'tanggal',
                        'jam',
                        'jam_selesai',
                        'tujuan',
                        'catatan',
                    ])
                    ->all() + ['status' => 'Menunggu'],
            };
            $record = $model::create($common + $values);
            if ($kind === 'bantuan') {
                $record->items()->createMany($data['items']);
            }
            if ($kind === 'donasi') {
                $record->payment()->create(['order_id' => $record->kode]);
            }
            app(StatusService::class)->record($record, null, 'pengunjung');
        });

        return redirect()
            ->route('tracking', [$kind, $token])
            ->with(
                'success',
                'Data berhasil disimpan. Simpan tautan pribadi halaman ini untuk melihat perkembangannya.',
            );
    }

    public function tracking(string $kind, string $token)
    {
        $record = self::model($kind)::where('token_hash', hash('sha256', $token))->firstOrFail();

        return view('public.tracking', compact('record', 'kind', 'token'));
    }

    public function pay(string $token, MidtransService $service)
    {
        $donation = Donasi::where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_unless($donation->status === 'Menunggu pembayaran', 409, 'Transaksi tidak dapat dibayar kembali.');
        $url = $service->checkout($donation, route('tracking', ['donasi', $token]));

        return $url
            ? redirect()->away($url)
            : back()->with(
                'warning',
                'Pembayaran belum dapat dibuka. Data donasi tetap tersimpan; hubungi pengurus bila kendala berlanjut.',
            );
    }

    public function webhook(Request $request, MidtransService $service)
    {
        $data = $request->validate([
            'order_id' => 'required|string|max:100',
            'status_code' => 'required|string|max:10',
            'gross_amount' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'signature_key' => 'required|string|size:128',
            'transaction_status' => 'required|string|max:40',
            'transaction_id' => 'nullable|string|max:100',
            'payment_type' => 'nullable|string|max:60',
            'fraud_status' => 'nullable|string|max:40',
        ]);
        $service->notification($data);

        return response()->json(['ok' => true]);
    }
}
