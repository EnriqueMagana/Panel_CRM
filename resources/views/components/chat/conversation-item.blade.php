@props([
    'title',
    'preview' => 'Sin mensajes todavía',
    'time' => null,
    'active' => false,
    'unread' => 0,
    'user' => null,
    'group' => false,
    'imageUrl' => null,
    'seed' => null,
    'avatarKey' => null,
])

<button type="button" {{ $attributes->class(['chat-list-item', 'is-active' => $active]) }}
    @if ($active) aria-current="page" @endif>
    <x-chat.avatar :user="$user" :label="$title" :group="$group" :image-url="$imageUrl" :seed="$seed"
        wire:key="{{ $avatarKey ?: 'conversation-avatar-' . sha1($title . $imageUrl . $seed) }}" wire:ignore />
    <span class="chat-list-copy">
        <span class="chat-row-top">
            <span class="chat-user-name">{{ $title }}</span>
            @if ($time)
                <time class="chat-time-text" datetime="{{ $time->toIso8601String() }}">
                    {{ $time->isToday() ? $time->format('H:i') : $time->shortRelativeDiffForHumans() }}
                </time>
            @endif
        </span>
        <span class="chat-row-bottom">
            <span class="chat-preview-text">{{ $preview }}</span>
            @if ($unread > 0)
                <span class="chat-unread-badge" aria-label="{{ $unread }} mensajes sin leer">{{ min($unread, 99) }}</span>
            @endif
        </span>
    </span>
</button>
