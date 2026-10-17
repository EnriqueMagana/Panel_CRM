<?php

return [
    'reauthentication_timeout_minutes' => (int) env('IAM_REAUTH_TIMEOUT_MINUTES', 15),

    'session' => [
        'idle_timeout_minutes' => (int) env('IAM_IDLE_TIMEOUT_MINUTES', 30),
        'absolute_timeout_minutes' => (int) env('IAM_ABSOLUTE_TIMEOUT_MINUTES', 480),
        'warning_seconds' => (int) env('IAM_SESSION_WARNING_SECONDS', 120),
    ],

    'password' => [
        'min_length' => (int) env('IAM_PASSWORD_MIN_LENGTH', 12),
        'check_compromised' => (bool) env('IAM_PASSWORD_CHECK_COMPROMISED', false),
        'compromised_threshold' => (int) env('IAM_PASSWORD_COMPROMISED_THRESHOLD', 3),
    ],
];
