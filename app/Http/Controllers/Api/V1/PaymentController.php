<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Donasi;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function pay(Request $request, int $id, MidtransService $service): JsonResponse
    {
        $donation = Donasi::with('payment')->findOrFail($id);

        if ($donation->owner_user_id !== $request->user()->id || ! $donation->ownership_verified_at) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke transaksi ini.',
            ], 403);
        }

        if ($donation->status !== 'Menunggu pembayaran') {
            return response()->json([
                'message' => 'Transaksi tidak dapat dibayar karena status saat ini: '.$donation->status,
            ], 409);
        }

        $payment = $donation->payment;
        if (! $payment->payment_url || ! $payment->snap_token) {
            $returnUrl = route('mobile.payment.return');
            $service->checkout($donation, $returnUrl);
            $payment->refresh();
        }

        $snapToken = $payment->getRawOriginal('snap_token') ?? $payment->snap_token;
        abort_unless($payment->payment_url, 503, 'Pembayaran belum tersedia. Periksa kembali transaksi ini; jangan membuat donasi baru untuk mencoba ulang.');

        return response()->json([
            'order_id' => $payment->order_id,
            'snap_token' => $snapToken,
            'payment_url' => $payment->payment_url,
            'return_url' => route('mobile.payment.return'),
            'status' => $payment->status,
        ]);
    }

    public function status(Request $request, int $id, MidtransService $service): JsonResponse
    {
        $donation = Donasi::with('payment')->findOrFail($id);

        if ($donation->owner_user_id !== $request->user()->id || ! $donation->ownership_verified_at) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke transaksi ini.',
            ], 403);
        }

        if ($donation->status === 'Menunggu pembayaran' && $donation->payment) {
            $service->sync($donation);
            $donation->refresh();
            $donation->load('payment');
        }

        $payment = $donation->payment;

        return response()->json([
            'donation_id' => $donation->id,
            'kode' => $donation->kode,
            'status' => $donation->status,
            'dibayar_pada' => $donation->dibayar_pada?->toIso8601String(),
            'payment' => $payment ? [
                'order_id' => $payment->order_id,
                'status' => $payment->status,
                'metode' => $payment->metode,
            ] : null,
        ]);
    }
}
