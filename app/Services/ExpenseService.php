<?php

namespace App\Services;

use App\Models\Pengeluaran;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExpenseService
{
    public function saveDraft(?int $id, array $data, ?UploadedFile $file, User $actor): Pengeluaran
    {
        return DB::transaction(function () use ($id, $data, $file, $actor) {
            $expense = $id ? Pengeluaran::whereKey($id)->lockForUpdate()->firstOrFail() : new Pengeluaran;
            if ($expense->exists) {
                abort_unless($expense->isDraft(), 409, 'Hanya pengeluaran berstatus draft yang dapat disunting.');
            } else {
                $expense->kode = 'PGL-'.strtoupper((string) Str::ulid());
                $expense->created_by = $actor->id;
                $expense->status = 'Draft';
            }

            if ($file) {
                if ($expense->bukti_path && Storage::exists($expense->bukti_path)) {
                    Storage::delete($expense->bukti_path);
                }
                $expense->bukti_path = $file->store('expenses');
            }

            $from = $expense->exists ? $expense->status : null;
            $expense->fill([
                'tanggal' => $data['tanggal'],
                'kategori_pengeluaran_id' => $data['kategori_pengeluaran_id'],
                'keperluan' => $data['keperluan'],
                'nominal' => $data['nominal'],
                'penerima' => $data['penerima'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'is_public' => ! empty($data['is_public']),
            ]);
            $expense->save();

            app(StatusService::class)->record(
                $expense,
                $from,
                'admin',
                $from ? 'Draft pengeluaran diperbarui' : 'Draft pengeluaran dibuat'
            );

            return $expense;
        });
    }

    public function post(Pengeluaran $expense, User $actor): Pengeluaran
    {
        return DB::transaction(function () use ($expense, $actor) {
            $expense = Pengeluaran::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            abort_unless($expense->isDraft(), 409, 'Hanya pengeluaran draft yang dapat dibukukan.');

            $from = $expense->status;
            $expense->forceFill([
                'status' => 'Dibukukan',
                'posted_by' => $actor->id,
                'posted_at' => now(),
            ])->save();

            app(StatusService::class)->record($expense, $from, 'admin', 'Pengeluaran berhasil dibukukan.');

            return $expense;
        });
    }

    public function cancel(Pengeluaran $expense, string $reason, User $actor): Pengeluaran
    {
        return DB::transaction(function () use ($expense, $reason, $actor) {
            $expense = Pengeluaran::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            abort_unless($expense->isBooked(), 409, 'Hanya pengeluaran yang sudah dibukukan yang dapat dibatalkan.');

            $from = $expense->status;
            $expense->forceFill([
                'status' => 'Dibatalkan',
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ])->save();

            app(StatusService::class)->record($expense, $from, 'admin', 'Pengeluaran dibatalkan: '.$reason);

            return $expense;
        });
    }

    public function deleteDraft(Pengeluaran $expense): void
    {
        DB::transaction(function () use ($expense) {
            $expense = Pengeluaran::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            abort_unless($expense->isDraft(), 409, 'Hanya pengeluaran draft yang dapat dihapus.');

            if ($expense->bukti_path && Storage::exists($expense->bukti_path)) {
                Storage::delete($expense->bukti_path);
            }

            $expense->histories()->delete();
            $expense->delete();
        });
    }
}
