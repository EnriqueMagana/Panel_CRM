@php
    $hasOpenThread = filled($selectedConversation) || filled($selectedContact);
    $selectedIsGroup = $selectedConversation?->type === 'group';
    $threadTitle = $selectedIsGroup
        ? $selectedConversation->name
        : ($selectedContact?->name ?? 'Conversación');
    $threadSubtitle = $selectedIsGroup
        ? $participants->count() . ' participantes'
        : ($selectedContact?->username ? '@' . $selectedContact->username : $selectedContact?->email);
    $threadAvatarSeed = $selectedIsGroup
        ? $selectedConversation?->avatar_seed
        : ($selectedContact?->avatar_seed ?: hash('sha256', (string) $selectedContact?->email));
    $threadAvatarUrl = $selectedIsGroup
        ? $selectedConversation?->imageUrlFor('small')
        : $selectedContact?->profilePhotoUrl('small');
    $threadAvatarKey = 'thread-avatar-' . ($selectedConversation?->id ?: 'contact-' . $selectedContact?->id)
        . '-' . sha1((string) $threadAvatarUrl . (string) $threadAvatarSeed);
    $lastMessageDate = null;
@endphp

<div class="chat-page" data-chat-workspace>
    @if ($notice)
        <div class="chat-toast" role="status" wire:transition.opacity>
            <x-chat.icon name="check" class="size-4" />
            <span>{{ $notice }}</span>
            <button type="button" wire:click="$set('notice', null)" aria-label="Cerrar aviso">
                <x-chat.icon name="x" class="size-4" />
            </button>
        </div>
    @endif

    <div data-chat-shell class="chat-shell" data-mobile-view="{{ $hasOpenThread ? 'chat' : 'list' }}">
        <aside class="chat-sidebar" aria-label="Conversaciones">
            <header class="chat-sidebar-head">
                <div>
                    <p class="chat-eyebrow">Mensajería</p>
                    <div class="chat-title-row">
                        <h1 class="chat-title">Mensajes</h1>
                        <span class="chat-conversation-count" aria-label="{{ $conversations->count() }} conversaciones">
                            {{ $conversations->count() }}
                        </span>
                    </div>
                </div>
                <button type="button" class="chat-new-button" wire:click="openCreateModal"
                    aria-label="Nueva conversación" aria-haspopup="dialog">
                    <x-chat.icon name="plus" />
                </button>
            </header>

            <div class="chat-search-wrap">
                <label class="chat-search">
                    <span class="sr-only">Buscar conversaciones</span>
                    <x-chat.icon name="search" class="chat-search-icon" />
                    <input type="search" placeholder="Buscar conversación…" class="chat-search-input"
                        wire:model.live.debounce.250ms="search" autocomplete="off">
                </label>
            </div>

            <nav class="chat-contact-list" aria-label="Lista de conversaciones">
                @forelse ($conversations as $conversation)
                    @php
                        $peer = $conversation->type === 'direct'
                            ? $conversation->participants->first(fn ($participant) => $participant->id !== $user->id)
                            : null;
                        $conversationTitle = $conversation->type === 'group'
                            ? $conversation->name
                            : ($peer?->name ?? 'Conversación');
                        $lastMessage = $conversation->latestMessage;
                        $attachmentLabel = match ($lastMessage?->type) {
                            'image' => 'Imagen',
                            'gif' => 'GIF',
                            'audio' => 'Audio',
                            default => '',
                        };
                        $previewBody = filled($lastMessage?->body) ? $lastMessage->body : $attachmentLabel;
                        $preview = $lastMessage
                            ? (($lastMessage->type !== 'system' && $lastMessage->user_id === $user->id ? 'Tú: ' : '') . $previewBody)
                            : 'Inicia la conversación';
                        $active = $selectedConversation?->id === $conversation->id;
                        $avatarSeed = $conversation->type === 'group'
                            ? $conversation->avatar_seed
                            : ($peer?->avatar_seed ?: hash('sha256', (string) $peer?->email));
                        $avatarKey = 'sidebar-avatar-' . $conversation->id . '-'
                            . sha1((string) $conversation->image_url . (string) $avatarSeed);
                    @endphp

                    <x-chat.conversation-item wire:key="conversation-{{ $conversation->id }}"
                        wire:click="selectConversation({{ $conversation->id }})" :title="$conversationTitle"
                        :preview="$preview" :time="$lastMessage?->sent_at" :active="$active"
                        :unread="$active ? 0 : $conversation->unread_count" :user="$peer"
                        :group="$conversation->type === 'group'" :image-url="$conversation->imageUrlFor('small')"
                        :seed="$avatarSeed" :avatar-key="$avatarKey" />
                @empty
                    <div class="chat-list-empty">
                        <x-chat.icon name="message" class="size-6" />
                        <p>{{ $search ? 'No encontramos conversaciones' : 'Tu bandeja está vacía' }}</p>
                        <span>{{ $search ? 'Prueba con otro término.' : 'Inicia una conversación con tu equipo.' }}</span>
                    </div>
                @endforelse
            </nav>
        </aside>

        <section class="chat-panel" aria-label="Conversación activa">
            @if ($hasOpenThread)
                <header class="chat-header">
                    <div class="chat-header-main">
                        <button type="button" wire:click="closeConversation" class="chat-mobile-back"
                            aria-label="Volver a conversaciones">
                            <x-chat.icon name="arrow-left" />
                        </button>
                        <x-chat.avatar :user="$selectedIsGroup ? null : $selectedContact" :label="$threadTitle" size="lg"
                            :group="$selectedIsGroup" :image-url="$threadAvatarUrl" :seed="$threadAvatarSeed"
                            loading="eager" wire:key="{{ $threadAvatarKey }}" wire:ignore />
                        <div class="chat-header-copy">
                            <h2 class="chat-contact-name">{{ $threadTitle }}</h2>
                            <p class="chat-status-text">{{ $threadSubtitle ?: 'Disponible en Snippetdesk' }}</p>
                        </div>
                    </div>

                    @if ($selectedConversation)
                        @if ($selectedIsGroup)
                            <button type="button" class="chat-icon-button" wire:click="openConversationInfo"
                                aria-label="Información y opciones del grupo">
                                <x-chat.icon name="more" />
                            </button>
                        @else
                            <button type="button" class="chat-icon-button" wire:click="openConversationInfo"
                                aria-label="Información y opciones del chat">
                                <x-chat.icon name="more" />
                            </button>
                        @endif
                    @endif
                </header>

                <div class="chat-message-list" data-chat-message-list aria-live="polite">
                    @if ($messages->isEmpty())
                        <div class="chat-thread-start">
                            <div class="chat-thread-start-icon"><x-chat.icon name="message" class="size-6" /></div>
                            <p>Esta conversación comienza aquí</p>
                            <span>Envía un mensaje para saludar a {{ $threadTitle }}.</span>
                        </div>
                    @else
                        @foreach ($messages as $message)
                            @php($messageDate = $message->sent_at?->toDateString() ?? $message->created_at->toDateString())
                            @if ($lastMessageDate !== $messageDate)
                                <div class="chat-date-separator" wire:key="date-{{ $messageDate }}">
                                    <span>{{ $message->sent_at?->isToday() ? 'Hoy' : ($message->sent_at?->isYesterday() ? 'Ayer' : $message->sent_at?->translatedFormat('d M Y')) }}</span>
                                </div>
                                @php($lastMessageDate = $messageDate)
                            @endif
                            <x-chat.message-bubble :message="$message" :current-user="$user"
                                :conversation="$selectedConversation" :editing="$editingMessageId === $message->id" />
                        @endforeach
                    @endif
                </div>

                <footer class="chat-composer-wrap">
                    @if ($attachment)
                        @php($attachmentMime = $attachment->getMimeType())
                        <div class="chat-attachment-preview" wire:loading.remove wire:target="attachment">
                            @if (str_starts_with((string) $attachmentMime, 'image/'))
                                <img src="{{ $attachment->temporaryUrl() }}" alt="Vista previa del adjunto">
                            @else
                                <span class="chat-attachment-file"><x-chat.icon name="mic" class="size-5" /></span>
                            @endif
                            <span class="min-w-0 flex-1">
                                <strong>{{ $attachment->getClientOriginalName() }}</strong>
                                <small>{{ number_format($attachment->getSize() / 1024, 0) }} KB</small>
                            </span>
                            <button type="button" wire:click="removeAttachment" aria-label="Quitar archivo">
                                <x-chat.icon name="x" class="size-4" />
                            </button>
                        </div>
                    @endif

                    <div class="chat-upload-progress" wire:loading.flex wire:target="attachment" role="status">
                        <span class="chat-spinner" aria-hidden="true"></span> Preparando archivo…
                    </div>

                    @error('attachment')
                        <p class="chat-field-error" role="alert">{{ $message }}</p>
                    @enderror
                    @error('messageBody')
                        <p class="chat-field-error" role="alert">{{ $message }}</p>
                    @enderror

                    <form wire:submit="sendMessage" class="chat-composer" data-chat-composer>
                        <label class="chat-composer-button" aria-label="Adjuntar imagen, GIF o audio" title="Adjuntar archivo">
                            <x-chat.icon name="paperclip" />
                            <input type="file" wire:model="attachment"
                                accept="image/jpeg,image/png,image/webp,image/gif,audio/mpeg,audio/wav,audio/ogg,audio/mp4,audio/aac,audio/webm"
                                class="sr-only">
                        </label>
                        <div class="chat-emoji-wrap">
                            <button type="button" class="chat-composer-button" wire:click="$toggle('emojiPickerOpen')"
                                aria-label="Elegir emoji" aria-expanded="{{ $emojiPickerOpen ? 'true' : 'false' }}">
                                <x-chat.icon name="smile" />
                            </button>
                            @if ($emojiPickerOpen)
                                <div class="chat-emoji-picker" role="group" aria-label="Emojis">
                                    @foreach ($emojis as $emoji)
                                        <button type="button" wire:click="appendEmoji('{{ $emoji }}')"
                                            aria-label="Agregar {{ $emoji }}">{{ $emoji }}</button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <label class="chat-composer-field">
                            <span class="sr-only">Escribe un mensaje</span>
                            <textarea wire:model="messageBody" rows="1" maxlength="2000" placeholder="Escribe un mensaje"
                                class="chat-composer-input" data-chat-composer-input></textarea>
                        </label>
                        <button type="submit" class="chat-send-button" aria-label="Enviar mensaje"
                            wire:loading.attr="disabled" wire:target="sendMessage,attachment">
                            <span wire:loading.remove wire:target="sendMessage"><x-chat.icon name="send" /></span>
                            <span wire:loading wire:target="sendMessage" class="chat-spinner" aria-hidden="true"></span>
                        </button>
                    </form>
                    <p class="chat-composer-help">Enter para enviar · Shift + Enter para una nueva línea</p>
                </footer>
            @else
                <div class="chat-empty-state">
                    <div class="chat-empty-illustration" aria-hidden="true">
                        <x-chat.icon name="message" class="size-8" />
                    </div>
                    <h2 class="chat-empty-title">Tus mensajes, en un solo lugar</h2>
                    <p class="chat-empty-copy">Selecciona una conversación o inicia una nueva para comenzar.</p>
                    <button type="button" class="chat-empty-action" wire:click="openCreateModal">Enviar mensaje</button>
                </div>
            @endif

            @if ($groupInfoOpen && $selectedIsGroup)
                <aside class="chat-info-panel" aria-label="Información del grupo" wire:transition>
                    <header class="chat-info-header">
                        <div>
                            <p class="chat-eyebrow">Opciones</p>
                            <h3>Información del grupo</h3>
                        </div>
                        <button type="button" class="chat-icon-button" wire:click="closeGroupInfo" aria-label="Cerrar panel">
                            <x-chat.icon name="x" />
                        </button>
                    </header>

                    <div class="chat-info-scroll">
                        <section class="chat-group-profile">
                            <x-chat.avatar :label="$selectedConversation->name" size="lg" group
                                :image-url="$selectedConversation->imageUrlFor('small')" :seed="$selectedConversation->avatar_seed"
                                loading="eager"
                                wire:key="group-info-avatar-{{ $selectedConversation->id }}-{{ sha1((string) $selectedConversation->image_url . (string) $selectedConversation->avatar_seed) }}"
                                wire:ignore />
                            <div>
                                <h4>{{ $selectedConversation->name }}</h4>
                                <p>{{ $participants->count() }} participantes</p>
                            </div>
                        </section>

                        @if ($selectedConversation->description)
                            <section class="chat-info-section">
                                <h4>Descripción</h4>
                                <p>{{ $selectedConversation->description }}</p>
                            </section>
                        @endif

                        @if ($isGroupOwner)
                            <section class="chat-info-section">
                                <button type="button" class="chat-settings-action"
                                    wire:click="$toggle('editingGroup')">
                                    <x-chat.icon name="pencil" class="size-4" /> Editar grupo
                                </button>
                                @if ($editingGroup)
                                    <form wire:submit="updateGroup" class="chat-settings-form">
                                        <label class="chat-form-label" for="group-edit-name">Nombre</label>
                                        <input id="group-edit-name" type="text" wire:model="groupEditName"
                                            class="chat-form-input" maxlength="120">
                                        @error('groupEditName')<p class="chat-field-error" role="alert">{{ $message }}</p>@enderror

                                        <label class="chat-form-label" for="group-edit-description">Descripción</label>
                                        <textarea id="group-edit-description" wire:model="groupEditDescription"
                                            class="chat-form-input chat-form-textarea" maxlength="1000"></textarea>
                                        @error('groupEditDescription')<p class="chat-field-error" role="alert">{{ $message }}</p>@enderror

                                        <label class="chat-file-field">
                                            <x-chat.icon name="camera" class="size-4" />
                                            <span>Cambiar foto</span>
                                            <input type="file" wire:model="groupEditPhoto"
                                                accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only">
                                        </label>
                                        @error('groupEditPhoto')<p class="chat-field-error" role="alert">{{ $message }}</p>@enderror

                                        <button type="submit" class="chat-primary-button" wire:loading.attr="disabled"
                                            wire:target="updateGroup,groupEditPhoto">
                                            <span wire:loading.remove wire:target="updateGroup">Guardar cambios</span>
                                            <span wire:loading wire:target="updateGroup">Guardando…</span>
                                        </button>
                                    </form>
                                @endif
                            </section>
                        @endif

                        @if ($isGroupOwner)
                            <section class="chat-info-section">
                                <button type="button" class="chat-settings-action"
                                    wire:click="$toggle('addingParticipants')"
                                    aria-expanded="{{ $addingParticipants ? 'true' : 'false' }}">
                                    <x-chat.icon name="user-plus" class="size-4" /> Agregar participantes
                                </button>

                                @if ($addingParticipants)
                                    @if ($availableParticipants->isEmpty())
                                        <div class="chat-add-empty" role="status">
                                            <x-chat.icon name="users" class="size-5" />
                                            <p>No hay más contactos disponibles para agregar.</p>
                                        </div>
                                    @else
                                        <form wire:submit="addParticipants" class="chat-add-members">
                                            <div class="chat-form-list">
                                                @foreach ($availableParticipants as $contact)
                                                    <label class="chat-check-item" wire:key="available-{{ $contact->id }}">
                                                        <input type="checkbox" wire:model="addParticipantIds"
                                                            value="{{ $contact->id }}" class="chat-check-input">
                                                        <x-chat.avatar :user="$contact" :label="$contact->name" size="sm"
                                                            wire:key="available-avatar-{{ $contact->id }}" wire:ignore />
                                                        <span>{{ $contact->name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('addParticipantIds')<p class="chat-field-error" role="alert">{{ $message }}</p>@enderror
                                            <button type="submit" class="chat-primary-button"
                                                wire:loading.attr="disabled" wire:target="addParticipants">
                                                <span wire:loading.remove wire:target="addParticipants">Agregar seleccionados</span>
                                                <span wire:loading wire:target="addParticipants">Agregando…</span>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </section>
                        @endif

                        <section class="chat-info-section">
                            <div class="chat-info-section-title">
                                <h4>Participantes · {{ $participants->count() }}</h4>
                            </div>

                            <div class="chat-participant-list">
                                @foreach ($participants as $participant)
                                    <div class="chat-participant" wire:key="participant-{{ $participant->id }}">
                                        <x-chat.avatar :user="$participant" :label="$participant->name" size="sm"
                                            wire:key="participant-avatar-{{ $participant->id }}" wire:ignore />
                                        <span class="min-w-0 flex-1">
                                            <strong>{{ $participant->name }}</strong>
                                            <small>
                                                {{ $participant->id === $selectedConversation->created_by ? 'Creador del grupo' : ($participant->username ? '@' . $participant->username : $participant->email) }}
                                            </small>
                                        </span>
                                        @if ($isGroupOwner && $participant->id !== $user->id)
                                            <button type="button" class="chat-remove-member"
                                                wire:click="removeParticipant({{ $participant->id }})"
                                                wire:confirm="¿Eliminar a {{ $participant->name }} del grupo?"
                                                aria-label="Eliminar a {{ $participant->name }}">
                                                <x-chat.icon name="user-minus" class="size-4" />
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <x-chat.media-gallery :media="$sharedMedia" :audio="$sharedAudio" />

                        <section class="chat-danger-zone">
                            <button type="button" wire:click="leaveConversation"
                                wire:confirm="¿Salir de este grupo? Si eres el creador, la propiedad pasará a otro participante.">
                                <x-chat.icon name="log-out" class="size-4" /> Salir del grupo
                            </button>
                        </section>
                    </div>
                </aside>
            @endif

            @if ($directInfoOpen && !$selectedIsGroup && $selectedConversation && $selectedContact)
                <aside class="chat-info-panel" aria-label="Información del contacto y opciones del chat" wire:transition>
                    <header class="chat-info-header">
                        <div>
                            <p class="chat-eyebrow">Opciones</p>
                            <h3>Información del chat</h3>
                        </div>
                        <button type="button" class="chat-icon-button" wire:click="closeDirectInfo"
                            aria-label="Cerrar panel">
                            <x-chat.icon name="x" />
                        </button>
                    </header>

                    <div class="chat-info-scroll">
                        <section class="chat-direct-profile">
                            <x-chat.avatar :user="$selectedContact" :label="$selectedContact->name" size="lg"
                                loading="eager" wire:key="direct-info-avatar-{{ $selectedContact->id }}" wire:ignore />
                            <div>
                                <h4>{{ $selectedContact->name }}</h4>
                                <p>{{ $selectedContact->username ? '@' . $selectedContact->username : $selectedContact->email }}</p>
                            </div>
                        </section>

                        <section class="chat-info-section chat-contact-details">
                            <h4>Contacto</h4>
                            <dl>
                                <div><dt>Correo</dt><dd>{{ $selectedContact->email }}</dd></div>
                                @if ($selectedContact->phone_number)
                                    <div><dt>Teléfono</dt><dd>{{ $selectedContact->phone_number }}</dd></div>
                                @endif
                            </dl>
                        </section>

                        <x-chat.media-gallery :media="$sharedMedia" :audio="$sharedAudio" />

                        <section class="chat-danger-zone">
                            <p class="chat-danger-help">
                                Se ocultará solamente de tu bandeja. La otra persona conservará el chat y el historial
                                permanecerá disponible para auditoría.
                            </p>
                            <button type="button" wire:click="hideDirectConversation"
                                wire:confirm="¿Eliminar este chat sólo para ti? Los mensajes no se borrarán para la otra persona.">
                                <x-chat.icon name="trash" class="size-4" /> Eliminar chat para mí
                            </button>
                        </section>
                    </div>
                </aside>
            @endif
        </section>
    </div>

    @if ($createModalOpen)
        <div class="chat-modal-overlay" wire:click.self="closeCreateModal" role="presentation">
            <section class="chat-modal-card" role="dialog" aria-modal="true" aria-labelledby="chat-modal-title"
                wire:transition>
                <header class="chat-modal-head">
                    <div>
                        <p class="chat-eyebrow">Mensajería</p>
                        <h2 id="chat-modal-title">Nueva conversación</h2>
                    </div>
                    <button type="button" wire:click="closeCreateModal" class="chat-icon-button" aria-label="Cerrar">
                        <x-chat.icon name="x" />
                    </button>
                </header>

                <div class="chat-modal-tabs" role="tablist" aria-label="Tipo de conversación">
                    <button type="button" role="tab" aria-selected="{{ $createTab === 'contact' ? 'true' : 'false' }}"
                        wire:click="$set('createTab', 'contact')">
                        <x-chat.icon name="message" class="size-4" />Contacto
                    </button>
                    <button type="button" role="tab" aria-selected="{{ $createTab === 'group' ? 'true' : 'false' }}"
                        wire:click="$set('createTab', 'group')">
                        <x-chat.icon name="users" class="size-4" />Grupo
                    </button>
                </div>

                @if ($createTab === 'contact')
                    <section role="tabpanel" class="chat-modal-section">
                        <label class="chat-search chat-modal-search">
                            <span class="sr-only">Buscar contactos</span>
                            <x-chat.icon name="search" class="chat-search-icon" />
                            <input type="search" placeholder="Buscar personas…" class="chat-search-input"
                                wire:model.live.debounce.250ms="contactSearch" autocomplete="off">
                        </label>
                        <div class="chat-modal-list">
                            @forelse ($filteredContacts as $contact)
                                <button type="button" wire:click="selectContact({{ $contact->id }})"
                                    class="chat-modal-contact" wire:key="contact-{{ $contact->id }}">
                                    <x-chat.avatar :user="$contact" :label="$contact->name"
                                        wire:key="contact-avatar-{{ $contact->id }}" wire:ignore />
                                    <span>
                                        <strong>{{ $contact->name }}</strong>
                                        <small>{{ $contact->username ? '@' . $contact->username : $contact->email }}</small>
                                    </span>
                                </button>
                            @empty
                                <p class="chat-modal-empty">No encontramos usuarios.</p>
                            @endforelse
                        </div>
                    </section>
                @else
                    <form wire:submit="createGroup" class="chat-group-panel" role="tabpanel">
                        <div class="chat-group-photo-field">
                            <div class="chat-group-photo-preview">
                                @if ($groupPhoto)
                                    <img src="{{ $groupPhoto->temporaryUrl() }}" alt="Vista previa de la foto del grupo">
                                @else
                                    <x-chat.avatar :label="$groupName ?: 'Nuevo grupo'" size="lg" group
                                        :seed="hash('sha256', 'new-group-' . $user->id)"
                                        class="chat-avatar--preview"
                                        wire:key="new-group-avatar-{{ $user->id }}" wire:ignore />
                                @endif
                            </div>
                            <label class="chat-file-field">
                                <x-chat.icon name="camera" class="size-4" />
                                <span>{{ $groupPhoto ? 'Cambiar foto' : 'Agregar foto' }}</span>
                                <input type="file" wire:model="groupPhoto"
                                    accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only">
                            </label>
                            <small>JPG, PNG, WebP o GIF · máximo 5 MB</small>
                            @error('groupPhoto')<p class="chat-field-error" role="alert">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="chat-group-name" class="chat-form-label">Nombre del grupo</label>
                            <input id="chat-group-name" type="text" wire:model="groupName" class="chat-form-input"
                                placeholder="Ej. Equipo de diseño" maxlength="120" required>
                            @error('groupName')<p class="chat-field-error" role="alert">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="chat-group-description" class="chat-form-label">Descripción</label>
                            <textarea id="chat-group-description" wire:model="groupDescription"
                                class="chat-form-input chat-form-textarea"
                                placeholder="¿Cuál es el propósito de este grupo?" maxlength="1000"></textarea>
                            @error('groupDescription')<p class="chat-field-error" role="alert">{{ $message }}</p>@enderror
                        </div>

                        <fieldset>
                            <legend class="chat-form-label">Participantes</legend>
                            <div class="chat-form-list">
                                @foreach ($contacts as $contact)
                                    <label class="chat-check-item" wire:key="group-contact-{{ $contact->id }}">
                                        <input type="checkbox" wire:model="selectedParticipants"
                                            value="{{ $contact->id }}" class="chat-check-input">
                                        <x-chat.avatar :user="$contact" :label="$contact->name" size="sm"
                                            wire:key="group-contact-avatar-{{ $contact->id }}" wire:ignore />
                                        <span>{{ $contact->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedParticipants')<p class="chat-field-error" role="alert">{{ $message }}</p>@enderror
                        </fieldset>

                        <button type="submit" class="chat-create-group-btn" wire:loading.attr="disabled"
                            wire:target="createGroup,groupPhoto">
                            <span wire:loading.remove wire:target="createGroup">Crear grupo</span>
                            <span wire:loading wire:target="createGroup">Creando grupo…</span>
                        </button>
                    </form>
                @endif
            </section>
        </div>
    @endif
</div>
