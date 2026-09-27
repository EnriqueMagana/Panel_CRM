<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_conversation_id',
        'user_id',
        'direction',
        'type',
        'event_data',
        'body',
        'attachment_path',
        'attachment_variants',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'sent_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
            'event_data' => 'array',
            'attachment_variants' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachmentUrlFor($this->type === 'gif' ? 'original' : 'medium');
    }

    public function attachmentUrlFor(string $size = 'medium'): ?string
    {
        $path = $this->attachment_variants[$size]
            ?? $this->attachment_variants['medium']
            ?? $this->attachment_path;

        if (! $path) {
            return null;
        }

        return route('media.public', [
            'path' => $path,
            'v' => $this->updated_at?->getTimestamp(),
        ], false);
    }
}
