<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEscalationAttachment extends Model
{
    protected $fillable = [
        'ticket_escalation_id',
        'ticket_escalation_comment_id',
        'uploaded_by',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function escalation(): BelongsTo
    {
        return $this->belongsTo(TicketEscalation::class, 'ticket_escalation_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TicketEscalationComment::class, 'ticket_escalation_comment_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return route('media.public', ['path' => $this->path, 'v' => $this->updated_at?->getTimestamp()], false);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
