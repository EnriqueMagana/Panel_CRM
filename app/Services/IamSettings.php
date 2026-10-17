<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class IamSettings
{
    public const IDLE_TIMEOUT = 'iam.idle_timeout_minutes';

    public const ABSOLUTE_TIMEOUT = 'iam.absolute_timeout_minutes';

    public const WARNING_SECONDS = 'iam.session_warning_seconds';

    public const PASSWORD_MIN_LENGTH = 'iam.password_min_length';

    public const CHECK_COMPROMISED = 'iam.password_check_compromised';

    public function idleTimeoutMinutes(): int
    {
        return $this->integer(self::IDLE_TIMEOUT, (int) config('iam.session.idle_timeout_minutes', 30));
    }

    public function absoluteTimeoutMinutes(): int
    {
        return $this->integer(self::ABSOLUTE_TIMEOUT, (int) config('iam.session.absolute_timeout_minutes', 480));
    }

    public function warningSeconds(): int
    {
        return $this->integer(self::WARNING_SECONDS, (int) config('iam.session.warning_seconds', 120));
    }

    public function passwordMinLength(): int
    {
        return $this->integer(self::PASSWORD_MIN_LENGTH, (int) config('iam.password.min_length', 12));
    }

    public function checkCompromisedPasswords(): bool
    {
        return filter_var($this->value(self::CHECK_COMPROMISED, config('iam.password.check_compromised', false)), FILTER_VALIDATE_BOOL);
    }

    public function update(array $values, User $actor): void
    {
        foreach ($values as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['group' => 'iam', 'value' => (string) $value, 'updated_by' => $actor->id],
            );
        }
    }

    private function integer(string $key, int $fallback): int
    {
        return (int) $this->value($key, $fallback);
    }

    private function value(string $key, mixed $fallback): mixed
    {
        if (! Schema::hasTable('system_settings')) {
            return $fallback;
        }

        return SystemSetting::query()->where('key', $key)->value('value') ?? $fallback;
    }
}
