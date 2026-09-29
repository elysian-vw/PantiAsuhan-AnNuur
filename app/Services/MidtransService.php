<?php

namespace App\Services;

use App\Models\Donasi;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MidtransService
{
    public function checkout(Donasi $donation, string $returnUrl): ?string
    {
        if (! config('panti.midtrans_key')) {
            return null;
        }
        $payment = $donation->payment;
        if ($payment->payment_url) {
            return $payment->payment_url;
        }
        // An order is created once. Unknown network outcomes must be reconciled, not recreated.
        if ($payment->status !== 'created') {
            return null;
        }
        if (! PaymentTransaction::whereKey($payment->id)->where('status', 'created')->update(['status' => 'requesting'])) {
            return null;
        }
        try {
            $response = Http::withBasicAuth(config('panti.midtrans_key'), '')->acceptJson()->timeout(20)
                ->post(config('panti.midtrans_base').'/snap/v1/transactions', [
                    'transaction_details' => ['order_id' => $payment->order_id, 'gross_amount' => (int) $donation->nominal],
                    'customer_details' => array_filter(['first_name' => $donation->nama, 'phone' => $donation->whatsapp, 'email' => $donation->email]),
                    'callbacks' => ['finish' => $returnUrl],
                ]);
            $url = $response->json('redirect_url');
            if (! $response->successful() || ! is_string($url) || parse_url($url, PHP_URL_SCHEME) !== 'https'
                || parse_url($url, PHP_URL_HOST) !== 'app.sandbox.midtrans.com') {
                $payment->update(['status' => 'request_failed']);

                return null;
            }
            $payment->update(['snap_token' => $response->json('token'), 'payment_url' => $url]);

            return $url;
        } catch (\Throwable $e) {
            $payment->update(['status' => 'request_unknown']);

            return null;
        }
    }

    public function notification(array $data): void
    {
        $key = config('panti.midtrans_key');
        abort_unless($key, 503, 'Integrasi pembayaran belum dikonfigurasi.');
        $signature = hash('sha512', $data['order_id'].$data['status_code'].$data['gross_amount'].$key);
        abort_unless(hash_equals($signature, $data['signature_key']), 403, 'Signature tidak valid.');

        DB::transaction(function () use ($data) {
            $payment = PaymentTransaction::where('order_id', $data['order_id'])->lockForUpdate()->firstOrFail();
            $donation = Donasi::whereKey($payment->donasi_id)->lockForUpdate()->firstOrFail();
            abort_unless(bccomp((string) $donation->nominal, (string) $data['gross_amount'], 2) === 0, 422, 'Nominal tidak cocok.');
            $next = match ($data['transaction_status']) {
                'settlement' => 'Berhasil',
                'capture' => ($data['fraud_status'] ?? '') === 'accept' ? 'Berhasil' : null,
                'deny', 'cancel', 'failure' => 'Gagal',
                'expire' => 'Kedaluwarsa',
                'pending' => 'Menunggu pembayaran',
                default => null,
            };
            // Success is terminal. Late pending callbacks must not reopen failed/expired payments.
            if (! $next || $donation->status === 'Berhasil' || ($donation->status !== 'Menunggu pembayaran' && $next === 'Menunggu pembayaran')) {
                return;
            }
            $payment->update(['provider_id' => $data['transaction_id'] ?? $payment->provider_id,
                'status' => $data['transaction_status'], 'metode' => $data['payment_type'] ?? $payment->metode]);
            if ($next === $donation->status) {
                return;
            }
            $from = $donation->status;
            $values = ['status' => $next];
            if ($next === 'Berhasil') {
                $values['dibayar_pada'] = now();
                $fee = config('panti.simulated_fee');
                if (is_numeric($fee) && $fee >= 0 && $fee <= $donation->nominal) {
                    $values['biaya'] = $fee;
                    $values['sumber_biaya'] = 'Simulasi sandbox';
                }
            }
            $donation->update($values);
            app(StatusService::class)->record($donation, $from, 'midtrans');
            app(WhatsappService::class)->notify($donation, 'donasi');
        });
    }
}
