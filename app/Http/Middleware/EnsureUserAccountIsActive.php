<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => $user->status === 'invited'
                    ? 'Acepta la invitación y configura tu contraseña para acceder.'
                    : 'Tu cuenta está suspendida. Contacta con un administrador.',
            ]);
        }

        return $next($request);
    }
}
