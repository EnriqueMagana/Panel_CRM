@props([
    'item',
    'nested' => false,
    'isFirst' => false,
    'isLast' => false,
    'canManage' => false,
])

<div wire:key="navigation-item-{{ $item->id }}" @class([
    'flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:p-4',
    'bg-background' => $nested,
])>
    <div class="flex min-w-0 flex-1 items-start gap-3">
        <span class="grid size-11 shrink-0 place-items-center rounded-lg border border-border bg-muted/50 text-muted-foreground">
            <x-sidebar-icon :name="$item->icon" />
        </span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h2 @class(['truncate font-semibold', 'text-sm' => $nested, 'text-base' => !$nested])>{{ $item->label }}</h2>
                <span @class([
                    'rounded-full border px-2 py-0.5 text-xs font-medium',
                    'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' => $item->is_active,
                    'border-border bg-muted text-muted-foreground' => ! $item->is_active,
                ])>{{ $item->is_active ? 'Activo' : 'Inactivo' }}</span>
            </div>
            <p class="mt-1 break-words text-xs text-muted-foreground">
                {{ match ($item->type) { 'group' => 'Grupo padre', 'action' => 'Acción', default => 'Módulo' } }}
                @if ($item->route_name || $item->action_key)
                    · {{ $item->route_name ?? $item->action_key }}
                @endif
                · {{ $item->permission_name ?: 'Disponible para toda cuenta autenticada' }}
            </p>
        </div>
    </div>

    @if ($canManage)
        <div class="flex flex-wrap items-center gap-1 sm:justify-end">
            <div class="mr-1 inline-flex rounded-md border border-input bg-background p-0.5" aria-label="Cambiar orden">
                <button type="button" wire:click="moveUp({{ $item->id }})" wire:loading.attr="disabled"
                    wire:target="moveUp({{ $item->id }})" @disabled($isFirst)
                    aria-label="Subir {{ $item->label }}" title="Subir"
                    class="grid size-11 place-items-center rounded text-muted-foreground transition-colors hover:bg-accent hover:text-foreground disabled:cursor-not-allowed disabled:opacity-30">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                        <path d="m6 14 6-6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <button type="button" wire:click="moveDown({{ $item->id }})" wire:loading.attr="disabled"
                    wire:target="moveDown({{ $item->id }})" @disabled($isLast)
                    aria-label="Bajar {{ $item->label }}" title="Bajar"
                    class="grid size-11 place-items-center rounded text-muted-foreground transition-colors hover:bg-accent hover:text-foreground disabled:cursor-not-allowed disabled:opacity-30">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                        <path d="m6 10 6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
            <button type="button" wire:click="toggleActive({{ $item->id }})" wire:loading.attr="disabled"
                wire:target="toggleActive({{ $item->id }})"
                aria-label="{{ $item->is_active ? 'Desactivar' : 'Activar' }} {{ $item->label }}"
                class="min-h-11 rounded-md border border-input px-3 text-sm font-medium transition-colors hover:bg-accent disabled:opacity-60">
                {{ $item->is_active ? 'Desactivar' : 'Activar' }}
            </button>
            <button type="button" wire:click="editItem({{ $item->id }})"
                class="min-h-11 rounded-md px-3 text-sm font-medium transition-colors hover:bg-accent">Editar</button>
            <button type="button" wire:click="deleteItem({{ $item->id }})"
                wire:confirm="¿Eliminar {{ $item->label }}{{ $item->type === 'group' ? ' y sus módulos' : '' }}?"
                class="min-h-11 rounded-md px-3 text-sm font-medium text-destructive transition-colors hover:bg-destructive/10">Eliminar</button>
        </div>
    @endif
</div>
