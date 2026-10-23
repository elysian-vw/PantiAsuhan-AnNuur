<?php

namespace App\Services;

use App\Models\Donatur;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DonorAccountService
{
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
