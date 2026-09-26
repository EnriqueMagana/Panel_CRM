@extends('layouts.app')

@section('title', 'Chats')

@section('content')
    @php
        $currentUser = auth()->user();
        $contacts = \App\Models\User::query()->whereKeyNot($currentUser->id)->orderBy('name')->get();
        $conversations = \App\Models\ChatConversation::query()
            ->with(['participants', 'messages' => fn($query) => $query->orderByDesc('sent_at')->orderByDesc('id')])
            ->whereHas('participants', fn($query) => $query->where('users.id', $currentUser->id))
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        $selectedConversation = null;
        $selectedContact = null;

        if (request()->filled('conversation_id')) {
            $selectedConversation = $conversations->firstWhere('id', request('conversation_id'));
        }

        if ($selectedConversation) {
            $selectedContact = $selectedConversation->participants->first(
                fn($participant) => $participant->id !== $currentUser->id,
            );
        }

        if (!$selectedConversation && request()->filled('contact_id')) {
            $selectedContact = $contacts->firstWhere('id', request('contact_id'));
            $selectedConversation = $conversations->first(function ($conversation) use (
                $selectedContact,
                $currentUser,
            ) {
                return $selectedContact &&
                    $conversation->participants->contains('id', $selectedContact->id) &&
                    $conversation->participants->contains('id', $currentUser->id);
            });
        }
    @endphp

    <div class="space-y-5" data-module-type="chat">
        <div data-chat-shell class="chat-shell" data-mobile-view="list">
            <aside data-chat-list class="chat-sidebar">
                <div class="chat-sidebar-head">
                    <div>
                        <p class="chat-eyebrow">Messages</p>
                        <h1 class="chat-title">Inbox</h1>
                    </div>
                    <button type="button" id="chat-create-toggle" class="chat-new-button" aria-label="Nueva conversación">
                        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                <div class="chat-search-wrap">
                    <label class="chat-search relative block">
                        <span class="sr-only">Buscar chat</span>
                        <svg viewBox="0 0 24 24" fill="none" class="chat-search-icon" aria-hidden="true">
                            <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.6" />
                            <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        </svg>
                        <input id="chat-search-input" type="search" placeholder="Search chat..." class="chat-search-input">
                    </label>
                </div>

                <div class="chat-contact-list">
                    @forelse ($conversations as $conversation)
                        @php
                            $conversationParticipant = $conversation->participants->first(
                                fn($participant) => $participant->id !== $currentUser->id,
                            );
                            $conversationTitle =
                                $conversation->type === 'group'
                                    ? $conversation->name
                                    : $conversationParticipant?->name ?? 'Chat';
                            $lastConversationMessage = $conversation->messages->first();
                            $activeConversation = $selectedConversation?->id === $conversation->id;
                        @endphp

                        <a href="{{ route('chats', ['conversation_id' => $conversation->id]) }}"
                            class="chat-list-item {{ $activeConversation ? 'is-active' : '' }}" data-chat-item
                            data-no-module-load="true" data-chat-name="{{ $conversationTitle }}"
                            data-chat-text="{{ $lastConversationMessage?->body ?? 'Sin mensajes' }}"
                            data-chat-type="{{ $conversation->type }}">
                            <div class="chat-avatar bg-gradient-to-br from-slate-700 to-slate-500">
                                {{ strtoupper(substr($conversationTitle, 0, 2)) }}
                            </div>
                            <div class="chat-list-copy">
                                <div class="chat-row-top">
                                    <span class="chat-user-name">{{ $conversationTitle }}</span>
                                    <span
                                        class="chat-time-text">{{ $lastConversationMessage?->sent_at?->diffForHumans() ?? 'new' }}</span>
                                </div>
                                <span
                                    class="chat-preview-text">{{ $lastConversationMessage?->body ?? 'Sin mensajes' }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="chat-empty-state chat-empty-inline">
                            <div>
                                <p class="chat-empty-title">No hay conversaciones activas</p>
                                <p class="chat-empty-copy">Usa + para iniciar una nueva conversación o grupo.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </aside>

            <section data-chat-panel class="chat-panel">
                @if ($selectedConversation && $selectedContact)
                    <header class="chat-header">
                        <div class="chat-header-main">
                            <button type="button" data-chat-back class="chat-mobile-back lg:hidden"
                                aria-label="Volver a conversaciones">
                                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                                    <path d="M15 18 9 12l6-6" stroke="currentColor" stroke-width="1.8"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div class="chat-avatar chat-avatar-large bg-gradient-to-br from-amber-500 to-orange-500">
                                {{ strtoupper(substr($selectedContact->name, 0, 2)) }}
                            </div>
                            <div>
                                <h2 class="chat-contact-name">{{ $selectedContact->name }}</h2>
                                <p class="chat-status-text">{{ $selectedContact->email }}</p>
                            </div>
                        </div>
                        <div class="chat-header-actions">
                            <button type="button" class="chat-icon-button" aria-label="Llamada">
                                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                                    <path
                                        d="M6.6 10.8a15.5 15.5 0 0 0 6.6 6.6l2.2-2.2a1.2 1.2 0 0 1 1.2-.28c1.3.43 2.7.66 4.1.66a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C11.3 21 3 12.7 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.4.23 2.8.66 4.1a1.2 1.2 0 0 1-.28 1.2L6.6 10.8Z"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                            <button type="button" class="chat-icon-button" aria-label="Video">
                                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                                    <rect x="3.5" y="6" width="12.5" height="12" rx="2.5" stroke="currentColor"
                                        stroke-width="1.6" />
                                    <path d="M16 10.5 20.5 8v8L16 13.5v-3Z" stroke="currentColor" stroke-width="1.5"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                            <button type="button" class="chat-icon-button" aria-label="Más opciones">
                                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                                    <circle cx="12" cy="5" r="1.6" fill="currentColor" />
                                    <circle cx="12" cy="12" r="1.6" fill="currentColor" />
                                    <circle cx="12" cy="19" r="1.6" fill="currentColor" />
                                </svg>
                            </button>
                            <form method="POST" action="{{ route('chat.conversation.destroy', $selectedConversation) }}"
                                onsubmit="return confirm('¿Eliminar esta conversación?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="chat-action-link text-red-500">Eliminar chat</button>
                            </form>
                        </div>
                    </header>

                    <div class="chat-message-list">
                        @foreach ($selectedConversation->messages as $message)
                            @php
                                $isOutgoing = $message->user_id === $currentUser->id;
                            @endphp

                            <div class="chat-message {{ $isOutgoing ? 'chat-message--outgoing' : 'chat-message--incoming' }}"
                                data-message-row>
                                <div
                                    class="chat-bubble {{ $isOutgoing ? 'chat-bubble--outgoing' : 'chat-bubble--incoming' }}">
                                    <p>{{ $message->body }}</p>
                                    <div class="chat-message-meta">
                                        <span class="chat-time">{{ $message->sent_at?->format('g:i A') ?? 'Now' }}</span>
                                        @if ($message->user_id === $currentUser->id)
                                            <div class="chat-message-actions">
                                                <button type="button" class="chat-action-link"
                                                    data-edit-message>Editar</button>
                                                <form method="POST"
                                                    action="{{ route('chat.messages.destroy', [$selectedConversation, $message]) }}"
                                                    onsubmit="return confirm('¿Eliminar este mensaje?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="chat-action-link text-red-500">Eliminar</button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if ($message->user_id === $currentUser->id)
                                    <form method="POST"
                                        action="{{ route('chat.messages.update', [$selectedConversation, $message]) }}"
                                        class="chat-edit-form hidden" data-edit-form>
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="body" value="{{ $message->body }}"
                                            class="chat-edit-input" required>
                                        <div class="chat-edit-actions">
                                            <button type="submit" class="chat-save-button">Guardar</button>
                                            <button type="button" class="chat-cancel-button"
                                                data-cancel-edit>Cancelar</button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="chat-composer-wrap">
                        <form method="POST" action="{{ route('chat.messages.store', $selectedConversation) }}"
                            class="chat-composer">
                            @csrf
                            <button type="button" class="chat-composer-button" aria-label="Agregar archivo">
                                <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.7"
                                        stroke-linecap="round" />
                                </svg>
                            </button>
                            <button type="button" class="chat-composer-button" aria-label="Adjuntar imagen">
                                <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                                    <rect x="3.5" y="6" width="17" height="12" rx="2.5"
                                        stroke="currentColor" stroke-width="1.6" />
                                    <circle cx="9" cy="11" r="2.2" stroke="currentColor"
                                        stroke-width="1.6" />
                                    <path d="m7 16 3.2-3.2 3.5 3.5 2.3-2.3 3 3" stroke="currentColor" stroke-width="1.6"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div class="flex-1"><input type="text" name="body" placeholder="Escribe un mensaje..."
                                    class="chat-composer-input" required></div>
                            <button type="submit" class="chat-send-button" aria-label="Enviar mensaje">
                                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                                    <path d="M3 11.5 20 4l-4 16-4.5-6.5L3 11.5Z" stroke="currentColor" stroke-width="1.5"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="chat-empty-state">
                        <div>
                            <p class="chat-empty-title">No hay conversaciones aún</p>
                            <p class="chat-empty-copy">Selecciona un usuario para iniciar la conversación.</p>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>

    <dialog id="chat-create-dialog" class="chat-modal">
        <div class="chat-modal-card">
            <div class="chat-modal-head">
                <h3>Nueva conversación</h3>
                <button type="button" data-close-chat-modal class="chat-icon-button"
                    aria-label="Cerrar modal">✕</button>
            </div>

            <div class="chat-modal-section">
                <h4>Contactos</h4>
                <div class="chat-modal-list">
                    @foreach ($contacts as $contact)
                        @php
                            $existingDirectChat = $conversations->first(function ($conversation) use (
                                $contact,
                                $currentUser,
                            ) {
                                return $conversation->type === 'direct' &&
                                    $conversation->participants->contains('id', $contact->id) &&
                                    $conversation->participants->contains('id', $currentUser->id);
                            });
                        @endphp

                        @if ($existingDirectChat)
                            <a href="{{ route('chats', ['conversation_id' => $existingDirectChat->id]) }}"
                                class="chat-modal-contact">
                                <span
                                    class="chat-avatar chat-avatar-small bg-gradient-to-br from-violet-500 to-fuchsia-500">{{ strtoupper(substr($contact->name, 0, 2)) }}</span>
                                <span>{{ $contact->name }}</span>
                            </a>
                        @else
                            <form method="POST" action="{{ route('chat.direct.store') }}"
                                class="chat-modal-contact-form">
                                @csrf
                                <input type="hidden" name="contact_id" value="{{ $contact->id }}">
                                <input type="hidden" name="body" value="Hola, empieza la conversación.">
                                <button type="submit" class="chat-modal-contact">
                                    <span
                                        class="chat-avatar chat-avatar-small bg-gradient-to-br from-violet-500 to-fuchsia-500">{{ strtoupper(substr($contact->name, 0, 2)) }}</span>
                                    <span>{{ $contact->name }}</span>
                                </button>
                            </form>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="chat-modal-section">
                <h4>Crear grupo</h4>
                <form method="POST" action="{{ route('chat.groups.store') }}" class="chat-group-panel">
                    @csrf
                    <div>
                        <label class="chat-form-label">Nombre del grupo</label>
                        <input type="text" name="name" class="chat-form-input" placeholder="Ej: Equipo de diseño"
                            required>
                    </div>
                    <div>
                        <label class="chat-form-label">Participantes</label>
                        <div class="chat-form-list">
                            @foreach ($contacts as $contact)
                                <label class="chat-check-item">
                                    <input type="checkbox" name="participants[]" value="{{ $contact->id }}"
                                        class="chat-check-input">
                                    <span>{{ $contact->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <button type="submit" class="chat-create-group-btn">Crear grupo</button>
                </form>
            </div>
        </div>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const shell = document.querySelector('[data-chat-shell]');
            const toggle = document.getElementById('chat-create-toggle');
            const modal = document.getElementById('chat-create-dialog');
            const searchInput = document.getElementById('chat-search-input');
            const backButton = document.querySelector('[data-chat-back]');
            const listItems = Array.from(document.querySelectorAll('[data-chat-item]'));

            toggle?.addEventListener('click', function() {
                if (modal instanceof HTMLDialogElement) {
                    modal.showModal();
                }
            });

            modal?.querySelector('[data-close-chat-modal]')?.addEventListener('click', function() {
                modal.close();
            });

            modal?.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.close();
                }
            });

            if (backButton && shell) {
                backButton.addEventListener('click', function() {
                    shell.dataset.mobileView = 'list';
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', function(event) {
                    const term = (event.target.value || '').toLowerCase().trim();
                    listItems.forEach((item) => {
                        const name = (item.dataset.chatName || '').toLowerCase();
                        const text = (item.dataset.chatText || '').toLowerCase();
                        const visible = !term || name.includes(term) || text.includes(term);
                        item.classList.toggle('hidden', !visible);
                    });
                });
            }

            document.querySelectorAll('[data-edit-message]').forEach((button) => {
                button.addEventListener('click', function() {
                    const row = button.closest('[data-message-row]');
                    if (!row) return;

                    row.querySelector('[data-edit-form]')?.classList.remove('hidden');
                    const bubble = row.querySelector('.chat-bubble');
                    bubble?.classList.add('hidden');
                });
            });

            document.querySelectorAll('[data-cancel-edit]').forEach((button) => {
                button.addEventListener('click', function() {
                    const row = button.closest('[data-message-row]');
                    if (!row) return;

                    row.querySelector('[data-edit-form]')?.classList.add('hidden');
                    row.querySelector('.chat-bubble')?.classList.remove('hidden');
                });
            });

            listItems.forEach((item) => {
                item.addEventListener('click', function() {
                    if (window.innerWidth < 1024 && shell) {
                        shell.dataset.mobileView = 'chat';
                    }
                });
            });
        });
    </script>
@endsection
