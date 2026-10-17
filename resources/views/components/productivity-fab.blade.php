@php
    $fabModuleEnabled = function (string $routeName): bool {
        $items = \App\Models\SidebarItem::query()->with('parent:id,is_active')->where('route_name', $routeName)->get();

        return $items->isEmpty() || $items->contains(
            fn ($item) => $item->is_active && ($item->parent_id === null || $item->parent?->is_active),
        );
    };
    $chatEnabled = $fabModuleEnabled('chats');
    $extensionEnabled = $fabModuleEnabled('browser-extension');
    $ticketEscalationsEnabled = $fabModuleEnabled('ticket-escalations');
    $fabUser = auth()->user();
@endphp

<div data-productivity-fab data-state="closed" class="fixed bottom-5 right-5 z-40 flex flex-col items-end gap-3 sm:bottom-7 sm:right-7">
    <div data-fab-menu id="productivity-fab-menu"
        data-state="closed"
        class="hidden w-56 origin-bottom-right translate-y-3 scale-95 flex-col gap-2 rounded-xl border border-border bg-popover p-2 text-popover-foreground opacity-0 shadow-xl transition-[opacity,transform] duration-200 ease-out motion-reduce:transition-none"
        aria-label="Acciones rápidas">
        @can('preset_messages.create')
            <button type="button" data-fab-create-message
                class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-medium transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <svg viewBox="0 0 24 24" fill="none" class="size-5 text-muted-foreground" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                Agregar mensaje
            </button>
        @endcan

        @can('ticket_escalations.view')
            @if ($ticketEscalationsEnabled)
                <button type="button" data-fab-ticket-search
                    class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-medium transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5 text-muted-foreground" aria-hidden="true">
                        <path d="M4.5 7.5h15A1.5 1.5 0 0 1 21 9v2a2.5 2.5 0 0 0 0 5v2a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18v-2a2.5 2.5 0 0 0 0-5V9a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                        <circle cx="13.5" cy="12.5" r="2.5" stroke="currentColor" stroke-width="1.6" />
                        <path d="m15.4 14.4 2.1 2.1M8 7.5v12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                    Buscar ticket
                </button>
            @endif
        @endcan

        @can('browser_extensions.view')
            @if ($extensionEnabled)
            <a href="{{ route('browser-extension') }}" wire:navigate data-fab-extensions
                class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-medium transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <svg viewBox="0 0 24 24" fill="none" class="size-5 text-muted-foreground" aria-hidden="true">
                    <path d="M8.5 4.5h7v4h4v7h-4v4h-7v-4h-4v-7h4v-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                </svg>
                Extensiones
            </a>
            @endif
        @endcan

        @if ($chatEnabled)
            <a href="{{ route('chats') }}" wire:navigate data-fab-chat
                class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-medium transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <svg viewBox="0 0 24 24" fill="none" class="size-5 text-muted-foreground" aria-hidden="true">
                    <path d="M5 5h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9l-5 4v-4.5A2 2 0 0 1 3 15V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                </svg>
                Chat
            </a>
        @endif

        <button type="button" data-fab-snapshot
            class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-medium transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <svg viewBox="0 0 24 24" fill="none" class="size-5 text-muted-foreground" aria-hidden="true">
                <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6H9l1.2-1.5h3.6L15 6h2.5A2.5 2.5 0 0 1 20 8.5v8A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                <circle cx="12" cy="12.5" r="3" stroke="currentColor" stroke-width="1.6" />
            </svg>
            Snapshot
        </button>
    </div>

    <button type="button" data-fab-toggle data-fab-expression="{{ $fabUser->avatar_preferences['expression'] ?? 'idle' }}" aria-controls="productivity-fab-menu" aria-expanded="false"
        aria-label="Abrir acciones rápidas"
        class="productivity-fab-toggle relative grid size-16 place-items-center overflow-visible rounded-full border border-border bg-card text-card-foreground shadow-lg transition-[transform,box-shadow,border-color] duration-200 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 motion-reduce:transition-none">
        <span class="productivity-fab-avatar-motion grid size-14 place-items-center" aria-hidden="true">
            <span data-fab-avatar-shell class="productivity-fab-avatar-shell relative grid size-12 place-items-center rounded-full border-2 border-primary/25 bg-background p-0.5 shadow-sm">
                <x-user-avatar :user="$fabUser" size="size-full" :pixels="48" alt="" animate="always"
                    :sync="true" class="rounded-full" />
                <span class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full border-2 border-card bg-emerald-500"></span>
            </span>
        </span>
    </button>
</div>

<dialog data-snapshot-dialog aria-labelledby="snapshot-dialog-title"
    class="fixed inset-0 m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-5xl overflow-y-auto rounded-xl border border-border bg-background p-0 text-foreground shadow-2xl backdrop:bg-black/50">
    <header class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-border bg-background px-5 py-4 sm:px-6">
        <div>
            <h2 id="snapshot-dialog-title" class="text-lg font-semibold">Snapshot</h2>
            <p class="mt-1 text-sm text-muted-foreground">Pega imágenes, nómbralas y arrástralas hacia otra herramienta.</p>
        </div>
        <button type="button" data-close-snapshot aria-label="Cerrar Snapshot"
            class="grid size-11 shrink-0 place-items-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
        </button>
    </header>

    <div class="grid gap-5 p-5 sm:p-6">
        <div data-snapshot-dropzone tabindex="0"
            class="rounded-xl border border-dashed border-border bg-muted/20 px-5 py-7 text-center outline-none transition-colors focus-visible:ring-2 focus-visible:ring-ring data-[dragging=true]:border-primary data-[dragging=true]:bg-primary/5">
            <svg viewBox="0 0 24 24" fill="none" class="mx-auto size-7 text-muted-foreground" aria-hidden="true">
                <path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <p class="mt-3 text-sm font-medium">Pega con Ctrl+V o arrastra imágenes aquí</p>
            <p class="mt-1 text-xs leading-5 text-muted-foreground">Las imágenes permanecen únicamente en este navegador durante la sesión.</p>
            <label class="mt-4 inline-flex min-h-11 cursor-pointer items-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent focus-within:ring-2 focus-within:ring-ring">
                Seleccionar imágenes
                <input data-snapshot-input type="file" accept="image/*" multiple class="sr-only">
            </label>
        </div>

        <div data-snapshot-empty class="rounded-xl border border-border bg-card px-5 py-10 text-center">
            <p class="text-sm font-medium">Todavía no hay imágenes</p>
            <p class="mt-1 text-xs text-muted-foreground">Copia una captura y pégala dentro de este panel.</p>
        </div>

        <div data-snapshot-list class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-live="polite"></div>

        <footer class="flex flex-col gap-3 border-t border-border pt-5 sm:flex-row sm:items-center sm:justify-between">
            <p data-snapshot-count class="text-sm text-muted-foreground">0 imágenes</p>
            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <button type="button" data-snapshot-clear disabled
                    class="min-h-11 rounded-md border border-input px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-50">Limpiar</button>
                <button type="button" data-snapshot-zip disabled
                    class="min-h-11 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50">
                    Descargar ZIP
                </button>
            </div>
        </footer>
    </div>
</dialog>
