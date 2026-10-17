<div>
    @if ($open)
        <x-modal id="ticket-quick-search-heading" title="Buscar ticket escalado"
            description="Consulta la información, evidencias y seguimiento sin salir de tus mensajes." close-action="closeSearch" size="3xl">
            <div class="grid gap-5">
                <div class="relative">
                    <label for="ticket-quick-search-input" class="sr-only">Buscar por folio, título, área o descripción</label>
                    <svg viewBox="0 0 24 24" fill="none"
                        class="pointer-events-none absolute left-3.5 top-1/2 size-5 -translate-y-1/2 text-muted-foreground" aria-hidden="true">
                        <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.6" />
                        <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                    <input id="ticket-quick-search-input" type="search" wire:model.live.debounce.300ms="search"
                        autofocus autocomplete="off" placeholder="Escribe un folio, título, área o descripción..."
                        class="h-12 w-full rounded-lg border border-input bg-background pl-11 pr-11 text-base outline-none placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring/30">
                    <span wire:loading wire:target="search"
                        class="absolute right-4 top-1/2 size-4 -translate-y-1/2 animate-spin rounded-full border-2 border-muted-foreground/30 border-t-foreground"
                        aria-label="Buscando"></span>
                </div>

                <div class="grid min-h-96 gap-5 lg:grid-cols-[20rem_minmax(0,1fr)]">
                    <section aria-label="Resultados de tickets" class="min-w-0 rounded-xl border border-border bg-muted/15 p-2">
                        <div class="flex items-center justify-between gap-2 px-2 py-2">
                            <h2 class="text-sm font-semibold">{{ trim($search) === '' ? 'Tickets recientes' : 'Resultados' }}</h2>
                            <span class="text-xs text-muted-foreground">{{ $tickets->count() }}</span>
                        </div>

                        @if (trim($search) !== '' && mb_strlen(trim($search)) < 2)
                            <p class="rounded-lg border border-dashed border-border bg-background px-4 py-8 text-center text-sm text-muted-foreground">
                                Escribe al menos 2 caracteres para buscar.
                            </p>
                        @elseif ($tickets->isEmpty())
                            <p class="rounded-lg border border-dashed border-border bg-background px-4 py-8 text-center text-sm text-muted-foreground">
                                No encontramos tickets con esos datos.
                            </p>
                        @else
                            <div class="grid max-h-[28rem] gap-1 overflow-y-auto pr-1">
                                @foreach ($tickets as $ticket)
                                    <button type="button" wire:click="selectTicket({{ $ticket->id }})"
                                        wire:key="quick-ticket-{{ $ticket->id }}"
                                        aria-pressed="{{ $selectedTicketId === $ticket->id ? 'true' : 'false' }}"
                                        class="grid min-h-20 w-full gap-2 rounded-lg border px-3 py-3 text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $selectedTicketId === $ticket->id ? 'border-primary/40 bg-primary/10' : 'border-transparent bg-background hover:border-border hover:bg-accent' }}">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-xs font-semibold text-muted-foreground">{{ $ticket->folio }}</span>
                                            <x-ticket-escalations.status-badge :status="$ticket->status" />
                                        </span>
                                        <span class="truncate text-sm font-semibold">{{ $ticket->title }}</span>
                                        <span class="truncate text-xs text-muted-foreground">{{ $ticket->escalated_area }} · {{ $ticket->creator->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section aria-live="polite" class="min-w-0">
                        @if ($selectedTicket)
                            <article wire:key="quick-ticket-detail-{{ $selectedTicket->id }}" class="grid gap-6">
                                <header class="flex flex-col gap-4 border-b border-border pb-5 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-ticket-escalations.status-badge :status="$selectedTicket->status" />
                                            <span class="font-mono text-sm font-semibold text-muted-foreground">{{ $selectedTicket->folio }}</span>
                                        </div>
                                        <h2 class="mt-2 break-words text-xl font-bold sm:text-2xl">{{ $selectedTicket->title }}</h2>
                                        <p class="mt-1 text-sm text-muted-foreground">Área: <span class="font-medium text-foreground">{{ $selectedTicket->escalated_area }}</span></p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-3 rounded-lg border border-border bg-muted/20 p-3">
                                        <x-user-avatar wire:ignore :user="$selectedTicket->creator" size="size-10" :pixels="40"
                                            :alt="$selectedTicket->creator->name" :sync="true" class="rounded-full ring-1 ring-border" />
                                        <div>
                                            <p class="text-sm font-semibold">{{ $selectedTicket->creator->name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ $selectedTicket->escalated_at->format('d/m/Y') }}</p>
                                        </div>
                                    </div>
                                </header>

                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Descripción del ticket</h3>
                                    <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-6">{{ $selectedTicket->description }}</p>
                                </div>

                                <section aria-labelledby="quick-ticket-evidence-{{ $selectedTicket->id }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 id="quick-ticket-evidence-{{ $selectedTicket->id }}" class="font-semibold">Evidencia del ticket</h3>
                                        <span class="text-xs text-muted-foreground">{{ $selectedTicket->ticketAttachments->count() }} archivos</span>
                                    </div>
                                    @if ($selectedTicket->ticketAttachments->isNotEmpty())
                                        <x-ticket-escalations.attachment-list :attachments="$selectedTicket->ticketAttachments" class="mt-3" />
                                    @else
                                        <p class="mt-3 rounded-lg border border-dashed border-border bg-muted/15 px-4 py-5 text-center text-sm text-muted-foreground">Este ticket no tiene evidencia adjunta.</p>
                                    @endif
                                </section>

                                <section aria-labelledby="quick-ticket-comments-{{ $selectedTicket->id }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 id="quick-ticket-comments-{{ $selectedTicket->id }}" class="font-semibold">Comentarios y seguimiento</h3>
                                        <span class="text-xs text-muted-foreground">{{ $selectedTicket->comments->count() }} comentarios</span>
                                    </div>
                                    @if ($selectedTicket->comments->isNotEmpty())
                                        <ol class="mt-4 grid gap-3">
                                            @foreach ($selectedTicket->comments as $comment)
                                                <li class="flex gap-3" wire:key="quick-ticket-comment-{{ $comment->id }}">
                                                    <x-user-avatar wire:ignore :user="$comment->user" size="size-9" :pixels="36"
                                                        :alt="$comment->user->name" :sync="true" class="rounded-full ring-1 ring-border" />
                                                    <div class="min-w-0 flex-1 rounded-lg border border-border bg-muted/15 p-3">
                                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                                            <p class="text-sm font-semibold">{{ $comment->user->name }}</p>
                                                            <time datetime="{{ $comment->created_at->toIso8601String() }}" class="text-xs text-muted-foreground">{{ $comment->created_at->format('d/m/Y H:i') }}</time>
                                                        </div>
                                                        @if (filled($comment->body))
                                                            <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-6">{{ $comment->body }}</p>
                                                        @endif
                                                        <x-ticket-escalations.attachment-list :attachments="$comment->attachments" class="mt-3" />
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ol>
                                    @else
                                        <p class="mt-3 rounded-lg border border-dashed border-border bg-muted/15 px-4 py-5 text-center text-sm text-muted-foreground">Aún no hay comentarios de seguimiento.</p>
                                    @endif
                                </section>
                            </article>
                        @else
                            <div class="flex min-h-96 flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/10 px-6 py-12 text-center">
                                <span class="grid size-12 place-items-center rounded-full border border-border bg-background text-muted-foreground" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" class="size-5"><path d="M4.5 7.5h15A1.5 1.5 0 0 1 21 9v2a2.5 2.5 0 0 0 0 5v2a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18v-2a2.5 2.5 0 0 0 0-5V9a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" /></svg>
                                </span>
                                <h2 class="mt-4 font-semibold">Selecciona un ticket</h2>
                                <p class="mt-1 max-w-sm text-sm leading-6 text-muted-foreground">Consulta aquí toda su información, evidencias y comentarios sin cambiar de página.</p>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </x-modal>
    @endif
</div>
