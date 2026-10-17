<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user?->must_change_password) {
            return $next($request);
        }

        $allowedRoute = $request->routeIs('profile', 'logout', 'media.public', 'password.confirm', 'password.confirm.store');
        $livewireFromProfile = $request->routeIs('livewire.update')
            && str_contains((string) $request->headers->get('referer'), '/profile');

        if ($allowedRoute || $livewireFromProfile) {
            return $next($request);
        }

        return redirect(route('profile').'#password-heading')
            ->with('profile-status', 'Debes establecer una contraseña nueva antes de continuar.');
    }
}
