<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Donatur;
use App\Models\User;
use App\Services\DonorAccountService;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{
    public function changePassword(Request $request, DonorAccountService $accounts): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:12|max:200|confirmed',
        ]);

        DB::transaction(function () use ($request, $data, $accounts) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->canUseDonorAccount(), 403);
            if (! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Kata sandi saat ini tidak sesuai.']);
            }
            $user->forceFill([
                'password' => $data['password'],
                'remember_token' => Str::random(60),
                'auth_version' => $user->auth_version + 1,
            ])->save();
            Password::broker('donors')->deleteToken($user);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            // Keep this device signed in; revoke every other mobile session.
            $currentToken = $request->user()->currentAccessToken();
            $user->tokens()->when($currentToken instanceof PersonalAccessToken,
                fn ($query) => $query->whereKeyNot($currentToken->id))->delete();
            $accounts->audit($user->donor, $user, 'password_diubah');
        });

        return response()->json(['message' => 'Kata sandi berhasil diperbarui. Sesi perangkat lain telah diakhiri.']);
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $donor = $user->donor;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'account_status' => $user->account_status,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
            'donor' => $donor ? [
                'id' => $donor->id,
                'nama' => $donor->nama,
                'whatsapp' => $donor->whatsapp,
                'email' => $donor->email,
                'jenis' => $donor->jenis,
                'registered_at' => $donor->created_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => 'required|string|max:100',
            'whatsapp' => 'required|string|max:25',
        ]);

        $normalizedWhatsapp = Phone::normalize($data['whatsapp']);

        if (! preg_match('/^628[0-9]{7,11}$/', $normalizedWhatsapp)) {
            return response()->json([
                'message' => 'Gunakan nomor WhatsApp Indonesia yang valid, misalnya 081234567890.',
                'errors' => [
                    'whatsapp' => ['Gunakan nomor WhatsApp Indonesia yang valid, misalnya 081234567890.'],
                ],
            ], 422);
        }

        $user = $request->user();
        $donor = $user->donor;

        if ($donor) {
            $duplicate = Donatur::where('whatsapp', $normalizedWhatsapp)
                ->whereKeyNot($donor->id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'message' => 'Nomor WhatsApp sudah digunakan oleh profil donatur lain.',
                    'errors' => [
                        'whatsapp' => ['Nomor WhatsApp sudah digunakan oleh profil donatur lain.'],
                    ],
                ], 422);
            }

            $donor->update([
                'nama' => $data['nama'],
                'whatsapp' => $normalizedWhatsapp,
            ]);
        }

        $user->update(['name' => $data['nama']]);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'account_status' => $user->account_status,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
            'donor' => $donor ? [
                'id' => $donor->id,
                'nama' => $donor->nama,
                'whatsapp' => $donor->whatsapp,
                'email' => $donor->email,
                'jenis' => $donor->jenis,
            ] : null,
        ]);
    }
}
