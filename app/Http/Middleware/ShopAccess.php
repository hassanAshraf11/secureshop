<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopAccess
{
    public function handle(Request $request, Closure $next, ?string $role = null)
    {
        $user = $request->user();
        if (! $user?->active || ! $user->email_verified_at ||
            (int) $request->session()->get('mfa_user') !== $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors(['login' => 'Please sign in to continue.']);
        }
        abort_if($role === 'admin' && $user->role !== 'admin', 403);

        return $next($request);
    }
}
