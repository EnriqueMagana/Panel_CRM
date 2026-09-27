@props([
    'title',
    'description',
    'compact' => false,
    'action' => null,
])

<div @class(['chat-empty-state', 'chat-empty-state--compact' => $compact])>
    <div class="chat-empty-illustration" aria-hidden="true">
        <x-chat.icon name="message" class="size-8" />
    </div>
    <h2 class="chat-empty-title">{{ $title }}</h2>
    <p class="chat-empty-copy">{{ $description }}</p>
    @if ($action)
        <button type="button" class="chat-empty-action" data-open-chat-modal>{{ $action }}</button>
    @endif
</div>
