<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Force-log-out any authenticated user whose account has been deactivated since
     * their last request. Without this, a deactivated user keeps full access until
     * their session expires.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && isset($user->is_active) && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Livewire requests can't follow a redirect mid-update; return 401 so the
            // browser-side handler reloads the page (Livewire 3+ does this automatically).
            if ($request->header('X-Livewire')) {
                abort(401, 'Your account has been deactivated.');
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been deactivated. Contact an administrator.']);
        }

        return $next($request);
    }
}
