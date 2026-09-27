<div class="mx-auto w-full max-w-5xl space-y-8">
    <header class="border-b border-border pb-6">
        <div class="flex items-start gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl border border-border bg-card text-card-foreground shadow-sm"
                aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" class="size-6">
                    <path d="M14.7 6.3a4.5 4.5 0 0 0-5.8 5.8L3.5 17.5a2.1 2.1 0 1 0 3 3l5.4-5.4a4.5 4.5 0 0 0 5.8-5.8l-2.8 2.8-3-3 2.8-2.8Z"
                        stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <div>
                <p class="text-sm font-medium text-muted-foreground">Configuración del sistema</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Centro técnico</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                    Define reglas globales de funcionamiento. Los cambios se aplican sin recargar la página.
                </p>
            </div>
        </div>
    </header>

    <section aria-labelledby="chat-policy-heading" class="overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-sm">
        <div class="border-b border-border px-5 py-5 sm:px-6">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-primary text-primary-foreground" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5">
                        <path d="M7 18.5 3.5 21V6.5A2.5 2.5 0 0 1 6 4h12a2.5 2.5 0 0 1 2.5 2.5v9A2.5 2.5 0 0 1 18 18H7Z"
                            stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                        <path d="M8 9.5h8M8 13h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                </span>
                <div>
                    <h2 id="chat-policy-heading" class="text-lg font-semibold">Historial para nuevos participantes</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground">
                        Elige qué podrá consultar una persona cuando sea incorporada a un grupo que ya tiene mensajes.
                    </p>
                </div>
            </div>
        </div>

        <form wire:submit="saveChatSettings" class="space-y-6 px-5 py-6 sm:px-6">
            <fieldset class="space-y-3">
                <legend class="text-sm font-medium">Visibilidad del historial</legend>

                @foreach ($historyOptions as $option)
                    <label @class([
                        'group flex min-h-24 cursor-pointer items-start gap-4 rounded-xl border p-4 transition-[border-color,background-color,box-shadow] duration-200',
                        'border-primary bg-primary/5 shadow-sm' => $chatHistoryVisibility === $option->value,
                        'border-border hover:border-foreground/30 hover:bg-accent/50' => $chatHistoryVisibility !== $option->value,
                    ])>
                        <input type="radio" wire:model.live="chatHistoryVisibility" value="{{ $option->value }}"
                            class="mt-1 size-4 shrink-0 accent-primary" />
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold">{{ $option->label() }}</span>
                            <span class="mt-1 block text-sm leading-6 text-muted-foreground">{{ $option->description() }}</span>
                        </span>
                    </label>
                @endforeach

                @error('chatHistoryVisibility')
                    <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="rounded-lg border border-border bg-muted/40 p-4 text-sm leading-6">
                <div class="flex items-start gap-3">
                    <svg viewBox="0 0 24 24" fill="none" class="mt-0.5 size-5 shrink-0 text-muted-foreground" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" />
                        <path d="M12 10.5V17M12 7.25v.25" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                    <p>
                        La regla se guarda al momento de agregar a cada participante. Modificarla después no cambia el acceso histórico de personas que ya pertenecen a un grupo.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-border pt-5">
                @can('technical_center.manage')
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveChatSettings"
                        class="inline-flex min-h-11 items-center justify-center rounded-md bg-primary px-5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-wait disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveChatSettings">Guardar configuración</span>
                        <span wire:loading wire:target="saveChatSettings">Guardando…</span>
                    </button>
                @endcan

                @if ($saved)
                    <p role="status" aria-live="polite" class="inline-flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                        <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                            <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Configuración guardada.
                    </p>
                @endif
            </div>
        </form>
    </section>
</div>
