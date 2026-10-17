@props(['escalation', 'commentUploads' => []])

<article class="overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-sm transition-shadow hover:shadow-md"
    wire:key="ticket-escalation-{{ $escalation->id }}">
    <details wire:ignore.self class="group">
        <summary class="grid min-h-24 cursor-pointer list-none gap-4 px-4 py-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-5 [&::-webkit-details-marker]:hidden">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ticket-escalations.status-badge :status="$escalation->status" />
                    <span class="font-mono text-xs font-semibold tracking-wide text-muted-foreground">{{ $escalation->folio }}</span>
                </div>
                <h2 class="mt-2 truncate text-base font-semibold sm:text-lg">{{ $escalation->title }}</h2>
            </div>

            <div class="flex items-center justify-between gap-4 sm:justify-end">
                <div class="flex min-w-0 items-center gap-2.5">
                    <x-user-avatar wire:ignore :user="$escalation->creator" size="size-9" :pixels="36" :alt="$escalation->creator->name"
                        :sync="true"
                        class="rounded-full ring-1 ring-border" />
                    <div class="min-w-0 sm:max-w-44">
                        <p class="truncate text-sm font-medium">{{ $escalation->creator->name }}</p>
                        <p class="text-xs text-muted-foreground">{{ $escalation->escalated_at->format('d/m/Y') }}</p>
                    </div>
                </div>
                <span class="grid size-11 shrink-0 place-items-center rounded-full border border-border bg-background text-muted-foreground transition-transform duration-200 group-open:rotate-180" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4">
                        <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            </div>
        </summary>

        <div class="border-t border-border bg-muted/15 px-4 py-5 sm:px-5">
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_16rem]">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Descripción completa</h3>
                    <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-6">{{ $escalation->description }}</p>
                    @if ($escalation->ticketAttachments->isNotEmpty())
                        <h4 class="mt-5 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Archivos del ticket</h4>
                        <x-ticket-escalations.attachment-list :attachments="$escalation->ticketAttachments" class="mt-2" />
                    @endif
                </div>

                <aside class="rounded-lg border border-border bg-background p-4 text-sm">
                    <dl class="grid gap-3">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Área escalada</dt>
                            <dd class="text-right font-medium">{{ $escalation->escalated_area }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Registrado por</dt>
                            <dd class="text-right font-medium">{{ $escalation->creator->name }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Fecha</dt>
                            <dd class="font-medium">{{ $escalation->escalated_at->format('d/m/Y') }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">Actualizado</dt>
                            <dd class="text-right font-medium">{{ $escalation->updated_at->diffForHumans() }}</dd>
                        </div>
                    </dl>
                </aside>
            </div>

            @if ($escalation->created_by === auth()->id())
                <div class="mt-5 flex flex-col gap-2 border-t border-border pt-4 sm:flex-row sm:justify-end">
                    @can('ticket_escalations.update')
                        <button type="button"
                            wire:click="$dispatchTo('ticket-escalation-form', 'open-ticket-escalation-form', { escalationId: {{ $escalation->id }} })"
                            class="inline-flex min-h-11 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            Editar
                        </button>
                    @endcan
                    @can('ticket_escalations.delete')
                        <button type="button" data-confirm-action
                            data-confirm-message="¿Eliminar el escalado {{ $escalation->folio }}? Esta acción no se puede deshacer."
                            wire:click="deleteEscalation({{ $escalation->id }})"
                            class="inline-flex min-h-11 items-center justify-center rounded-md px-4 text-sm font-medium text-destructive transition-colors hover:bg-destructive/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            Eliminar
                        </button>
                    @endcan
                </div>
            @endif

            <x-ticket-escalations.comments :escalation="$escalation" :comment-uploads="$commentUploads" />
        </div>
    </details>
</article>
