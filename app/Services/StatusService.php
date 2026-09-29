<?php

namespace App\Services;

use App\Models\Record;

class StatusService
{
    public function record(Record $record, ?string $from, string $source, ?string $note = null): void
    {
        $record->histories()->create(['dari' => $from, 'ke' => $record->status,
            'user_id' => $source === 'admin' ? auth()->id() : null, 'sumber' => $source, 'catatan' => $note]);
    }

    public function allowed(string $kind, string $status): array
    {
        return match ($kind) {
            'bantuan' => match ($status) {
                'Diajukan' => ['Dikonfirmasi', 'Ditolak'], 'Dikonfirmasi' => ['Diterima', 'Ditolak'], default => [],
            },
            'kunjungan' => match ($status) {
                'Menunggu' => ['Disetujui', 'Dijadwalkan ulang', 'Ditolak'],
                'Disetujui' => ['Hadir', 'Dijadwalkan ulang', 'Ditolak'],
                'Dijadwalkan ulang' => ['Disetujui', 'Dijadwalkan ulang', 'Ditolak'],
                'Hadir' => ['Selesai'], default => [],
            },
            default => [],
        };
    }
}
