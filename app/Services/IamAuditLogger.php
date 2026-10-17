<?php

namespace App\Services;

use App\Models\IamAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class IamAuditLogger
{
    public function record(
        string $event,
        string $description,
        ?User $actor = null,
        ?User $subject = null,
        array $metadata = [],
    ): void {
        if (! Schema::hasTable('iam_audit_logs')) {
            return;
        }

        IamAuditLog::query()->create([
            'actor_id' => $actor?->id,
            'subject_user_id' => $subject?->id,
            'event' => $event,
            'description' => $description,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}
