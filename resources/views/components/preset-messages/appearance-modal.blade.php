@props(['themes', 'hasBackgroundImage' => false, 'accentColor' => null, 'backgroundColor' => null])

<x-modal id="preset-appearance-heading" title="Personalizar mensajes"
    description="Prepara los cambios y aplícalos juntos al guardar. Solo afectan tu biblioteca."
    close-action="cancelAppearance" size="2xl">
    <form wire:submit="saveAppearance" class="grid gap-7" data-preset-appearance-form>
        <fieldset class="grid gap-3">
            <legend class="text-sm font-semibold">Temas predeterminados</legend>
            <p class="text-xs leading-5 text-muted-foreground">Elige una combinación completa o selecciona Personalizado para ajustar cada detalle.</p>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($themes as $value => $theme)
                    <label class="preset-theme-choice" data-preset-theme-preview="{{ $value }}">
                        <input type="radio" wire:model="draftTheme" value="{{ $value }}" class="sr-only">
                        <span class="preset-theme-swatch" aria-hidden="true"><i></i><i></i><i></i></span>
                        <span>{{ $theme['label'] }}</span>
                    </label>
                @endforeach
                <label class="preset-theme-choice" data-preset-theme-preview="custom">
                    <input type="radio" wire:model="draftTheme" value="custom" class="sr-only">
                    <span class="preset-theme-swatch preset-theme-swatch--custom" aria-hidden="true"><i></i><i></i><i></i></span>
                    <span>Personalizado</span>
                </label>
            </div>
        </fieldset>

        <div class="grid gap-6 border-t border-border pt-6 lg:grid-cols-2">
            <fieldset class="grid gap-3">
                <legend class="text-sm font-semibold">Vista de mensajes</legend>
                <div class="grid grid-cols-2 gap-3">
                    @foreach (['list' => 'Lista', 'grid' => 'Cuadrícula'] as $value => $label)
                        <label class="preset-choice">
                            <input type="radio" wire:model="draftLayout" value="{{ $value }}" class="sr-only" data-preset-custom-control>
                            <span class="preset-layout-preview preset-layout-preview--{{ $value }}" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="grid gap-3">
                <legend class="text-sm font-semibold">Densidad</legend>
                <div class="preset-segmented">
                    @foreach (['comfortable' => 'Cómoda', 'compact' => 'Compacta'] as $value => $label)
                        <label><input type="radio" wire:model="draftDensity" value="{{ $value }}" class="sr-only" data-preset-custom-control><span>{{ $label }}</span></label>
                    @endforeach
                </div>
            </fieldset>
        </div>

        <fieldset class="grid gap-3 border-t border-border pt-6">
            <legend class="text-sm font-semibold">Color de acento</legend>
            <p class="text-xs leading-5 text-muted-foreground">Se aplica a botones, fijados y estados seleccionados.</p>
            <div class="flex flex-wrap items-center gap-3">
                @foreach (['blue' => 'Azul', 'violet' => 'Violeta', 'emerald' => 'Verde', 'rose' => 'Rosa', 'amber' => 'Ámbar'] as $value => $label)
                    <label class="preset-color-choice" data-preset-color="{{ $value }}" title="{{ $label }}">
                        <input type="radio" wire:model="draftAccent" value="{{ $value }}" class="sr-only" data-preset-custom-control>
                        <span aria-hidden="true"></span><span class="sr-only">{{ $label }}</span>
                    </label>
                @endforeach
                <label class="preset-custom-color">
                    <input type="radio" wire:model="draftAccent" value="custom" class="sr-only" data-preset-custom-control>
                    <input type="color" wire:model="draftAccentColor" value="{{ $accentColor ?: '#2563eb' }}"
                        aria-label="Color de acento personalizado" data-preset-accent-picker data-preset-custom-control>
                    <span>Personalizado</span>
                </label>
            </div>
            @error('draftAccentColor') <p role="alert" class="text-xs text-destructive">{{ $message }}</p> @enderror
        </fieldset>

        <fieldset class="grid gap-4 border-t border-border pt-6">
            <legend class="text-sm font-semibold">Fondo de biblioteca</legend>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach (['plain' => 'Liso', 'dots' => 'Puntos', 'grid' => 'Cuadrícula', 'soft' => 'Suave', 'image' => 'Imagen'] as $value => $label)
                    <label class="preset-background-choice" data-preset-background-preview="{{ $value }}">
                        <input type="radio" wire:model="draftBackground" value="{{ $value }}" class="sr-only" data-preset-custom-control>
                        <span aria-hidden="true"></span><span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div class="grid gap-4 rounded-lg border border-border bg-muted/20 p-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <label for="preset-background-color" class="text-xs font-medium">Color base del fondo</label>
                    <div class="flex flex-wrap items-center gap-2">
                        <input id="preset-background-color" type="color" wire:model="draftBackgroundColor"
                            value="{{ $backgroundColor ?: '#f8fafc' }}" data-preset-background-picker data-preset-custom-control
                            class="size-11 rounded-md border border-input bg-background p-1" aria-label="Color de fondo personalizado">
                        @foreach (['#ffffff', '#f8fafc', '#eff6ff', '#faf5ff', '#f0fdf4', '#fff7ed'] as $color)
                            <button type="button" class="preset-background-swatch" style="--swatch-color: {{ $color }}"
                                data-preset-background-color-value="{{ $color }}" aria-label="Usar color {{ $color }}"></button>
                        @endforeach
                    </div>
                    @error('draftBackgroundColor') <p role="alert" class="text-xs text-destructive">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-2">
                    <label for="preset-background-image" class="text-xs font-medium">Imagen de fondo</label>
                    <input id="preset-background-image" type="file" wire:model="backgroundImageUpload"
                        accept="image/png,image/jpeg,image/webp" data-preset-background-upload data-preset-custom-control
                        class="block min-h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-muted file:px-3 file:py-1 file:text-xs file:font-medium">
                    <p class="text-xs leading-5 text-muted-foreground">JPG, PNG o WebP, máximo 4 MB. Se adapta para cubrir el área.</p>
                    <div wire:loading wire:target="backgroundImageUpload" class="text-xs text-muted-foreground">Preparando imagen…</div>
                    @error('backgroundImageUpload') <p role="alert" class="text-xs text-destructive">{{ $message }}</p> @enderror
                    @if ($hasBackgroundImage)
                        <label class="flex min-h-11 items-center gap-2 text-xs font-medium">
                            <input type="checkbox" wire:model="removeBackgroundImage" class="size-4 rounded border-input"> Eliminar la imagen guardada
                        </label>
                    @endif
                </div>
            </div>
        </fieldset>

        <div class="grid gap-6 border-t border-border pt-6 lg:grid-cols-2">
            @foreach ([
                ['title' => 'Estilo de tarjeta', 'model' => 'draftCardStyle', 'options' => ['outlined' => 'Contorno', 'elevated' => 'Elevada', 'glass' => 'Cristal']],
                ['title' => 'Esquinas', 'model' => 'draftRadius', 'options' => ['subtle' => 'Sutil', 'rounded' => 'Redonda', 'large' => 'Amplia']],
            ] as $group)
                <fieldset class="grid gap-3">
                    <legend class="text-sm font-semibold">{{ $group['title'] }}</legend>
                    <div class="preset-segmented preset-segmented--stacked">
                        @foreach ($group['options'] as $value => $label)
                            <label><input type="radio" wire:model="{{ $group['model'] }}" value="{{ $value }}" class="sr-only" data-preset-custom-control><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </div>

        <fieldset class="grid gap-4 border-t border-border pt-6"
            x-data="{ fontSize: $wire.entangle('draftFontSize') }">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <legend class="text-sm font-semibold">Tamaño del texto</legend>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">Ajusta el contenido y los títulos de los mensajes entre 12 y 24 píxeles.</p>
                </div>
                <output for="preset-font-size" class="min-w-16 rounded-md border border-border bg-muted/40 px-3 py-2 text-center text-sm font-semibold tabular-nums"
                    x-text="`${fontSize} px`">{{ $this->draftFontSize }} px</output>
            </div>

            <div class="grid items-center gap-4 sm:grid-cols-[minmax(0,1fr)_6.5rem]">
                <div class="grid gap-2">
                    <label for="preset-font-size" class="sr-only">Tamaño del texto en píxeles</label>
                    <input id="preset-font-size" type="range" min="12" max="24" step="1"
                        x-model.number="fontSize" data-preset-custom-control
                        class="h-11 w-full cursor-pointer accent-primary"
                        aria-describedby="preset-font-size-help">
                    <div class="flex justify-between text-xs tabular-nums text-muted-foreground" aria-hidden="true">
                        <span>12 px</span><span>18 px</span><span>24 px</span>
                    </div>
                </div>
                <div class="relative">
                    <label for="preset-font-size-number" class="sr-only">Escribir tamaño del texto</label>
                    <input id="preset-font-size-number" type="number" min="12" max="24" step="1"
                        x-model.number="fontSize" data-preset-custom-control inputmode="numeric"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 pr-9 text-base tabular-nums outline-none focus-visible:ring-2 focus-visible:ring-ring sm:text-sm">
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground">px</span>
                </div>
            </div>

            <div id="preset-font-size-help" class="rounded-lg border border-border bg-muted/20 p-4">
                <p class="text-xs font-medium text-muted-foreground">Vista previa</p>
                <p class="mt-2 text-foreground" :style="`font-size: ${fontSize}px; line-height: 1.55`">
                    Este es el tamaño que tendrán tus mensajes al guardar los cambios.
                </p>
            </div>
            @error('draftFontSize') <p role="alert" class="text-xs text-destructive">{{ $message }}</p> @enderror
        </fieldset>

        <footer class="sticky bottom-0 -mx-5 -mb-5 flex flex-col-reverse gap-2 border-t border-border bg-background/95 px-5 py-4 backdrop-blur sm:-mx-6 sm:-mb-6 sm:flex-row sm:justify-end sm:px-6">
            <button type="button" wire:click="cancelAppearance" class="min-h-11 rounded-md border border-input px-4 text-sm font-medium hover:bg-accent">Cancelar</button>
            <button type="submit" wire:loading.attr="disabled" wire:target="saveAppearance,backgroundImageUpload"
                class="min-h-11 rounded-md bg-primary px-5 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:cursor-wait disabled:opacity-60">
                <span wire:loading.remove wire:target="saveAppearance">Guardar cambios</span>
                <span wire:loading wire:target="saveAppearance">Guardando…</span>
            </button>
        </footer>
    </form>
</x-modal>
