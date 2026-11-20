<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\User;
use App\Notifications\DonorAccountLink;
use App\Services\DonorAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class DonorAccountController extends Controller
{
    public function show(Donatur $donor)
    {
        $donor->load(['user', 'decider']);
        $donations = $donor->donations()->latest()->paginate(15);
        $successCount = $donor->donations()->where('status', 'Berhasil')->count();
        $successTotal = (float) ($donor->donations()->where('status', 'Berhasil')->sum('nominal') ?? 0);
        $audits = DB::table('donor_account_audits')->where('donatur_id', $donor->id)->latest('id')->limit(20)->get();

        return view('admin.donors.account', compact('donor', 'donations', 'audits', 'successCount', 'successTotal'));
    }

    public function invite(Donatur $donor, DonorAccountService $service)
    {
        $status = DB::transaction(function () use ($donor, $service) {
            $donor = Donatur::whereKey($donor->id)->lockForUpdate()->firstOrFail();
            $user = $donor->user_id ? User::whereKey($donor->user_id)->lockForUpdate()->first() : null;
            abort_unless($donor->jenis === 'tetap' && $user?->role === 'donatur' && $user->account_status === 'menunggu', 409);

            if ($donor->status_verifikasi !== 'disetujui') {
                $donor->forceFill([
                    'status_verifikasi' => 'disetujui',
                    'diputuskan_oleh' => auth()->id(),
                    'diputuskan_pada' => now(),
                    'alasan_penolakan' => null,
                ])->save();
            }

            $result = Password::broker('donors')->sendResetLink(['email' => $user->email, 'role' => 'donatur'], function ($user, $token) {
                $user->notify(new DonorAccountLink($token, true));
            });
            if ($result === Password::RESET_LINK_SENT) {
                $service->audit($donor, auth()->user(), 'undangan_aktivasi_dibuat');
            }

            return $result;
        });

        return back()->with($status === Password::RESET_LINK_SENT ? 'success' : 'warning',
            $status === Password::RESET_LINK_SENT
                ? (in_array(config('mail.default'), ['log', 'array']) ? 'Undangan dibuat dalam mode pengujian email; belum dikirim ke kotak masuk.' : 'Undangan telah diproses layanan email.')
                : 'Undangan baru saja dibuat. Tunggu satu menit sebelum mencoba kembali.');
    }

    public function approve(Donatur $donor, DonorAccountService $service)
    {
        // Approval authorizes an activation invitation; only using the emailed
        // token proves ownership of that email address and activates the account.
        return $this->invite($donor, $service);
    }

    public function promote(Request $request, Donatur $donor, DonorAccountService $service)
    {
        $data = $request->validate([
            'email' => 'nullable|email|max:200',
            'verify_history' => 'nullable|boolean',
            'send_invite' => 'nullable|boolean',
            'reason' => 'nullable|string|max:2000',
        ]);

        $donor = $service->promoteToPermanent($donor, $request->user(), $data);

        if (! empty($data['send_invite']) && $donor->user_id) {
            return $this->invite($donor, $service);
        }

        return back()->with('success', 'Donatur berhasil diverifikasi dari riwayat donasi dan ditetapkan sebagai Donatur Tetap.');
    }

    public function rejectPromotion(Request $request, Donatur $donor, DonorAccountService $service)
    {
        $data = $request->validate([
            'reason' => 'required|string|min:5|max:2000',
        ], [
            'reason.required' => 'Alasan penolakan wajib diisi.',
            'reason.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $service->rejectPermanentPromotion($donor, $request->user(), $data['reason']);

        return back()->with('success', 'Status pengajuan donatur tetap telah ditolak.');
    }

    public function access(Request $request, Donatur $donor, DonorAccountService $service)
    {
        $data = $request->validate(['access' => 'required|in:nonaktif,aktif']);
        DB::transaction(function () use ($donor, $data, $service, $request) {
            $donor = Donatur::whereKey($donor->id)->lockForUpdate()->firstOrFail();
            $user = $donor->user_id ? User::whereKey($donor->user_id)->lockForUpdate()->first() : null;
            abort_unless($user?->role === 'donatur', 409);
            if ($data['access'] === 'nonaktif') {
                $service->disable($user);
            } else {
                abort_unless($donor->jenis === 'tetap', 409);
                $user->forceFill([
                    'account_status' => $user->email_verified_at ? 'aktif' : 'menunggu',
                    'auth_version' => $user->auth_version + 1,
                ])->save();
            }
            $service->audit($donor, $request->user(), 'akses_diubah', ['status' => $user->account_status]);
        });

        return back()->with('success', 'Akses akun diperbarui. Akun yang belum memverifikasi email perlu diaktivasi melalui undangan.');
    }

    public function verifyDonation(Request $request, Donatur $donor, Donasi $donation, DonorAccountService $service)
    {
        $data = $request->validate(['confirmed' => 'accepted', 'reason' => 'required|string|min:10|max:2000']);
        DB::transaction(function () use ($donor, $donation, $service, $request, $data) {
            $donor = Donatur::whereKey($donor->id)->lockForUpdate()->firstOrFail();
            $donation = Donasi::whereKey($donation->id)->lockForUpdate()->firstOrFail();
            abort_unless($donor->jenis === 'tetap' && $donor->user_id && $donation->donatur_id === $donor->id, 409);
            abort_if($donation->owner_user_id !== null, 409);
            $donation->forceFill([
                'owner_user_id' => $donor->user_id, 'ownership_verified_by' => $request->user()->id,
                'ownership_verified_at' => now(),
            ])->save();
            $service->audit($donor, $request->user(), 'kepemilikan_donasi_diverifikasi', ['donasi_id' => $donation->id, 'alasan' => $data['reason']]);
        });

        return back()->with('success', 'Kepemilikan transaksi telah diverifikasi dan terhubung ke akun donatur.');
    }
}
