<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    protected $fillable = [
        'type',
        'name',
        'created_by',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_conversation_user')
            ->withPivot(['is_admin', 'joined_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderByDesc('sent_at')->orderByDesc('id');
    }
}
