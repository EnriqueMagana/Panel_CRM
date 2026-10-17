<div class="mx-auto grid max-w-7xl gap-7" data-module-type="dashboard">
    <header class="flex flex-col gap-5 border-b border-border pb-7 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-medium text-primary">Productividad</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Extensiones del navegador</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground">
                Añade herramientas a Snippetdesk, consulta qué hace cada una y descarga su paquete para Chrome o Edge.
            </p>
        </div>
        @can('browser_extensions.manage')
            <button type="button" wire:click="openExtensionForm"
                class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                Añadir extensión
            </button>
        @endcan
    </header>

    @if (session('browser-extension-status'))
        <div role="status" aria-live="polite" class="rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm">
            {{ session('browser-extension-status') }}
        </div>
    @endif

    <section class="grid gap-5" aria-labelledby="extension-catalog-heading">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="extension-catalog-heading" class="text-xl font-semibold">Catálogo de extensiones</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ $extensions->count() }} {{ $extensions->count() === 1 ? 'extensión disponible' : 'extensiones disponibles' }}</p>
            </div>
            <label class="relative w-full sm:max-w-sm">
                <span class="sr-only">Buscar extensiones</span>
                <svg viewBox="0 0 24 24" fill="none" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.6" /><path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o navegador…"
                    class="h-11 w-full rounded-md border border-input bg-background pl-10 pr-3 text-base outline-none placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring sm:text-sm">
            </label>
        </div>

        @if ($extensions->isNotEmpty())
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($extensions as $extension)
                    <article wire:key="browser-extension-{{ $extension->id }}"
                        class="flex min-h-80 flex-col rounded-xl border border-border bg-card p-5 text-card-foreground shadow-sm transition-shadow hover:shadow-md">
                        <div class="flex items-start justify-between gap-4">
                            <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" class="size-5"><path d="M8.5 4.5h7v4h4v7h-4v4h-7v-4h-4v-7h4v-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" /></svg>
                            </span>
                            <div class="flex flex-wrap justify-end gap-2 text-xs">
                                <span class="rounded-full bg-muted px-2.5 py-1 text-muted-foreground">v{{ $extension->version }}</span>
                                @if ($canManage)
                                    <span class="rounded-full px-2.5 py-1 {{ $extension->is_active ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-muted text-muted-foreground' }}">
                                        {{ $extension->is_active ? 'Publicada' : 'Inactiva' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <h3 class="mt-4 text-lg font-semibold">{{ $extension->name }}</h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">{{ $extension->description }}</p>

                        @if ($extension->features)
                            <ul class="mt-4 grid gap-2 text-sm">
                                @foreach (array_slice($extension->features, 0, 4) as $feature)
                                    <li class="flex gap-2 leading-5"><svg viewBox="0 0 24 24" fill="none" class="mt-0.5 size-4 shrink-0 text-primary" aria-hidden="true"><path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg><span>{{ $feature }}</span></li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-auto border-t border-border pt-5">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                                <span>{{ $extension->supported_browsers }}</span>
                                <span>{{ $extension->formattedSize() }}</span>
                            </div>
                            <div class="grid gap-2 {{ $canManage ? 'sm:grid-cols-2' : '' }}">
                                @if ($extension->is_active)
                                    <a href="{{ $extension->downloadUrl() }}" download="{{ $extension->slug }}-{{ $extension->version }}.zip"
                                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                                        Descargar ZIP
                                    </a>
                                @else
                                    <span class="inline-flex min-h-11 items-center justify-center rounded-md bg-muted px-4 text-sm text-muted-foreground">No disponible</span>
                                @endif
                                @if ($canManage)
                                    <button type="button" wire:click="toggleExtension({{ $extension->id }})"
                                        class="min-h-11 rounded-md border border-input px-4 text-sm font-medium hover:bg-accent">
                                        {{ $extension->is_active ? 'Desactivar' : 'Publicar' }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-dashed border-border bg-muted/20 px-6 py-14 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-muted text-muted-foreground" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" class="size-5"><path d="M8.5 4.5h7v4h4v7h-4v4h-7v-4h-4v-7h4v-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" /></svg></span>
                <h3 class="mt-4 font-semibold">No encontramos extensiones</h3>
                <p class="mt-1 text-sm text-muted-foreground">Prueba otra búsqueda o añade el primer paquete ZIP.</p>
            </div>
        @endif
    </section>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <details class="group rounded-xl border border-border bg-card shadow-sm" open>
            <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-semibold sm:px-6">
                Configurar una extensión en Chrome o Edge
                <svg viewBox="0 0 24 24" fill="none" class="size-5 transition-transform group-open:rotate-180" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </summary>
            <ol class="grid gap-4 border-t border-border p-5 sm:grid-cols-2 sm:p-6">
                @foreach ([
                    ['Descarga el ZIP', 'Elige una extensión del catálogo y descomprime el archivo.'],
                    ['Abre extensiones', 'Visita chrome://extensions o edge://extensions.'],
                    ['Activa modo desarrollador', 'Usa el interruptor ubicado en la parte superior.'],
                    ['Carga la carpeta', 'Pulsa “Cargar descomprimida” y selecciona la carpeta extraída.'],
                    ['Abre el panel lateral', 'Pulsa el icono de la extensión desde la barra del navegador.'],
                    ['Verifica la conexión', 'Regresa aquí y utiliza el botón Verificar extensión.'],
                ] as $index => [$title, $description])
                    <li class="grid grid-cols-[2.25rem_minmax(0,1fr)] gap-3">
                        <span class="grid size-9 place-items-center rounded-full bg-primary/10 text-sm font-semibold text-primary">{{ $index + 1 }}</span>
                        <div class="pt-1"><h3 class="text-sm font-semibold">{{ $title }}</h3><p class="mt-1 text-sm leading-6 text-muted-foreground">{{ $description }}</p></div>
                    </li>
                @endforeach
            </ol>
        </details>

        <aside class="grid content-start gap-4">
            <section class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold">Conexión del navegador</h2>
                    <span class="size-2.5 rounded-full {{ $connected ? 'bg-emerald-500' : 'bg-muted-foreground/40' }}" aria-hidden="true"></span>
                </div>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">{{ $connected ? 'La extensión está vinculada y puede pegar mensajes.' : 'Verifica la extensión para habilitar “Pegar en navegador”.' }}</p>
                @if ($connected)
                    <dl class="mt-4 grid gap-3 text-sm"><div><dt class="text-xs text-muted-foreground">Navegador</dt><dd class="mt-1 font-medium">{{ $browserName }}</dd></div><div><dt class="text-xs text-muted-foreground">Última detección</dt><dd class="mt-1 font-medium">{{ $lastSeenAt }}</dd></div></dl>
                @endif
                <button type="button" data-detect-browser-extension class="mt-5 min-h-11 w-full rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">Verificar extensión</button>
                <p data-extension-detection-status class="mt-2 min-h-5 text-xs text-muted-foreground" aria-live="polite"></p>
                @if ($connected && $canManage)
                    <button type="button" wire:click="disconnect" wire:confirm="¿Desvincular esta extensión?" class="mt-2 min-h-11 w-full rounded-md border border-input px-4 text-sm font-medium hover:bg-accent">Desvincular</button>
                @endif
            </section>
        </aside>
    </div>

    @if ($formOpen)
        <x-modal id="browser-extension-form-heading" title="Añadir extensión"
            description="Publica un paquete compatible con Chrome o Edge." close-action="cancelExtensionForm" size="2xl">
            <form wire:submit="saveExtension" class="grid gap-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2"><label for="extension-name" class="text-sm font-medium">Nombre</label><input id="extension-name" wire:model="extensionName" required maxlength="120" class="h-11 rounded-md border border-input bg-background px-3 text-base outline-none focus-visible:ring-2 focus-visible:ring-ring sm:text-sm" placeholder="Ej. Capturador de tickets">@error('extensionName')<p role="alert" class="text-sm text-destructive">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-2"><label for="extension-version" class="text-sm font-medium">Versión</label><input id="extension-version" wire:model="extensionVersion" required maxlength="30" class="h-11 rounded-md border border-input bg-background px-3 text-base outline-none focus-visible:ring-2 focus-visible:ring-ring sm:text-sm" placeholder="1.0.0">@error('extensionVersion')<p role="alert" class="text-sm text-destructive">{{ $message }}</p>@enderror</div>
                </div>
                <div class="grid gap-2"><label for="extension-description" class="text-sm font-medium">Qué hace</label><textarea id="extension-description" wire:model="extensionDescription" required maxlength="2000" rows="4" class="rounded-md border border-input bg-background px-3 py-2.5 text-base leading-6 outline-none focus-visible:ring-2 focus-visible:ring-ring sm:text-sm" placeholder="Describe el problema que resuelve y cómo ayuda al usuario."></textarea>@error('extensionDescription')<p role="alert" class="text-sm text-destructive">{{ $message }}</p>@enderror</div>
                <div class="grid gap-2"><label for="extension-features" class="text-sm font-medium">Funciones principales</label><textarea id="extension-features" wire:model="extensionFeatures" maxlength="3000" rows="4" class="rounded-md border border-input bg-background px-3 py-2.5 text-base leading-6 outline-none focus-visible:ring-2 focus-visible:ring-ring sm:text-sm" placeholder="Una función por línea"></textarea><p class="text-xs text-muted-foreground">Cada línea aparecerá como una característica en la tarjeta.</p>@error('extensionFeatures')<p role="alert" class="text-sm text-destructive">{{ $message }}</p>@enderror</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2"><label for="extension-browsers" class="text-sm font-medium">Navegadores compatibles</label><input id="extension-browsers" wire:model="extensionBrowsers" required maxlength="160" class="h-11 rounded-md border border-input bg-background px-3 text-base outline-none focus-visible:ring-2 focus-visible:ring-ring sm:text-sm">@error('extensionBrowsers')<p role="alert" class="text-sm text-destructive">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-2"><label for="extension-zip" class="text-sm font-medium">Archivo ZIP</label><input id="extension-zip" type="file" wire:model="extensionZip" accept=".zip,application/zip" required class="block min-h-11 rounded-md border border-input bg-background px-3 py-2 text-sm"><p class="text-xs text-muted-foreground">Debe incluir manifest.json en la raíz. Máximo 20 MB.</p><div wire:loading wire:target="extensionZip" class="text-xs text-muted-foreground">Validando ZIP…</div>@error('extensionZip')<p role="alert" class="text-sm text-destructive">{{ $message }}</p>@enderror</div>
                </div>
                <label class="flex min-h-11 w-fit items-center gap-3 text-sm font-medium"><input type="checkbox" wire:model="extensionActive" class="size-4 rounded border-input">Publicar inmediatamente</label>
                <footer class="flex flex-col-reverse gap-2 border-t border-border pt-5 sm:flex-row sm:justify-end"><button type="button" wire:click="cancelExtensionForm" class="min-h-11 rounded-md border border-input px-4 text-sm font-medium hover:bg-accent">Cancelar</button><button type="submit" wire:loading.attr="disabled" wire:target="saveExtension,extensionZip" class="min-h-11 rounded-md bg-primary px-5 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60"><span wire:loading.remove wire:target="saveExtension">Añadir al catálogo</span><span wire:loading wire:target="saveExtension">Guardando…</span></button></footer>
            </form>
        </x-modal>
    @endif
</div>
