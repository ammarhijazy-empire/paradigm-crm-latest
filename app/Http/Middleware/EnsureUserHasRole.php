<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Signed-in users without any role (new sign-ups) or deactivated users get no access. */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && ! $user->active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            return redirect()->route('login')->withErrors(['email' => __('auth.deactivated')]);
        }
        if ($user && $user->roles()->doesntExist()) {
            if ($request->expectsJson()) {
                abort(403, __('auth.no_role'));
            }
            return response()->view('auth.waiting', [], 403);
        }
        return $next($request);
    }
}
