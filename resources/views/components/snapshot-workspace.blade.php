@props(['page' => false])

<section data-snapshot-workspace @if ($page) data-snapshot-page @endif
    {{ $attributes->class(['grid gap-5']) }}>
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

    <div data-snapshot-list class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-live="polite"></div>

    <footer class="flex flex-col gap-3 border-t border-border pt-5 sm:flex-row sm:items-center sm:justify-between">
        <p data-snapshot-count class="text-sm text-muted-foreground">0 imágenes</p>
        <div class="flex flex-col-reverse gap-2 sm:flex-row">
            <button type="button" data-snapshot-clear disabled
                class="min-h-11 rounded-md border border-input px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-50">Limpiar</button>
            <button type="button" data-snapshot-zip disabled
                class="min-h-11 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50">Descargar ZIP</button>
        </div>
    </footer>
</section>
