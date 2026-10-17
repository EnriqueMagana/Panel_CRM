@props([
    'message',
    'library',
    'pinned' => false,
    'hidden' => false,
    'canMoveUp' => false,
    'canMoveDown' => false,
    'moveUpId' => null,
    'moveDownId' => null,
    'extensionConfigured' => false,
    'imported' => false,
])

@php($manualVariables = $message->manualVariables())

<article wire:key="preset-message-{{ $message->id }}" data-preset-message-card
    data-preset-message-id="{{ $message->id }}" data-preset-template="{{ $message->body }}"
    data-current-user="{{ auth()->user()->name }}"
    @if ($pinned) draggable="true" data-pinned-preset-id="{{ $message->id }}" @endif
    class="preset-message-card {{ $pinned ? 'is-pinned' : '' }}">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                @if ($pinned)
                    <span class="preset-pin-badge" aria-label="Mensaje fijado">
                        <svg viewBox="0 0 24 24" fill="none" class="size-3.5" aria-hidden="true">
                            <path d="m8 4 8 8m-6.5-6.5 5-1.5 5.5 5.5-1.5 5-4 1L12 18l-6-6 2.5-2.5 1-4Z"
                                stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </span>
                @endif
                <h3 class="font-semibold leading-5">{{ $message->title }}</h3>
                @if (!$message->is_active)
                    <span
                        class="rounded-full border border-border bg-muted px-2 py-0.5 text-[11px] text-muted-foreground">Inactivo</span>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1">
            @if ($pinned)
                <button type="button" wire:click="movePinned({{ $message->id }}, {{ $moveUpId ?? 0 }}, 'up')"
                    @disabled(!$canMoveUp) class="preset-card-icon-button hidden sm:grid"
                    aria-label="Mover {{ $message->title }} hacia arriba" title="Mover arriba">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                        <path d="m6 15 6-6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
                <button type="button" wire:click="movePinned({{ $message->id }}, {{ $moveDownId ?? 0 }}, 'down')"
                    @disabled(!$canMoveDown) class="preset-card-icon-button hidden sm:grid"
                    aria-label="Mover {{ $message->title }} hacia abajo" title="Mover abajo">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                        <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
            @endif

            @if ($hidden)
                <button type="button" wire:click="restoreMessage({{ $message->id }})" class="preset-card-icon-button"
                    aria-label="Restaurar {{ $message->title }}" title="Restaurar mensaje">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                        <path d="M4 8v5h5M5.4 16a8 8 0 1 0 .2-8.2L4 9" stroke="currentColor" stroke-width="1.7"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            @elseif ($library === 'mine')
                <button type="button" wire:click="togglePin({{ $message->id }})" data-preset-desktop-pin
                    class="preset-card-icon-button hidden sm:grid" aria-pressed="{{ $pinned ? 'true' : 'false' }}"
                    aria-label="{{ $pinned ? 'Desfijar' : 'Fijar' }} {{ $message->title }}"
                    title="{{ $pinned ? 'Desfijar mensaje' : 'Fijar mensaje' }}">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                        <path d="m8 4 8 8m-6.5-6.5 5-1.5 5.5 5.5-1.5 5-4 1L12 18l-6-6 2.5-2.5 1-4Z"
                            stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="m9.5 14.5-5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                </button>
            @endif

            <details class="relative">
                <summary aria-label="Acciones para {{ $message->title }}" class="preset-card-icon-button list-none">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true">
                        <circle cx="5" cy="12" r="1.5" />
                        <circle cx="12" cy="12" r="1.5" />
                        <circle cx="19" cy="12" r="1.5" />
                    </svg>
                </summary>
                <div
                    class="absolute right-0 z-20 mt-1 w-40 rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-lg">
                    @if ($hidden)
                        <button type="button" wire:click="restoreMessage({{ $message->id }})"
                            class="flex min-h-10 w-full items-center rounded-sm px-3 text-left text-sm hover:bg-accent">Restaurar</button>
                    @else
                        @if ($pinned)
                            <button type="button"
                                wire:click="movePinned({{ $message->id }}, {{ $moveUpId ?? 0 }}, 'up')"
                                @disabled(!$canMoveUp)
                                class="flex min-h-10 w-full items-center rounded-sm px-3 text-left text-sm hover:bg-accent disabled:cursor-not-allowed disabled:opacity-40 sm:hidden">
                                Mover arriba
                            </button>
                            <button type="button"
                                wire:click="movePinned({{ $message->id }}, {{ $moveDownId ?? 0 }}, 'down')"
                                @disabled(!$canMoveDown)
                                class="flex min-h-10 w-full items-center rounded-sm px-3 text-left text-sm hover:bg-accent disabled:cursor-not-allowed disabled:opacity-40 sm:hidden">
                                Mover abajo
                            </button>
                        @endif
                        @if ($library === 'mine')
                            <button type="button" wire:click="togglePin({{ $message->id }})"
                                class="flex min-h-10 w-full items-center rounded-sm px-3 text-left text-sm hover:bg-accent sm:hidden">
                                {{ $pinned ? 'Desfijar' : 'Fijar' }}
                            </button>
                        @endif
                        @if ($library === 'mine' && $message->created_by === auth()->id())
                            @can('preset_messages.update')
                                <button type="button" wire:click="editMessage({{ $message->id }})"
                                    class="flex min-h-10 w-full items-center rounded-sm px-3 text-left text-sm hover:bg-accent">Editar</button>
                            @endcan
                            @can('preset_messages.delete')
                                <button type="button" wire:click="deleteMessage({{ $message->id }})"
                                    wire:confirm="¿Eliminar el mensaje {{ $message->title }}?"
                                    class="flex min-h-10 w-full items-center rounded-sm px-3 text-left text-sm text-destructive hover:bg-accent">Eliminar</button>
                            @endcan
                        @endif
                        <button type="button" wire:click="hideMessage({{ $message->id }})"
                            class="flex min-h-10 w-full items-center rounded-sm px-3 text-left text-sm hover:bg-accent">Ocultar
                            para mí</button>
                    @endif
                </div>
            </details>
        </div>
    </div>

    <p class="preset-message-body mt-4 whitespace-pre-line text-sm leading-6 text-muted-foreground">
        {{ $message->body }}</p>

    @if (!$hidden && $manualVariables !== [])
        <div class="mt-auto grid min-w-0 gap-3 border-t border-border pt-4">
            @foreach ($manualVariables as $variable)
                @php($calculated = \App\Models\PresetMessage::calculatedVariable($variable))
                <div class="grid min-w-0 gap-1.5">
                    <label for="message-{{ $message->id }}-{{ $variable }}" class="text-xs font-medium">
                        {{ $calculated ? 'Fecha inicial para calcular 90 días' : ucfirst(str_replace('_', ' ', $variable)) }}
                    </label>
                    <input id="message-{{ $message->id }}-{{ $variable }}"
                        type="{{ $calculated['input_type'] ?? 'text' }}" data-preset-variable="{{ $variable }}"
                        @if ($calculated) data-preset-variable-calculation="{{ $calculated['calculation'] }}" @endif
                        autocomplete="off"
                        class="h-11 min-w-0 w-full rounded-md border border-input bg-background px-3 text-base outline-none focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm"
                        placeholder="{{ $calculated ? 'Selecciona la fecha de inicio' : 'Escribe ' . str_replace('_', ' ', $variable) }}">
                </div>
            @endforeach
            <div class="grid min-w-0 gap-2">
                <button type="button" data-copy-preset-message class="preset-copy-button mt-1">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                        <rect x="8" y="8" width="11" height="11" rx="2" stroke="currentColor"
                            stroke-width="1.6" />
                        <path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" stroke="currentColor"
                            stroke-width="1.6" />
                    </svg>
                    <span data-copy-label>Generar y copiar</span>
                </button>
                @if ($extensionConfigured)
                    <button type="button" data-paste-preset-message class="preset-copy-button mt-1">
                        <span data-paste-label>Pegar en navegador</span>
                    </button>
                @endif
            </div>
            <p data-preset-error class="hidden text-xs text-destructive" role="alert">Completa todas las variables
                para generar el mensaje.</p>
        </div>
    @elseif (!$hidden)
        <div class="mt-auto flex min-w-0 flex-wrap items-center gap-3 pt-4">
            <button type="button" data-copy-preset-message class="preset-copy-link mt-0">
                <span data-copy-label>Copiar mensaje</span>
                <svg viewBox="0 0 24 24" fill="none" class="size-4 shrink-0" aria-hidden="true">
                    <rect x="8" y="8" width="11" height="11" rx="2" stroke="currentColor"
                        stroke-width="1.6" />
                    <path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" stroke="currentColor"
                        stroke-width="1.6" />
                </svg>
            </button>
            @if ($extensionConfigured)
                <button type="button" data-paste-preset-message class="preset-copy-link mt-0">
                    <span data-paste-label>Pegar en navegador</span>
                </button>
            @endif
        </div>
    @endif

    @if (($library === 'global' || $hidden) && $message->creator)
        <footer class="mt-4 grid gap-3 border-t border-border pt-3">
            <div class="flex min-w-0 items-center gap-2.5">
                <x-user-avatar :user="$message->creator" size="size-8" :pixels="32"
                    alt="Avatar de {{ $message->creator->name }}" loading="lazy" class="rounded-full" />
                <p class="min-w-0 text-xs leading-5 text-muted-foreground">
                    Compartido por
                    <span class="font-semibold text-foreground">{{ $message->creator->name }}</span>
                </p>
            </div>

            @if ($library === 'global')
                @can('preset_messages.create')
                    @if ($imported)
                        <button type="button" disabled aria-label="Este mensaje ya fue importado"
                            class="inline-flex min-h-11 w-full cursor-default items-center justify-center gap-2 rounded-md border border-emerald-500/25 bg-emerald-500/10 px-3 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                            <svg viewBox="0 0 24 24" fill="none" class="size-4 shrink-0" aria-hidden="true">
                                <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Ya importado
                        </button>
                    @else
                        <button type="button" wire:click="importMessage({{ $message->id }})" wire:loading.attr="disabled"
                            wire:target="importMessage({{ $message->id }})"
                            class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-md border border-border bg-background px-3 text-sm font-semibold text-foreground transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40 disabled:cursor-wait disabled:opacity-60">
                            <svg viewBox="0 0 24 24" fill="none" class="size-4 shrink-0" aria-hidden="true">
                                <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14" stroke="currentColor" stroke-width="1.7"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span wire:loading.remove wire:target="importMessage({{ $message->id }})">Importar a Mis mensajes</span>
                            <span wire:loading wire:target="importMessage({{ $message->id }})">Importando…</span>
                        </button>
                    @endif
                @endcan
            @endif
        </footer>
    @endif

    <p data-copy-status class="sr-only" aria-live="polite"></p>
</article>
