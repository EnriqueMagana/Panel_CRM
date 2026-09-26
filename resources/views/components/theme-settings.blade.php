<dialog data-theme-settings aria-labelledby="theme-settings-title" aria-describedby="theme-settings-description"
    class="fixed inset-y-0 right-0 m-0 ml-auto h-dvh max-h-dvh w-full max-w-md border-0 border-l border-border bg-background p-0 text-foreground shadow-2xl backdrop:bg-black/50">
    <div class="flex h-full flex-col">
        <header class="flex items-start justify-between gap-4 border-b border-border px-5 py-4">
            <div>
                <h2 id="theme-settings-title" class="text-lg font-semibold">Ajustes del tema</h2>
                <p id="theme-settings-description" class="mt-1 text-sm leading-5 text-muted-foreground">Ajusta la
                    apariencia y la distribución a tus preferencias.</p>
            </div>
            <button type="button" data-close-theme-settings aria-label="Cerrar ajustes del tema"
                class="grid size-10 shrink-0 place-items-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground">
                <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                </svg>
            </button>
        </header>

        <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-5 py-5">
            <section aria-labelledby="theme-options-heading">
                <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                    <h3 id="theme-options-heading">Tema</h3>
                    <button type="button" data-reset-theme aria-label="Restablecer tema"
                        class="grid size-6 place-items-center rounded-full text-muted-foreground hover:bg-accent"><svg
                            viewBox="0 0 24 24" fill="none" class="size-3.5" aria-hidden="true">
                            <path d="M4 11a8 8 0 1 1 2.3 5.7M4 5v6h6" stroke="currentColor" stroke-width="1.7"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></button>
                </div>
                <div class="grid grid-cols-3 gap-3" role="radiogroup" aria-label="Seleccionar tema">
                    @foreach ([['system', 'Sistema'], ['light', 'Claro'], ['dark', 'Oscuro']] as [$value, $label])
                        <label class="group relative cursor-pointer text-center">
                            <input type="radio" name="theme-setting" value="{{ $value }}"
                                data-theme-value="{{ $value }}" class="peer sr-only">
                            <span
                                class="absolute -right-1 -top-1 z-10 hidden size-5 place-items-center rounded-full bg-primary text-primary-foreground peer-checked:grid"><svg
                                    viewBox="0 0 24 24" fill="none" class="size-3" aria-hidden="true">
                                    <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span>
                            <span
                                class="grid aspect-[1.45] grid-cols-[28%_1fr] gap-1 overflow-hidden rounded-md border border-border p-1 transition peer-checked:border-primary peer-checked:ring-1 peer-checked:ring-primary peer-focus-visible:outline-2 peer-focus-visible:outline-ring {{ $value === 'dark' ? 'bg-slate-950' : 'bg-slate-100' }}">
                                <span
                                    class="grid content-start gap-1 rounded-sm bg-slate-300 p-1 {{ $value === 'dark' ? 'bg-slate-800' : '' }}"><span
                                        class="h-1 w-3 rounded bg-slate-500 {{ $value === 'dark' ? 'bg-slate-500' : '' }}"></span><span
                                        class="h-1 w-4 rounded bg-slate-400"></span><span
                                        class="h-1 w-2 rounded bg-slate-400"></span></span>
                                <span class="grid content-start gap-1 p-1"><span
                                        class="h-1 w-8 rounded bg-slate-400"></span><span
                                        class="grid grid-cols-2 gap-1"><span
                                            class="h-3 rounded-sm bg-slate-300 {{ $value === 'dark' ? 'bg-slate-800' : '' }}"></span><span
                                            class="h-3 rounded-sm bg-slate-300 {{ $value === 'dark' ? 'bg-slate-800' : '' }}"></span></span><span
                                        class="h-3 rounded-sm bg-white {{ $value === 'dark' ? 'bg-slate-800' : '' }}"></span></span>
                            </span>
                            <span class="mt-1 block text-xs">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="sidebar-options-heading" class="max-md:hidden">
                <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                    <h3 id="sidebar-options-heading">Sidebar</h3>
                    <button type="button" data-reset-sidebar aria-label="Restablecer estilo del sidebar"
                        class="grid size-6 place-items-center rounded-full hover:bg-accent"><svg viewBox="0 0 24 24"
                            fill="none" class="size-3.5" aria-hidden="true">
                            <path d="M4 11a8 8 0 1 1 2.3 5.7M4 5v6h6" stroke="currentColor" stroke-width="1.7"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></button>
                </div>
                <div class="grid grid-cols-3 gap-3" role="radiogroup" aria-label="Seleccionar estilo del sidebar">
                    @foreach ([['inset', 'Inset'], ['floating', 'Flotante'], ['sidebar', 'Sidebar']] as [$value, $label])
                        <label class="group relative cursor-pointer text-center">
                            <input type="radio" name="sidebar-setting" value="{{ $value }}"
                                data-sidebar-value="{{ $value }}" class="peer sr-only">
                            <span
                                class="absolute -right-1 -top-1 z-10 hidden size-5 place-items-center rounded-full bg-primary text-primary-foreground peer-checked:grid"><svg
                                    viewBox="0 0 24 24" fill="none" class="size-3" aria-hidden="true">
                                    <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span>
                            <span
                                class="flex aspect-[1.45] gap-1 overflow-hidden rounded-md border border-border bg-muted p-1 transition peer-checked:border-primary peer-checked:ring-1 peer-checked:ring-primary peer-focus-visible:outline-2 peer-focus-visible:outline-ring">
                                <span
                                    class="w-1/3 rounded-sm bg-slate-700 {{ $value === 'inset' ? 'm-1 rounded-sm' : '' }} {{ $value === 'floating' ? 'm-1 rounded-md shadow' : '' }}"></span>
                                <span class="flex-1 rounded-sm bg-background"><span
                                        class="m-1 block h-1 w-2/3 rounded bg-muted-foreground/40"></span><span
                                        class="m-1 block h-3 rounded-sm bg-muted"></span></span>
                            </span>
                            <span class="mt-1 block text-xs">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="layout-options-heading" class="max-md:hidden">
                <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                    <h3 id="layout-options-heading">Distribución</h3>
                    <button type="button" data-reset-layout aria-label="Restablecer distribución"
                        class="grid size-6 place-items-center rounded-full hover:bg-accent"><svg viewBox="0 0 24 24"
                            fill="none" class="size-3.5" aria-hidden="true">
                            <path d="M4 11a8 8 0 1 1 2.3 5.7M4 5v6h6" stroke="currentColor" stroke-width="1.7"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></button>
                </div>
                <div class="grid grid-cols-3 gap-3" role="radiogroup" aria-label="Seleccionar distribución">
                    @foreach ([['default', 'Predeterminada', 'w-1/4'], ['compact', 'Compacta', 'w-[12%]'], ['full', 'Completa', 'hidden']] as [$value, $label, $sidebarWidth])
                        <label class="group relative cursor-pointer text-center">
                            <input type="radio" name="layout-setting" value="{{ $value }}"
                                data-layout-value="{{ $value }}" class="peer sr-only">
                            <span
                                class="absolute -right-1 -top-1 z-10 hidden size-5 place-items-center rounded-full bg-primary text-primary-foreground peer-checked:grid"><svg
                                    viewBox="0 0 24 24" fill="none" class="size-3" aria-hidden="true">
                                    <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span>
                            <span
                                class="flex aspect-[1.45] gap-1 overflow-hidden rounded-md border border-border bg-muted p-1 transition peer-checked:border-primary peer-checked:ring-1 peer-checked:ring-primary peer-focus-visible:outline-2 peer-focus-visible:outline-ring"><span
                                    class="{{ $sidebarWidth }} rounded-sm bg-slate-700"></span><span
                                    class="flex-1 rounded-sm bg-background p-1"><span
                                        class="block h-1 w-2/3 rounded bg-muted-foreground/40"></span><span
                                        class="mt-1 block h-3 rounded-sm bg-muted"></span></span></span>
                            <span class="mt-1 block text-xs">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="direction-options-heading">
                <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                    <h3 id="direction-options-heading">Dirección</h3>
                    <button type="button" data-reset-direction aria-label="Restablecer dirección"
                        class="grid size-6 place-items-center rounded-full hover:bg-accent"><svg viewBox="0 0 24 24"
                            fill="none" class="size-3.5" aria-hidden="true">
                            <path d="M4 11a8 8 0 1 1 2.3 5.7M4 5v6h6" stroke="currentColor" stroke-width="1.7"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></button>
                </div>
                <div class="grid max-w-[18rem] grid-cols-2 gap-3" role="radiogroup"
                    aria-label="Seleccionar dirección del contenido">
                    @foreach ([['ltr', 'Izquierda a derecha'], ['rtl', 'Derecha a izquierda']] as [$value, $label])
                        <label class="group relative cursor-pointer text-center">
                            <input type="radio" name="direction-setting" value="{{ $value }}"
                                data-direction-value="{{ $value }}" class="peer sr-only">
                            <span
                                class="absolute -right-1 -top-1 z-10 hidden size-5 place-items-center rounded-full bg-primary text-primary-foreground peer-checked:grid"><svg
                                    viewBox="0 0 24 24" fill="none" class="size-3" aria-hidden="true">
                                    <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span>
                            <span
                                class="flex aspect-[1.45] items-center gap-1 overflow-hidden rounded-md border border-border bg-muted p-2 transition peer-checked:border-primary peer-checked:ring-1 peer-checked:ring-primary peer-focus-visible:outline-2 peer-focus-visible:outline-ring {{ $value === 'rtl' ? 'flex-row-reverse' : '' }}"><span
                                    class="h-full w-1/3 rounded-sm bg-slate-700"></span><span
                                    class="flex-1 space-y-1"><span
                                        class="block h-1 w-full rounded bg-muted-foreground/40"></span><span
                                        class="block h-2 w-full rounded-sm bg-background"></span></span></span>
                            <span class="mt-1 block text-xs">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </section>
        </div>

        <footer class="border-t border-border p-4">
            <button type="button" data-reset-all-settings
                class="min-h-11 w-full rounded-md bg-destructive px-4 text-sm font-medium text-destructive-foreground transition-colors hover:bg-destructive/90">Restablecer
                todos los ajustes</button>
        </footer>
    </div>
</dialog>
