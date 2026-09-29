<?php

namespace App\Jobs;

use App\Models\WhatsappLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendWhatsapp implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public int $logId) {}

    public function handle(): void
    {
        $log = WhatsappLog::findOrFail($this->logId);
        if ($log->status !== 'Antrean') {
            return;
        }
        if (! config('panti.whatsapp_enabled') || ! config('panti.fonnte_token')) {
            $log->update(['status' => 'Nonaktif']);

            return;
        }
        $log->update(['status' => 'Diproses']);
        try {
            $response = Http::withHeaders(['Authorization' => config('panti.fonnte_token')])->asForm()->timeout(20)
                ->post('https://api.fonnte.com/send', ['target' => $log->tujuan, 'message' => $log->pesan, 'countryCode' => '62']);
            $accepted = $response->successful() && $response->json('status') === true;
            $log->update(['status' => $accepted ? 'Diterima provider' : 'Gagal',
                'provider_id' => $accepted ? substr(json_encode($response->json('id')), 0, 250) : null,
                'dikirim_pada' => $accepted ? now() : null,
                'keterangan' => $accepted ? 'Permintaan diterima Fonnte; bukan bukti pesan dibaca.' : 'Provider menolak permintaan. Periksa koneksi perangkat dan konfigurasi Fonnte.']);
        } catch (\Throwable $e) {
            $log->update(['status' => 'Tidak diketahui', 'keterangan' => 'Koneksi terputus. Periksa riwayat provider sebelum mengirim ulang.']);
        }
    }
}
