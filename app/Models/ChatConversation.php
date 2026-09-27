<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class ChatConversation extends Model
{
    protected $fillable = [
        'type',
        'name',
        'description',
        'image_path',
        'image_variants',
        'avatar_seed',
        'created_by',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'image_variants' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChatConversation $conversation): void {
            if ($conversation->type === 'group' && blank($conversation->avatar_seed)) {
                $conversation->avatar_seed = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_conversation_user')
            ->withPivot(['is_admin', 'joined_at', 'history_visible_after_message_id', 'hidden_at'])
            ->withTimestamps();
    }

    public function scopeVisibleTo(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->whereHas('participants', fn (Builder $participants) => $participants
            ->whereKey($userId)
            ->whereNull('chat_conversation_user.hidden_at'));
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function messagesVisibleTo(User $user): HasMany
    {
        $messages = $this->messages();
        $participant = $this->participants()->whereKey($user->id)->first();

        if (! $participant) {
            return $messages->whereRaw('1 = 0');
        }

        if ($this->type !== 'group') {
            return $messages;
        }

        $cutoff = $participant->pivot->history_visible_after_message_id;

        if ($cutoff !== null) {
            $messages->where('chat_messages.id', '>', (int) $cutoff);
        }

        return $messages;
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->ofMany([
            'sent_at' => 'max',
            'id' => 'max',
        ]);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->imageUrlFor('medium');
    }

    public function imageUrlFor(string $size = 'medium'): ?string
    {
        $path = $this->image_variants[$size]
            ?? $this->image_variants['medium']
            ?? $this->image_path;

        if (! $path) {
            return null;
        }

        return route('media.public', [
            'path' => $path,
            'v' => $this->updated_at?->getTimestamp(),
        ], false);
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->created_by === (int) $user->id;
    }
}
