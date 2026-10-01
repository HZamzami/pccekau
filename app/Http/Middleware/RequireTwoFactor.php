<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// The 2FA plugin only guards Filament panel routes. Patient PDFs and document
// downloads live outside the panel, so users who turned on two-factor login
// get the same code check here; otherwise a password alone would open them.
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

        return $next($request);
    }
}
