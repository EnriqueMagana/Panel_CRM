@props(['message', 'currentUser', 'conversation', 'editing' => false])

@php($outgoing = (int) $message->user_id === (int) $currentUser->id)

@if ($message->type === 'system')
    <div class="chat-system-event" wire:key="chat-system-event-{{ $message->id }}" role="note"
        aria-label="Evento del grupo">
        <span>{{ $message->body }}</span>
    </div>
@else
    <article @class(['chat-message', 'chat-message--outgoing' => $outgoing, 'chat-message--incoming' => !$outgoing])
        wire:key="chat-message-{{ $message->id }}">
    @if ($editing)
        <form wire:submit="updateMessage" class="chat-edit-form">
            <label class="sr-only" for="edit-message-{{ $message->id }}">Editar mensaje</label>
            <textarea id="edit-message-{{ $message->id }}" wire:model="editingBody" rows="2" maxlength="2000"
                class="chat-edit-input" required></textarea>
            @error('editingBody')
                <p class="chat-field-error" role="alert">{{ $message }}</p>
            @enderror
            <div class="chat-edit-actions">
                <button type="button" class="chat-cancel-button" wire:click="cancelEditingMessage">Cancelar</button>
                <button type="submit" class="chat-save-button" wire:loading.attr="disabled" wire:target="updateMessage">
                    <span wire:loading.remove wire:target="updateMessage">Guardar</span>
                    <span wire:loading wire:target="updateMessage">Guardando…</span>
                </button>
            </div>
        </form>
    @else
        <div @class(['chat-bubble', 'chat-bubble--outgoing' => $outgoing, 'chat-bubble--incoming' => !$outgoing])>
            @if (!$outgoing && $conversation->type === 'group')
                <p class="chat-message-sender">{{ $message->user?->name ?? 'Usuario' }}</p>
            @endif

            @if ($message->attachment_url)
                @if (in_array($message->type, ['image', 'gif'], true))
                    <a href="{{ $message->attachment_url }}" target="_blank" rel="noreferrer"
                        class="chat-media-link" aria-label="Abrir {{ $message->attachment_name }}">
                        <img src="{{ $message->attachment_url }}" alt="{{ $message->attachment_name ?: 'Imagen compartida' }}"
                            @if ($message->type === 'image')
                                srcset="{{ $message->attachmentUrlFor('small') }} 320w, {{ $message->attachmentUrlFor('medium') }} 960w, {{ $message->attachmentUrlFor('large') }} 1920w"
                                sizes="(max-width: 640px) 78vw, 352px"
                            @endif
                            loading="lazy" decoding="async" class="chat-message-image">
                    </a>
                @elseif ($message->type === 'audio')
                    <audio class="chat-message-audio" controls preload="metadata">
                        <source src="{{ $message->attachment_url }}" type="{{ $message->attachment_mime }}">
                        Tu navegador no puede reproducir este audio.
                    </audio>
                @endif
            @endif

            @if (filled($message->body))
                <p class="chat-message-body">{{ $message->body }}</p>
            @endif

            <div class="chat-message-meta">
                @if ($outgoing)
                    <details class="chat-message-menu">
                        <summary aria-label="Opciones del mensaje"><x-chat.icon name="more" class="size-4" /></summary>
                        <div class="chat-message-menu-popover">
                            <button type="button" wire:click="startEditingMessage({{ $message->id }})">
                                <x-chat.icon name="pencil" class="size-4" />Editar texto
                            </button>
                            <button type="button" class="is-destructive"
                                wire:click="deleteMessage({{ $message->id }})"
                                wire:confirm="¿Eliminar este mensaje? Esta acción no se puede deshacer.">
                                <x-chat.icon name="trash" class="size-4" />Eliminar
                            </button>
                        </div>
                    </details>
                @endif
                <time datetime="{{ $message->sent_at?->toIso8601String() }}">
                    {{ $message->sent_at?->format('H:i') ?? 'Ahora' }}
                </time>
                @if ($outgoing)
                    <x-chat.icon name="check-check" @class(['size-4', 'is-read' => filled($message->read_at)]) />
                @endif
            </div>
        </div>
    @endif
    </article>
@endif
