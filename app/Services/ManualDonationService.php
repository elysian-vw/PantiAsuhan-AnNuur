<?php

namespace App\Services;

use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManualDonationService
{
    public function record(array $data, ?UploadedFile $file, User $actor): Donasi
    {
        return DB::transaction(function () use ($data, $file, $actor) {
            $whatsapp = Phone::normalize($data['whatsapp']);
            $donor = Donatur::firstOrCreate(
                ['whatsapp' => $whatsapp],
                ['nama' => $data['nama'], 'email' => $data['email'] ?? null],
            );

            $proofPath = $file ? $file->store('manual-donations', 'local') : null;
            $token = Str::random(64);

            $donation = Donasi::create([
                'kode' => 'DON-'.strtoupper((string) Str::ulid()),
                'token_hash' => hash('sha256', $token),
                'donatur_id' => $donor->id,
                'kategori_donasi_id' => $data['kategori_donasi_id'],
                'nama' => $data['nama'],
                'whatsapp' => $whatsapp,
                'email' => $data['email'] ?? null,
                'nominal' => $data['nominal'],
                'status' => 'Berhasil',
                'sumber' => 'manual',
                'biaya' => 0,
                'sumber_biaya' => 'Tanpa biaya gateway (manual)',
                'metode' => $data['metode'],
                'referensi_manual' => $data['referensi_manual'] ?? null,
                'bukti_transfer' => $proofPath,
                'pencatat_user_id' => $actor->id,
                'dibayar_pada' => $data['tanggal_diterima'] ?? now(),
            ]);

            app(StatusService::class)->record(
                $donation,
                null,
                'admin',
                'Penerimaan donasi manual ('.strtoupper($data['metode']).')'.(! empty($data['catatan']) ? ': '.$data['catatan'] : '')
            );

            return $donation;
        });
    }
}
