<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Donasi;
use App\Models\User;
use App\Services\MidtransService;
use App\Services\StatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DonationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Donasi::with(['category', 'payment'])
            ->where('owner_user_id', $user->id)
            ->whereNotNull('ownership_verified_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $paginator = $query->latest('id')->paginate($perPage);

        $paginator->getCollection()->transform(fn ($donation) => $this->formatDonation($donation));

        return response()->json($paginator);
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $base = Donasi::where('owner_user_id', $user->id)
            ->whereNotNull('ownership_verified_at');

        $successQuery = (clone $base)->where('status', 'Berhasil');
        $pendingQuery = (clone $base)->where('status', 'Menunggu pembayaran');

        $totalSuccess = (string) ($successQuery->sum('nominal') ?? '0.00');

        return response()->json([
            'total_nominal_berhasil' => $totalSuccess,
            'total_nominal_formatted' => 'Rp '.number_format((float) $totalSuccess, 0, ',', '.'),
            'count_berhasil' => $successQuery->count(),
            'count_menunggu' => $pendingQuery->count(),
            'count_total' => (clone $base)->count(),
        ]);
    }

    public function store(Request $request, MidtransService $midtransService): JsonResponse
    {
        $data = $request->validate([
            'kategori_donasi_id' => 'required|exists:kategori_donasi,id',
            'nominal' => 'required|integer|min:5000|max:999999999',
            'catatan' => 'nullable|string|max:500',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        $user = $request->user();
        $donor = $user->donor;

        if (! $donor) {
            return response()->json([
                'message' => 'Profil donatur tidak ditemukan untuk akun ini.',
            ], 422);
        }

        $randomToken = Str::random(64);
        $created = false;
        $record = DB::transaction(function () use ($data, $user, $donor, $randomToken, &$created) {
            // Serialize submissions per owner; the unique index also protects retries.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $key = $data['idempotency_key'] ?? null;
            $hash = hash('sha256', json_encode([(int) $data['kategori_donasi_id'], (int) $data['nominal'], $data['catatan'] ?? null], JSON_THROW_ON_ERROR));
            if ($key !== null) {
                $existing = Donasi::where('owner_user_id', $user->id)->where('idempotency_key', $key)->first();
                if ($existing) {
                    abort_unless(hash_equals($existing->request_hash, $hash), 409, 'Permintaan ini sudah digunakan untuk donasi berbeda.');

                    return $existing;
                }
            } else {
                // Compatibility for older clients without a request key.
                $existing = Donasi::where('owner_user_id', $user->id)->whereNull('idempotency_key')
                    ->where('kategori_donasi_id', $data['kategori_donasi_id'])->where('nominal', $data['nominal'])
                    ->where('status', 'Menunggu pembayaran')->where('created_at', '>=', now()->subSeconds(15))->first();
                if ($existing) {
                    return $existing;
                }
            }
            $donation = Donasi::create([
                'idempotency_key' => $key,
                'request_hash' => $key !== null ? $hash : null,
                'kode' => 'DON-'.strtoupper((string) Str::ulid()),
                'token_hash' => hash('sha256', $randomToken),
                'donatur_id' => $donor->id,
                'kategori_donasi_id' => $data['kategori_donasi_id'],
                'nama' => $user->name,
                'whatsapp' => $donor->whatsapp,
                'email' => $user->email,
                'nominal' => $data['nominal'],
                'status' => 'Menunggu pembayaran',
                'owner_user_id' => $user->id,
                'ownership_verified_by' => $user->id,
                'ownership_verified_at' => now(),
                'sumber' => 'online',
                'catatan' => $data['catatan'] ?? null,
            ]);

            $donation->payment()->create([
                'order_id' => $donation->kode,
            ]);

            app(StatusService::class)->record($donation, null, 'mobile_donatur');
            $created = true;

            return $donation;
        });

        // Request checkout via MidtransService
        if ($created) {
            $midtransService->checkout($record, route('mobile.payment.return'));
        }
        $record->load(['category', 'payment']);

        return response()->json([
            'message' => $created ? 'Transaksi donasi berhasil dibuat.' : 'Transaksi serupa sedang diproses. Silakan lanjutkan transaksi yang sudah ada.',
            'data' => $this->formatDonation($record),
        ], $created ? 201 : 200);
    }

    public function cancel(Request $request, int $id, MidtransService $midtransService): JsonResponse
    {
        $donation = Donasi::with('payment')->findOrFail($id);

        if ($donation->owner_user_id !== $request->user()->id || ! $donation->ownership_verified_at) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke transaksi ini.',
            ], 403);
        }

        $donation = $midtransService->cancelDonation($donation);

        return response()->json([
            'message' => 'Pembayaran donasi berhasil dibatalkan.',
            'data' => $this->formatDonation($donation),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $donation = Donasi::with(['category', 'payment'])->findOrFail($id);

        if ($donation->owner_user_id !== $request->user()->id || ! $donation->ownership_verified_at) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke transaksi ini.',
            ], 403);
        }

        return response()->json([
            'data' => $this->formatDonation($donation),
        ]);
    }

    private function formatDonation(Donasi $donation): array
    {
        $payment = $donation->payment;
        $fee = $donation->recordedFee();
        $snapToken = $payment ? $payment->getRawOriginal('snap_token') ?? $payment->snap_token : null;

        return [
            'id' => $donation->id,
            'kode' => $donation->kode,
            'nominal' => number_format((float) $donation->nominal, 2, '.', ''),
            'nominal_formatted' => 'Rp '.number_format((float) $donation->nominal, 0, ',', '.'),
            'biaya' => $fee !== null ? number_format((float) $fee, 2, '.', '') : null,
            'biaya_formatted' => $fee !== null ? 'Rp '.number_format((float) $fee, 0, ',', '.') : null,
            'sumber_biaya' => $donation->sumber_biaya,
            'status' => $donation->status,
            'dibayar_pada' => $donation->dibayar_pada?->toIso8601String(),
            'created_at' => $donation->created_at?->toIso8601String(),
            'catatan' => $donation->catatan,
            'kategori' => $donation->category ? [
                'id' => $donation->category->id,
                'nama' => $donation->category->nama,
            ] : null,
            'payment' => $payment ? [
                'order_id' => $payment->order_id,
                'status' => $payment->status,
                'metode' => $payment->metode,
                'snap_token' => $snapToken,
                'payment_url' => $payment->payment_url,
                'return_url' => route('mobile.payment.return'),
            ] : null,
        ];
    }
}
