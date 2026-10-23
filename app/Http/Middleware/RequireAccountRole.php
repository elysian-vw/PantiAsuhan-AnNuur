<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireAccountRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();
        abort_unless($user && $user->role === $role, 403);
        $version = $request->session()->get('auth_version');
        if ($user->account_status !== 'aktif' || ($version !== null && (int) $version !== $user->auth_version)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($role === 'pengurus' ? 'login' : 'donor.login')
                ->with('warning', 'Sesi berakhir. Silakan masuk kembali atau hubungi pengurus.');
        }
        // Compatibility with administrator sessions created before this migration.
        if ($version === null) {
            abort_unless($role === 'pengurus', 403);
            $request->session()->put('auth_version', $user->auth_version);
        }
        if ($role === 'donatur') {
            abort_unless($user->canUseDonorAccount(), 403);
        }

        return $next($request);
    }
}
