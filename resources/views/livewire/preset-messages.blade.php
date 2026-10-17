<div class="preset-messages-page space-y-7" data-preset-layout="{{ $messageLayout }}"
    data-preset-density="{{ $messageDensity }}" data-preset-accent="{{ $messageAccent }}"
    data-preset-background="{{ $messageBackground }}" data-preset-card-style="{{ $messageCardStyle }}"
    data-preset-radius="{{ $messageRadius }}"
    style="--preset-message-font-size: {{ $messageFontSize }}px; --preset-custom-accent: {{ $messageAccentColor ?: '#2563eb' }}; --preset-background-color: {{ $messageBackgroundColor ?: 'transparent' }}; @if ($messageBackgroundImageUrl) --preset-background-image: url('{{ $messageBackgroundImageUrl }}'); @endif">
    @if (session('preset-messages-status'))
        <div role="status" aria-live="polite"
            class="flex items-center gap-2 rounded-md border border-primary/20 bg-primary/5 px-4 py-3 text-sm">
            <svg viewBox="0 0 24 24" fill="none" class="size-4 shrink-0 text-primary" aria-hidden="true">
                <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
            {{ session('preset-messages-status') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        @php
            $messageLibraries = ['mine' => 'Mis mensajes'];
            if (auth()->user()->can('preset_messages.view_global')) {
                $messageLibraries['global'] = 'Biblioteca global';
            }
            $messageLibraries['hidden'] = 'Ocultos';
        @endphp
        <nav aria-label="Bibliotecas de mensajes"
            class="grid w-full {{ count($messageLibraries) === 3 ? 'grid-cols-3' : 'grid-cols-2' }} gap-1 rounded-lg border border-border bg-muted/40 p-1 lg:w-auto">
            @foreach ($messageLibraries as $value => $label)
                <button type="button" wire:click="selectLibrary('{{ $value }}')"
                    aria-pressed="{{ $library === $value ? 'true' : 'false' }}"
                    class="min-h-11 rounded-md px-3 text-sm font-medium transition-colors {{ $library === $value ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        <button type="button" wire:click="openAppearance" aria-haspopup="dialog"
            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6" />
                <path d="M12 2.75v2.1M12 19.15v2.1M21.25 12h-2.1M4.85 12h-2.1M18.54 5.46l-1.48 1.48M6.94 17.06l-1.48 1.48M18.54 18.54l-1.48-1.48M6.94 6.94 5.46 5.46" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
            Personalizar vista
        </button>
    </div>
    <section aria-label="Buscar mensajes">
        <label for="preset-message-search" class="sr-only">Buscar por nombre o contenido</label>
        <div class="relative max-w-xl">
            <svg viewBox="0 0 24 24" fill="none"
                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true">
                <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.6" />
                <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
            <input id="preset-message-search" type="search" wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre o contenido..." autocomplete="off"
                class="h-11 w-full rounded-md border border-input bg-background pl-10 pr-10 text-base outline-none transition-shadow placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
            <span wire:loading wire:target="search"
                class="absolute right-3.5 top-1/2 size-4 -translate-y-1/2 animate-spin rounded-full border-2 border-muted-foreground/30 border-t-foreground"
                aria-label="Buscando"></span>
        </div>
    </section>

    @if ($formOpen)
        <x-modal id="preset-message-form-heading" :title="$editingMessageId ? 'Editar mensaje' : 'Agregar mensaje'"
            description="Añade variables manuales o automáticas para personalizar el texto antes de copiarlo."
            close-action="cancelForm" size="2xl">
            <form wire:submit="saveMessage" class="grid gap-5">
                <div class="grid gap-2">
                    <label for="preset-title" class="text-sm font-medium">Nombre del mensaje</label>
                    <input id="preset-title" wire:model="title" required maxlength="120" autocomplete="off"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-base outline-none focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm"
                        placeholder="Ej. Confirmación de cita">
                    @error('title')
                        <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label for="preset-body" class="text-sm font-medium">Contenido</label>
                        <span class="text-xs text-muted-foreground">Máximo 5,000 caracteres</span>
                    </div>
                    <textarea id="preset-body" wire:model="body" required maxlength="5000" rows="7"
                        class="w-full resize-y rounded-md border border-input bg-background px-3 py-2.5 text-base leading-6 outline-none focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm"
                        placeholder="Hola &#123;&#123;nombre&#125;&#125;, tu solicitud &#123;&#123;folio&#125;&#125; está lista."></textarea>
                    <p class="text-xs leading-5 text-muted-foreground">
                        Usa <code>&#123;&#123;variable&#125;&#125;</code>. El formato anterior
                        <code>[variable]</code> sigue siendo compatible y se convierte al guardar.
                    </p>
                    <div class="grid gap-3 rounded-lg border border-border bg-muted/25 p-3">
                        <div>
                            <p class="text-xs font-medium">Variables automáticas</p>
                            <p class="mt-0.5 text-xs leading-5 text-muted-foreground">Se completan con la información
                                actual al copiar.</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($automaticVariables as $variable => $option)
                                    <button type="button" data-insert-preset-variable="{{ $variable }}"
                                        data-variable-target="preset-body" title="Ejemplo: {{ $option['example'] }}"
                                        class="min-h-11 rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        + {{ $option['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="border-t border-border pt-3">
                            <p class="text-xs font-medium">Variables calculadas</p>
                            <p class="mt-0.5 text-xs leading-5 text-muted-foreground">Solicitan un dato y generan el valor final al instante.</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($calculatedVariables as $variable => $option)
                                    <button type="button" data-insert-preset-variable="{{ $variable }}"
                                        data-variable-target="preset-body" title="Ejemplo: {{ $option['example'] }}"
                                        class="min-h-11 rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        + {{ $option['label'] }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs leading-5 text-muted-foreground">Ejemplo: “El usuario se desbloqueará en &#123;&#123;dias_para_90&#125;&#125;”.</p>
                        </div>
                        <div class="border-t border-border pt-3">
                            <p class="text-xs font-medium">Campos para completar</p>
                            <p class="mt-0.5 text-xs leading-5 text-muted-foreground">Crean un campo para escribir el
                                valor antes de copiar.</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($suggestedVariables as $variable => $label)
                                    <button type="button" data-insert-preset-variable="{{ $variable }}"
                                        data-variable-target="preset-body"
                                        class="min-h-11 rounded-md border border-border bg-background px-3 text-xs font-medium transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        + {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @error('body')
                        <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex min-h-11 w-fit cursor-pointer items-center gap-3 text-sm">
                    <input type="checkbox" wire:model="isActive" class="size-4 rounded border-input accent-primary">
                    <span>
                        <span class="font-medium">Mensaje activo</span>
                        <span class="block text-xs text-muted-foreground">Visible para quienes pueden consultar el
                            módulo.</span>
                    </span>
                </label>

                <div class="flex flex-col-reverse gap-2 border-t border-border pt-5 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelForm"
                        class="min-h-11 rounded-md border border-input px-4 text-sm font-medium transition-colors hover:bg-accent">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveMessage"
                        class="inline-flex min-h-11 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove
                            wire:target="saveMessage">{{ $editingMessageId ? 'Guardar cambios' : 'Crear mensaje' }}</span>
                        <span wire:loading wire:target="saveMessage">Guardando...</span>
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    <section class="grid gap-6" aria-label="Mensajes predeterminados">
        <x-preset-messages.message-collection :messages="$pinnedMessages" :library="$library"
            :extension-configured="$extensionConfigured" :imported-source-ids="$importedSourceIds" pinned title="Fijados" />

        @if ($messages->isNotEmpty())
            <x-preset-messages.message-collection :messages="$messages->getCollection()" :library="$library"
                :extension-configured="$extensionConfigured"
                :imported-source-ids="$importedSourceIds"
                :hidden="$library === 'hidden'" :title="$pinnedMessages->isNotEmpty() ? ($library === 'hidden' ? null : 'Todos los mensajes') : null" />
        @elseif ($pinnedMessages->isEmpty())
            <div class="flex min-h-64 flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/20 px-6 py-12 text-center">
                <span class="grid size-12 place-items-center rounded-full bg-muted text-muted-foreground" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5">
                        <path d="M5 4.5h14A2.5 2.5 0 0 1 21.5 7v8A2.5 2.5 0 0 1 19 17.5H9L4.5 21v-4A2.5 2.5 0 0 1 2.5 15V7A2.5 2.5 0 0 1 5 4.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                    </svg>
                </span>
                <h3 class="mt-4 font-semibold">
                    {{ $search !== '' ? 'No encontramos coincidencias' : match ($library) {
                        'mine' => 'Aún no tienes mensajes',
                        'global' => 'La biblioteca global está vacía',
                        default => 'No tienes mensajes ocultos',
                    } }}
                </h3>
                <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                    {{ $search !== '' ? 'Prueba con otro nombre o una frase del contenido.' : match ($library) {
                        'mine' => 'Crea tu primer mensaje para responder más rápido.',
                        'global' => 'Los mensajes activos de tus compañeros aparecerán aquí.',
                        default => 'Los mensajes que ocultes aparecerán aquí para poder restaurarlos.',
                    } }}
                </p>
            </div>
        @endif

        @if ($messages->hasPages())
            <div class="pt-2">{{ $messages->links() }}</div>
        @endif
    </section>

    @if ($appearanceOpen)
        <x-preset-messages.appearance-modal :themes="$presetThemes" :has-background-image="filled($messageBackgroundImagePath)"
            :accent-color="$draftAccentColor" :background-color="$draftBackgroundColor" />
    @endif

</div>
