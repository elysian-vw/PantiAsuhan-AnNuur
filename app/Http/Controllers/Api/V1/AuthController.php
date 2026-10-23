<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Donatur;
use App\Models\User;
use App\Notifications\DonorAccountLink;
use App\Services\DonorAccountService;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, DonorAccountService $service): JsonResponse
    {
        $normalizedPhone = Phone::normalize($request->input('whatsapp'));
        $request->merge(['whatsapp' => $normalizedPhone, 'email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'whatsapp' => ['required', 'regex:/^628[0-9]{7,11}$/', Rule::unique('donatur', 'whatsapp')],
            'email' => ['required', 'email', 'max:200', Rule::unique('users', 'email'), Rule::unique('donatur', 'email')],
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Format nomor WhatsApp tidak valid (contoh: 081234567890).',
            'whatsapp.unique' => 'Nomor WhatsApp sudah terdaftar.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $email = Str::lower(trim($data['email']));

        $donor = DB::transaction(function () use ($data, $email, $service) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $email,
                'password' => $data['password'],
                'role' => 'donatur',
                'account_status' => 'menunggu',
                'email_verified_at' => null,
            ]);

            $donor = Donatur::create([
                'nama' => $data['name'],
                'whatsapp' => $data['whatsapp'],
                'email' => $email,
                'jenis' => 'tetap',
                'user_id' => $user->id,
            ]);

            $service->audit($donor, $user, 'pendaftaran_mobile_mandiri', [
                'nama' => $donor->nama,
                'email' => $donor->email,
                'whatsapp' => $donor->whatsapp,
                'ip' => request()->ip(),
            ]);

            return $donor;
        });

        return response()->json([
            'message' => 'Pendaftaran berhasil diajukan. Akun Anda sedang menunggu verifikasi dan persetujuan dari pengurus panti asuhan.',
            'data' => [
                'nama' => $donor->nama,
                'email' => $donor->email,
                'status' => 'menunggu',
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:200',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $email = Str::lower(trim($data['email']));
        $key = 'api-donor-login:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Terlalu banyak percobaan login. Coba lagi dalam '.$seconds.' detik.',
            ], 429);
        }

        $user = User::where('email', $email)->first();

        // Check if user exists and password is valid but account is pending/inactive
        if ($user && $user->role === 'donatur' && Hash::check($data['password'], $user->password)) {
            if ($user->account_status === 'menunggu') {
                throw ValidationException::withMessages([
                    'email' => ['Akun Anda sedang dalam proses verifikasi dan aktivasi oleh pengurus panti asuhan.'],
                ]);
            }
            if ($user->account_status === 'nonaktif') {
                throw ValidationException::withMessages([
                    'email' => ['Akun Anda telah dinonaktifkan. Silakan hubungi pengurus panti asuhan.'],
                ]);
            }
        }

        if (! $user?->canUseDonorAccount() || ! Auth::validate([
            'email' => $email,
            'password' => $data['password'],
            'role' => 'donatur',
            'account_status' => 'aktif',
        ])) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => ['Email, password, atau akses akun tidak sesuai.'],
            ]);
        }

        RateLimiter::clear($key);

        $deviceName = $data['device_name'] ?? 'mobile_app';
        $token = $user->createToken($deviceName)->plainTextToken;
        $donor = $user->donor;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'account_status' => $user->account_status,
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

    public function me(Request $request): JsonResponse
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
                'created_at' => $donor->created_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesi mobile berhasil diakhiri.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:200',
        ]);

        $email = Str::lower(trim($data['email']));
        $key = 'api-donor-forgot:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Terlalu banyak permintaan reset password. Coba lagi dalam '.$seconds.' detik.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        DB::transaction(function () use ($email) {
            $user = User::where('email', $email)->lockForUpdate()->first();
            if ($user?->canUseDonorAccount()) {
                try {
                    Password::broker('donors')->sendResetLink(['email' => $email, 'role' => 'donatur'], function ($user, $token) {
                        $user->notify(new DonorAccountLink($token));
                    });
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
        });

        return response()->json([
            'message' => 'Jika email terdaftar pada akun donatur aktif, tautan pemulihan akan diproses. Periksa kotak masuk dan folder spam.',
        ]);
    }
}
