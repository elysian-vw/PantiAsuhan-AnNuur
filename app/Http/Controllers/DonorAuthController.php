<?php

namespace App\Http\Controllers;

use App\Models\Donasi;
use App\Models\User;
use App\Notifications\DonorAccountLink;
use App\Services\DonorAccountService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DonorAuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.donor-login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:200', 'password' => 'required|string']);
        $data['email'] = Str::lower(trim($data['email']));
        $key = 'donor-login:'.$data['email'].'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak percobaan. Coba lagi dalam satu menit.']);
        }
        $user = User::where('email', $data['email'])->first();
        if (! $user?->canUseDonorAccount() || ! Auth::validate($data + ['role' => 'donatur', 'account_status' => 'aktif'])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email, password, atau akses akun tidak sesuai.']);
        }
        Auth::login($user);
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('auth_version', $user->auth_version);
        $request->session()->forget('url.intended');

        return redirect()->route('donor.account');
    }

    public function forgotForm()
    {
        return view('auth.donor-forgot');
    }

    public function sendReset(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:200']);
        $email = Str::lower(trim($data['email']));
        DB::transaction(function () use ($email) {
            $user = User::where('email', $email)->lockForUpdate()->first();
            if ($user?->canUseDonorAccount()) {
                try {
                    Password::broker('donors')->sendResetLink(['email' => $email, 'role' => 'donatur'], function ($user, $token) {
                        $user->notify(new DonorAccountLink($token));
                    });
                } catch (\Throwable $exception) {
                    // Keep the same public response even when mail delivery fails.
                    report($exception);
                }
            }
        });

        return back()->with('success', 'Jika email terdaftar pada akun donatur aktif, tautan pemulihan akan diproses. Periksa kotak masuk dan folder spam.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.donor-reset', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function reset(Request $request, DonorAccountService $service)
    {
        $data = $request->validate([
            'email' => 'required|email|max:200', 'token' => 'required|string|max:200',
            'password' => 'required|string|min:12|max:200|confirmed',
        ]);
        $data['email'] = Str::lower(trim($data['email']));
        $status = DB::transaction(function () use ($data, $service) {
            $user = User::where('email', $data['email'])->lockForUpdate()->first();
            if (! $user || $user->role !== 'donatur' || ! in_array($user->account_status, ['aktif', 'menunggu'])
                || ! $user->donor()->where('jenis', 'tetap')->exists()) {
                return Password::INVALID_TOKEN;
            }

            return Password::broker('donors')->reset($data + ['role' => 'donatur'], function (User $user, string $password) use ($service) {
                $activation = $user->account_status === 'menunggu';
                $user->forceFill([
                    'password' => $password, 'email_verified_at' => $user->email_verified_at ?? now(),
                    'account_status' => 'aktif', 'remember_token' => Str::random(60),
                    'auth_version' => $user->auth_version + 1,
                ])->save();
                $service->clearSessions($user);
                $service->audit($user->donor, null, $activation ? 'akun_diaktivasi' : 'password_dipulihkan');
                event(new PasswordReset($user));
            });
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Tautan tidak valid atau sudah kedaluwarsa. Minta tautan baru atau hubungi pengurus.']);
        }
        if (Auth::id() === User::where('email', $data['email'])->value('id')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('donor.login')->with('success', 'Password berhasil disimpan dan email terverifikasi. Silakan masuk kembali.');
    }

    public function account(Request $request)
    {
        $query = Donasi::where('owner_user_id', $request->user()->id)->whereNotNull('ownership_verified_at');

        return view('auth.donor-account', [
            'donations' => (clone $query)->latest()->paginate(15),
            'total' => (clone $query)->where('status', 'Berhasil')->sum('nominal'),
        ]);
    }
}
