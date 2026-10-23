<?php

namespace App\Services;

use App\Jobs\SendWhatsapp;
use App\Models\Record;
use App\Models\WhatsappLog;

class WhatsappService
{
    public function notify(Record $record, string $kind): void
    {
        $enabled = match ($kind) {
            'donasi' => in_array($record->status, ['Berhasil', 'Gagal', 'Kedaluwarsa', 'Dibatalkan']),
            'bantuan' => $record->status === 'Dikonfirmasi',
            default => false,
        };
        if (! $enabled) {
            return;
        }
        $history = $record->histories()->latest('id')->first();
        $message = "Panti Asuhan NU An-Nuur 2\n{$record->kode}: {$record->status}.";
        $log = WhatsappLog::firstOrCreate(
            ['dedup_key' => "$kind:{$record->id}:{$history?->id}"],
            [
                'tujuan' => $record->whatsapp,
                'jenis' => "$kind: {$record->status}",
                'pesan' => $message,
                'status' => config('panti.whatsapp_enabled') && config('panti.fonnte_token') ? 'Antrean' : 'Nonaktif',
            ],
        );
        if ($log->wasRecentlyCreated && $log->status === 'Antrean') {
            SendWhatsapp::dispatch($log->id)->afterCommit();
        }
    }
}
