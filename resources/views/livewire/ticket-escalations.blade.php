<div class="space-y-6">
    @if (session('ticket-escalations-status'))
        <div role="status" aria-live="polite"
            class="flex items-center gap-2 rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
            <svg viewBox="0 0 24 24" fill="none" class="size-4 shrink-0" aria-hidden="true">
                <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            {{ session('ticket-escalations-status') }}
        </div>
    @endif

    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-primary">Mesa de ayuda</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Escalado de tickets</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                Centraliza los casos escalados para que el equipo consulte su contexto, responsable y estado actual.
            </p>
        </div>

        @can('ticket_escalations.create')
            <button type="button"
                wire:click="$dispatchTo('ticket-escalation-form', 'open-ticket-escalation-form')" aria-haspopup="dialog"
                class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                Nuevo escalado
            </button>
        @endcan
    </header>

    <section aria-label="Filtros de escalados"
        class="grid gap-3 rounded-xl border border-border bg-card p-3 shadow-sm sm:grid-cols-[minmax(0,1fr)_14rem] sm:p-4">
        <div class="relative">
            <label for="ticket-escalation-search" class="sr-only">Buscar por folio, título o descripción</label>
            <svg viewBox="0 0 24 24" fill="none"
                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true">
                <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.6" />
                <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
            <input id="ticket-escalation-search" type="search" wire:model.live.debounce.350ms="search"
                placeholder="Buscar folio, título o descripción..." autocomplete="off"
                class="h-11 w-full rounded-md border border-input bg-background pl-10 pr-10 text-base outline-none placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
            <span wire:loading wire:target="search"
                class="absolute right-3.5 top-1/2 size-4 -translate-y-1/2 animate-spin rounded-full border-2 border-muted-foreground/30 border-t-foreground"
                aria-label="Buscando"></span>
        </div>

        <div>
            <label for="ticket-status-filter" class="sr-only">Filtrar por estatus</label>
            <select id="ticket-status-filter" wire:model.live="statusFilter"
                class="h-11 w-full rounded-md border border-input bg-background px-3 text-base outline-none focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                <option value="all">Todos los estatus</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </section>

    <div class="flex min-h-6 items-center justify-between gap-3 text-sm text-muted-foreground">
        <p>{{ $escalations->total() }} {{ $escalations->total() === 1 ? 'escalado' : 'escalados' }}</p>
        <p wire:loading wire:target="statusFilter" class="text-xs">Actualizando...</p>
    </div>

    <section aria-label="Tickets escalados" class="grid gap-3">
        @forelse ($escalations as $escalation)
            <x-ticket-escalations.card :escalation="$escalation" :comment-uploads="$commentUploads" />
        @empty
            <div class="flex min-h-72 flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/15 px-6 py-12 text-center">
                <span class="grid size-12 place-items-center rounded-full border border-border bg-background text-muted-foreground" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5">
                        <path d="M4.5 7.5h15A1.5 1.5 0 0 1 21 9v2a2.5 2.5 0 0 0 0 5v2a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18v-2a2.5 2.5 0 0 0 0-5V9a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" />
                    </svg>
                </span>
                <h2 class="mt-4 font-semibold">{{ $search !== '' || $statusFilter !== 'all' ? 'No encontramos coincidencias' : 'Aún no hay tickets escalados' }}</h2>
                <p class="mt-1 max-w-md text-sm leading-6 text-muted-foreground">
                    {{ $search !== '' || $statusFilter !== 'all' ? 'Prueba con otro folio, palabra o estatus.' : 'Cuando el equipo registre el primer escalado, aparecerá aquí para todos.' }}
                </p>
            </div>
        @endforelse
    </section>

    @if ($escalations->hasPages())
        <div class="pt-2">{{ $escalations->links() }}</div>
    @endif

</div>
