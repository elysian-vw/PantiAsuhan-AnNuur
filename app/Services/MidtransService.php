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
        if (
            ! PaymentTransaction::whereKey($payment->id)
                ->where('status', 'created')
                ->update(['status' => 'requesting'])
        ) {
            return null;
        }
        try {
            $response = Http::withBasicAuth(config('panti.midtrans_key'), '')
                ->acceptJson()
                ->timeout(20)
                ->post(config('panti.midtrans_base').'/snap/v1/transactions', [
                    'transaction_details' => [
                        'order_id' => $payment->order_id,
                        'gross_amount' => (int) $donation->nominal,
                    ],
                    'customer_details' => array_filter([
                        'first_name' => $donation->nama,
                        'phone' => $donation->whatsapp,
                        'email' => $donation->email,
                    ]),
                    'callbacks' => ['finish' => $returnUrl],
                ]);
            $url = $response->json('redirect_url');
            if (
                ! $response->successful() ||
                ! is_string($url) ||
                parse_url($url, PHP_URL_SCHEME) !== 'https' ||
                parse_url($url, PHP_URL_HOST) !== 'app.sandbox.midtrans.com'
            ) {
                PaymentTransaction::whereKey($payment->id)->where('status', 'requesting')
                    ->update(['status' => 'request_failed']);

                return null;
            }
            $payment->update(['snap_token' => $response->json('token'), 'payment_url' => $url]);

            return $url;
        } catch (\Throwable $e) {
            PaymentTransaction::whereKey($payment->id)->where('status', 'requesting')
                ->update(['status' => 'request_unknown']);

            return null;
        }
    }

    public function sync(Donasi $donation): void
    {
        $payment = $donation->payment;
        if (! $payment || ! $payment->order_id) {
            return;
        }

        $key = config('panti.midtrans_key');
        if (! $key) {
            return;
        }

        try {
            $base = str_contains((string) config('panti.midtrans_base'), 'sandbox')
                ? 'https://api.sandbox.midtrans.com'
                : 'https://api.midtrans.com';

            $response = Http::withBasicAuth($key, '')
                ->acceptJson()
                ->timeout(10)
                ->get("{$base}/v2/{$payment->order_id}/status");

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && isset($data['order_id'], $data['signature_key'], $data['status_code'])) {
                    $this->notification($data);
                }
            }
        } catch (\Throwable) {
            // Ignore connection errors and fall back to local state
        }
    }

    public function cancelDonation(Donasi $donation): Donasi
    {
        // The lock order matches notifications. Claim never-submitted orders locally
        // so checkout's conditional created -> requesting update cannot race us.
        $cancelledLocally = DB::transaction(function () use ($donation) {
            $payment = PaymentTransaction::where('donasi_id', $donation->id)->lockForUpdate()->firstOrFail();
            $current = Donasi::whereKey($donation->id)->lockForUpdate()->firstOrFail();
            abort_unless($current->status === 'Menunggu pembayaran', 409, 'Transaksi tidak dapat dibatalkan pada status saat ini.');
            if ($payment->status !== 'created' || $payment->payment_url || $payment->snap_token) {
                return false;
            }
            $this->recordCancellation($current, $payment);

            return true;
        });

        if ($cancelledLocally) {
            return $donation->fresh(['category', 'payment']);
        }

        // Network requests stay outside database locks. Every final write reads
        // the current status again, including notifications arriving in between.
        $this->sync($donation->fresh('payment'));
        $current = $donation->fresh('payment');
        if ($current->status === 'Dibatalkan') {
            return $current->load('category');
        }
        abort_unless($current->status === 'Menunggu pembayaran', 409, 'Status pembayaran telah berubah. Muat ulang transaksi.');
        $confirmed = $this->cancel($current->payment);
        if (! $confirmed) {
            $this->sync($current->fresh('payment'));
        }

        return DB::transaction(function () use ($donation, $confirmed) {
            $payment = PaymentTransaction::where('donasi_id', $donation->id)->lockForUpdate()->firstOrFail();
            $current = Donasi::whereKey($donation->id)->lockForUpdate()->firstOrFail();
            if ($current->status === 'Dibatalkan') {
                return $current->load(['category', 'payment']);
            }
            abort_unless($current->status === 'Menunggu pembayaran', 409, 'Status pembayaran telah berubah. Muat ulang transaksi.');
            abort_unless($confirmed, 503, 'Pembatalan belum dapat dikonfirmasi. Status pembayaran belum diubah; periksa kembali sebelum mencoba lagi.');
            $this->recordCancellation($current, $payment);

            return $current->load(['category', 'payment']);
        });
    }

    private function recordCancellation(Donasi $donation, PaymentTransaction $payment): void
    {
        $from = $donation->status;
        $donation->update(['status' => 'Dibatalkan']);
        $payment->update(['status' => 'cancel']);
        app(StatusService::class)->record($donation, $from, 'mobile_donatur', 'Dibatalkan oleh donatur');
        app(WhatsappService::class)->notify($donation, 'donasi');
    }

    public function cancel(PaymentTransaction $payment): bool
    {
        $key = config('panti.midtrans_key');
        if (! $key || ! $payment->order_id) {
            return false;
        }

        try {
            $base = str_contains((string) config('panti.midtrans_base'), 'sandbox')
                ? 'https://api.sandbox.midtrans.com'
                : 'https://api.midtrans.com';

            $response = Http::withBasicAuth($key, '')
                ->acceptJson()
                ->timeout(10)
                ->post("{$base}/v2/{$payment->order_id}/cancel");

            return $response->successful()
                && (string) $response->json('status_code') === '200'
                && $response->json('transaction_status') === 'cancel'
                && $response->json('order_id') === $payment->order_id;
        } catch (\Throwable) {
            return false;
        }
    }

    public function notification(array $data): void
    {
        $key = config('panti.midtrans_key');
        abort_unless($key, 503, 'Integrasi pembayaran belum dikonfigurasi.');
        $signature = hash('sha512', $data['order_id'].$data['status_code'].$data['gross_amount'].$key);
        abort_unless(hash_equals($signature, $data['signature_key']), 403, 'Signature tidak valid.');

        // Dashboard connectivity tests use a synthetic order, not a local donation.
        if (str_starts_with($data['order_id'], 'payment_notif_test_')) {
            return;
        }

        DB::transaction(function () use ($data) {
            $payment = PaymentTransaction::where('order_id', $data['order_id'])->lockForUpdate()->firstOrFail();
            $donation = Donasi::whereKey($payment->donasi_id)->lockForUpdate()->firstOrFail();
            abort_unless(
                bccomp((string) $donation->nominal, (string) $data['gross_amount'], 2) === 0,
                422,
                'Nominal tidak cocok.',
            );
            $next = match ($data['transaction_status']) {
                'settlement' => 'Berhasil',
                'capture' => ($data['fraud_status'] ?? '') === 'accept' ? 'Berhasil' : null,
                'cancel' => 'Dibatalkan',
                'deny', 'failure' => 'Gagal',
                'expire' => 'Kedaluwarsa',
                'pending' => 'Menunggu pembayaran',
                default => null,
            };
            // Success is terminal. Late pending callbacks must not reopen failed/expired payments.
            if (
                ! $next ||
                $donation->status === 'Berhasil' ||
                ($donation->status === 'Dibatalkan' && $next !== 'Berhasil') ||
                ($donation->status !== 'Menunggu pembayaran' && $next === 'Menunggu pembayaran')
            ) {
                return;
            }
            $payment->update([
                'provider_id' => $data['transaction_id'] ?? $payment->provider_id,
                'status' => $data['transaction_status'],
                'metode' => $data['payment_type'] ?? $payment->metode,
            ]);
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
