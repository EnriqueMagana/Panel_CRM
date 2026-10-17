<?php

namespace App\Providers;

use App\Models\User;
use App\Services\IamAuditLogger;
use App\Services\IamSettings;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('Super Admin') ? true : null);

        Password::defaults(function (): Password {
            $settings = app(IamSettings::class);
            $rule = Password::min(max(12, $settings->passwordMinLength()));

            if ($settings->checkCompromisedPasswords()) {
                $rule->uncompromised((int) config('iam.password.compromised_threshold', 3));
            }

            return $rule;
        });

        Event::listen(Login::class, function (Login $event): void {
            app(IamAuditLogger::class)->record('auth.login', 'Inicio de sesión exitoso.', $event->user, $event->user);
        });
        Event::listen(Failed::class, function (Failed $event): void {
            app(IamAuditLogger::class)->record(
                'auth.failed',
                'Intento de inicio de sesión fallido.',
                null,
                $event->user,
                ['email' => $event->credentials['email'] ?? null],
            );
        });
        Event::listen(Logout::class, function (Logout $event): void {
            $reason = session('iam.logout_reason', 'manual');
            app(IamAuditLogger::class)->record(
                $reason === 'inactivity' ? 'auth.timeout' : 'auth.logout',
                $reason === 'inactivity' ? 'Sesión cerrada por inactividad.' : 'Cierre de sesión.',
                $event->user,
                $event->user,
            );
        });
    }
}
