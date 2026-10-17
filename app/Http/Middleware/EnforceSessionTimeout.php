<?php

namespace App\Http\Middleware;

use App\Services\IamSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionTimeout
{
    private const AUTHENTICATED_AT = 'iam.authenticated_at';

    private const LAST_ACTIVITY_AT = 'iam.last_activity_at';

    public function __construct(private readonly IamSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $now = now()->timestamp;
        $authenticatedAt = (int) $request->session()->get(self::AUTHENTICATED_AT, $now);
        $lastActivityAt = (int) $request->session()->get(self::LAST_ACTIVITY_AT, $now);
        $idleTimeout = max(1, $this->settings->idleTimeoutMinutes()) * 60;
        $absoluteTimeout = max(1, $this->settings->absoluteTimeoutMinutes()) * 60;

        if (($now - $lastActivityAt) >= $idleTimeout || ($now - $authenticatedAt) >= $absoluteTimeout) {
            $request->session()->put('iam.logout_reason', 'inactivity');
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with(
                'status',
                'Tu sesión terminó por seguridad. Inicia sesión nuevamente.',
            );
        }

        $request->session()->put([
            self::AUTHENTICATED_AT => $authenticatedAt,
            self::LAST_ACTIVITY_AT => $now,
        ]);

        return $next($request);
    }
}
