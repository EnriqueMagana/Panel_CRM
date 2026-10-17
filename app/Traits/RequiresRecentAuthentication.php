<?php

namespace App\Traits;

trait RequiresRecentAuthentication
{
    protected function requireRecentAuthentication(): bool
    {
        $confirmedAt = (int) session('auth.password_confirmed_at', 0);
        $timeout = max(1, (int) config('iam.reauthentication_timeout_minutes', 15)) * 60;

        if ($confirmedAt > 0 && (time() - $confirmedAt) < $timeout) {
            return true;
        }

        session()->put('url.intended', url()->previous());
        $this->redirectRoute('password.confirm', navigate: true);

        return false;
    }
}
