<?php

namespace App\Services;

use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DonorAccountService
{
    public function queueForPermanentVerificationIfEligible(Donatur $donor, ?Donasi $donation = null): void
    {
        $locked = Donatur::whereKey($donor->id)->lockForUpdate()->first();
        if (! $locked || $locked->jenis !== 'biasa' || $locked->status_verifikasi !== 'none') {
            return;
        }

        $locked->forceFill([
            'status_verifikasi' => 'pending',
            'diajukan_pada' => now(),
        ])->save();

        $this->audit($locked, null, 'masuk_antrean_donatur_tetap', array_filter([
            'donasi_id' => $donation?->id,
            'kode_donasi' => $donation?->kode,
        ]));
    }

    public function promoteToPermanent(Donatur $donor, User $actor, array $data = []): Donatur
    {
        return DB::transaction(function () use ($donor, $actor, $data) {
            $donor = Donatur::whereKey($donor->id)->lockForUpdate()->firstOrFail();
            $email = isset($data['email']) && trim((string) $data['email']) !== ''
                ? Str::lower(trim((string) $data['email']))
                : ($donor->email ? Str::lower(trim($donor->email)) : null);

            $user = $donor->user_id ? User::whereKey($donor->user_id)->lockForUpdate()->first() : null;

            if ($email) {
                $emailTaken = User::where('email', $email)
                    ->when($user, fn ($q) => $q->whereKeyNot($user->id))
                    ->exists()
                    || Donatur::where('email', $email)
                        ->whereKeyNot($donor->id)
                        ->exists();

                if ($emailTaken) {
                    throw ValidationException::withMessages([
                        'email' => 'Gunakan email unik untuk akun donatur tetap.',
                    ]);
                }

                if (! $user) {
                    $user = User::create([
                        'name' => $donor->nama,
                        'email' => $email,
                        'password' => Str::random(64),
                        'role' => 'donatur',
                        'account_status' => 'menunggu',
                    ]);
                    $donor->user_id = $user->id;
                } else {
                    $user->fill(['name' => $donor->nama, 'email' => $email])->save();
                }
            }

            $donor->forceFill([
                'email' => $email ?? $donor->email,
                'jenis' => 'tetap',
                'status_verifikasi' => 'disetujui',
                'diputuskan_oleh' => $actor->id,
                'diputuskan_pada' => now(),
                'alasan_penolakan' => null,
            ])->save();

            $verifiedCount = 0;
            if (! empty($data['verify_history']) && $donor->user_id) {
                $unownedDonations = Donasi::where('donatur_id', $donor->id)
                    ->whereNull('owner_user_id')
                    ->lockForUpdate()
                    ->get();

                foreach ($unownedDonations as $donation) {
                    $donation->forceFill([
                        'owner_user_id' => $donor->user_id,
                        'ownership_verified_by' => $actor->id,
                        'ownership_verified_at' => now(),
                    ])->save();
                    $verifiedCount++;
                }
            }

            $this->audit($donor, $actor, 'verifikasi_donatur_tetap_disetujui', array_filter([
                'email' => $donor->email,
                'user_id' => $donor->user_id,
                'riwayat_ditautkan' => $verifiedCount,
                'catatan' => $data['reason'] ?? null,
            ], fn ($v) => $v !== null));

            return $donor;
        });
    }

    public function rejectPermanentPromotion(Donatur $donor, User $actor, string $reason): Donatur
    {
        return DB::transaction(function () use ($donor, $actor, $reason) {
            $donor = Donatur::whereKey($donor->id)->lockForUpdate()->firstOrFail();

            $donor->forceFill([
                'status_verifikasi' => 'ditolak',
                'diputuskan_oleh' => $actor->id,
                'diputuskan_pada' => now(),
                'alasan_penolakan' => $reason,
            ])->save();

            $this->audit($donor, $actor, 'verifikasi_donatur_tetap_ditolak', [
                'alasan' => $reason,
            ]);

            return $donor;
        });
    }

    public function save(?int $id, array $data, User $actor): Donatur
    {
        return DB::transaction(function () use ($id, $data, $actor) {
            $donor = $id ? Donatur::whereKey($id)->lockForUpdate()->firstOrFail() : new Donatur;
            $before = $donor->only(['nama', 'email', 'whatsapp', 'jenis', 'user_id']);
            $user = $donor->user_id ? User::whereKey($donor->user_id)->lockForUpdate()->firstOrFail() : null;
            if ($user) {
                abort_unless($user->role === 'donatur', 409);
            }
            $email = isset($data['email']) ? Str::lower(trim($data['email'])) : null;
            if ($data['jenis'] === 'tetap') {
                if (! $email || User::where('email', $email)->when($user, fn ($q) => $q->whereKeyNot($user->id))->exists()) {
                    throw ValidationException::withMessages(['email' => 'Gunakan email unik untuk akun donatur tetap.']);
                }
                if (! $user) {
                    $user = User::create([
                        'name' => $data['nama'], 'email' => $email,
                        'password' => Str::random(64), 'role' => 'donatur', 'account_status' => 'menunggu',
                    ]);
                } else {
                    $changedEmail = $user->email !== $email;
                    if ($changedEmail || $donor->jenis !== 'tetap') {
                        Password::broker('donors')->deleteToken($user);
                        $user->forceFill([
                            'email_verified_at' => null, 'account_status' => 'menunggu',
                            'remember_token' => Str::random(60), 'auth_version' => $user->auth_version + 1,
                        ]);
                        $this->clearSessions($user);
                    }
                    $user->fill(['name' => $data['nama'], 'email' => $email])->save();
                }
                $donor->user_id = $user->id;
                if ($donor->status_verifikasi !== 'disetujui') {
                    $donor->status_verifikasi = 'disetujui';
                    $donor->diputuskan_oleh = $actor->id;
                    $donor->diputuskan_pada = now();
                    $donor->alasan_penolakan = null;
                }
            } elseif ($user) {
                $this->disable($user);
            }
            $donor->fill(['nama' => $data['nama'], 'whatsapp' => $data['whatsapp'], 'email' => $email, 'jenis' => $data['jenis']])->save();
            $this->audit($donor, $actor, 'data_donatur_disimpan', ['sebelum' => $before, 'sesudah' => $donor->only(['nama', 'email', 'whatsapp', 'jenis', 'user_id'])]);

            return $donor;
        });
    }

    public function disable(User $user): void
    {
        Password::broker('donors')->deleteToken($user);
        $user->forceFill(['account_status' => 'nonaktif', 'auth_version' => $user->auth_version + 1, 'remember_token' => Str::random(60)])->save();
        $this->clearSessions($user);
    }

    public function clearSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->tokens()->delete();
    }

    public function audit(Donatur $donor, ?User $actor, string $action, array $details = []): void
    {
        DB::table('donor_account_audits')->insert([
            'donatur_id' => $donor->id, 'actor_user_id' => $actor?->id, 'action' => $action,
            'details' => json_encode($details, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
