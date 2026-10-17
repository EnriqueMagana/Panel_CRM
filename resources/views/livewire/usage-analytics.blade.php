<section class="grid gap-5" aria-labelledby="usage-analytics-heading">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-primary">Productividad</p>
            <h2 id="usage-analytics-heading" class="mt-1 text-2xl font-semibold tracking-tight">Analíticas de uso</h2>
            <p class="mt-1 text-sm text-muted-foreground">Descubre qué plantillas ayudan más y dónde falta contenido.</p>
        </div>
        <a href="{{ route('preset-messages') }}" wire:navigate
            class="inline-flex min-h-11 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent">
            Abrir mensajes
        </a>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Mensajes copiados', 'value' => number_format($total_copies), 'hint' => 'Copias y pegados desde la extensión'],
            ['label' => 'Tiempo ahorrado', 'value' => $timeSavedLabel, 'hint' => 'Estimado frente a escribir manualmente'],
            ['label' => 'Búsquedas sin resultados', 'value' => number_format($searches_without_results), 'hint' => 'Oportunidades para crear plantillas'],
            ['label' => 'Plantillas abandonadas', 'value' => number_format($abandoned_templates), 'hint' => 'Activas y todavía sin uso'],
        ] as $metric)
            <article class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-muted-foreground">{{ $metric['label'] }}</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight">{{ $metric['value'] }}</p>
                <p class="mt-2 text-xs leading-5 text-muted-foreground">{{ $metric['hint'] }}</p>
            </article>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <h3 class="font-semibold">Mensajes más copiados</h3>
            <p class="mt-1 text-sm text-muted-foreground">Tus cinco plantillas más utilizadas.</p>
            <ol class="mt-4 grid gap-2">
                @forelse ($top_messages as $message)
                    <li class="flex min-h-11 items-center justify-between gap-4 rounded-lg bg-muted/40 px-3">
                        <span class="min-w-0 truncate text-sm font-medium">{{ $message->title ?? 'Mensaje eliminado' }}</span>
                        <span class="shrink-0 rounded-full bg-background px-2 py-1 text-xs text-muted-foreground">{{ $message->copies }} copias</span>
                    </li>
                @empty
                    <li class="rounded-lg border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground">Las copias comenzarán a aparecer aquí.</li>
                @endforelse
            </ol>
        </article>

        <article class="rounded-xl border border-border bg-card p-5 shadow-sm">
            <h3 class="font-semibold">Búsquedas sin resultados</h3>
            <p class="mt-1 text-sm text-muted-foreground">Úsalas como lista de nuevas plantillas por crear.</p>
            <ol class="mt-4 grid gap-2">
                @forelse ($top_searches as $search)
                    <li class="flex min-h-11 items-center justify-between gap-4 rounded-lg bg-muted/40 px-3">
                        <span class="min-w-0 truncate text-sm font-medium">“{{ $search->search_query }}”</span>
                        <span class="shrink-0 rounded-full bg-background px-2 py-1 text-xs text-muted-foreground">{{ $search->searches }} veces</span>
                    </li>
                @empty
                    <li class="rounded-lg border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground">No hay búsquedas pendientes de atender.</li>
                @endforelse
            </ol>
        </article>
    </div>
</section>
