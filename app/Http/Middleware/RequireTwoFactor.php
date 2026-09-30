<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// The 2FA plugin only guards Filament panel routes. Patient PDFs and document
// downloads live outside the panel, so they need the same two checks here or
// a password alone would be enough to fetch them.
class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->hasEnabledTwoFactorAuthentication() && ! $user->isTwoFactorChallengePassed()) {
            return redirect()->guest(route('filament.admin.two-factor.challenge'));
        }

        if (config('auth.two_factor_enforced') && ! $user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('filament.admin.two-factor.setup');
        }

        return $next($request);
    }
}
