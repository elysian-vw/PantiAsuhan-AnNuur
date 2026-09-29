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
            'donasi' => in_array($record->status, ['Berhasil', 'Gagal', 'Kedaluwarsa']),
            'bantuan' => $record->status === 'Dikonfirmasi',
            'kunjungan' => in_array($record->status, ['Disetujui', 'Ditolak', 'Dijadwalkan ulang']),
            default => false,
        };
        if (! $enabled) {
            return;
        }
        $history = $record->histories()->latest('id')->first();
        $message = "Panti Asuhan NU An-Nuur 2\n{$record->kode}: {$record->status}.";
        if ($kind === 'kunjungan') {
            $message .=
                "\nJadwal: {$record->tanggal}, ".
                substr($record->jam, 0, 5).
                '–'.
                substr($record->jam_selesai, 0, 5).
                ' WIB.';
            if ($record->status === 'Dijadwalkan ulang') {
                $message .= "\nMohon balas pesan ini kepada pengurus untuk menyetujui jadwal baru.";
            }
        }
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
