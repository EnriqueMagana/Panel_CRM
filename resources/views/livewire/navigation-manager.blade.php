<div class="space-y-7">
    <header class="flex flex-col gap-4 border-b border-border pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-muted-foreground">Administración</p>
            <h1 class="mt-1 text-2xl font-semibold">Navegación</h1>
            <p class="mt-1 text-sm text-muted-foreground">Gestiona grupos, enlaces, iconos, permisos y orden del menú.
            </p>
        </div>
        @can('navigation.manage')
            <div class="flex flex-wrap gap-2 self-start sm:self-auto">
                <button type="button" wire:click="createGroup"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium transition-colors hover:bg-accent">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                        <path d="M3.5 7.5h6l1.7 2h9.3v8.75a1.75 1.75 0 0 1-1.75 1.75H5.25a1.75 1.75 0 0 1-1.75-1.75V7.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                        <path d="M12 12.5v4m-2-2h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                    </svg>
                    Nuevo grupo
                </button>
                <button type="button" wire:click="createItem"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                        <path d="M12 5v14m-7-7h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                    Nuevo módulo
                </button>
            </div>
        @else
            <span class="inline-flex min-h-9 items-center rounded-full border border-border bg-muted/50 px-3 text-xs font-medium text-muted-foreground">
                Vista limitada a tus permisos
            </span>
        @endcan
    </header>

    @if (session('navigation-status'))
        <p role="status" class="border-l-2 border-primary pl-4 text-sm">{{ session('navigation-status') }}</p>
    @endif

    @if ($formOpen)
        <x-modal id="navigation-form-heading" :title="$editingItemId ? 'Editar elemento' : ($type === 'group' ? 'Nuevo grupo' : 'Nuevo módulo')"
            description="Configura la jerarquía, el destino y el permiso requerido. El orden se ajusta con las flechas del listado." close-action="cancelForm"
            size="xl">
            <form wire:submit="saveItem" class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-2">
                    <label for="navigation-label" class="text-sm font-medium">Nombre visible</label>
                    <input id="navigation-label" wire:model="label" required maxlength="100"
                        class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                    @error('label')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid gap-2">
                    <label for="navigation-type" class="text-sm font-medium">Tipo</label>
                    <select id="navigation-type" wire:model.live="type"
                        class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                        <option value="group">Grupo</option>
                        <option value="link">Enlace</option>
                        <option value="action">Acción</option>
                    </select>
                    @error('type')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                @if ($type !== 'group')
                    <div class="grid gap-2">
                        <label for="navigation-parent" class="text-sm font-medium">Grupo padre</label>
                        <select id="navigation-parent" wire:model="parentId"
                            class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">Sin grupo</option>
                            @foreach ($groups as $group)
                                @if ($group->id !== $editingItemId)
                                    <option value="{{ $group->id }}">{{ $group->label }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('parentId')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
                <div class="grid gap-2">
                    <label for="navigation-icon" class="text-sm font-medium">Icono</label>
                    <select id="navigation-icon" wire:model="icon"
                        class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                        <option value="">Sin icono</option>
                        @foreach (config('sidebar.icons') as $key => $iconLabel)
                            <option value="{{ $key }}">{{ $iconLabel }}</option>
                        @endforeach
                    </select>
                    @error('icon')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                @if ($type === 'link')
                    <div class="grid gap-2">
                        <label for="navigation-route" class="text-sm font-medium">Ruta</label>
                        <select id="navigation-route" wire:model="routeName"
                            class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">Selecciona una ruta</option>
                            @foreach ($routes as $routeName => $routeLabel)
                                <option value="{{ $routeName }}">{{ $routeLabel }} ({{ $routeName }})</option>
                            @endforeach
                        </select>
                        @error('routeName')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid gap-2">
                        <label for="navigation-fragment" class="text-sm font-medium">Ancla (opcional)</label>
                        <input id="navigation-fragment" wire:model="routeFragment" placeholder="profile-details"
                            class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                    </div>
                    <div class="grid gap-2">
                        <label for="navigation-active-route" class="text-sm font-medium">Ruta para estado activo</label>
                        <input id="navigation-active-route" wire:model="activeRoute" placeholder="users"
                            class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                    </div>
                @elseif ($type === 'action')
                    <div class="grid gap-2">
                        <label for="navigation-action" class="text-sm font-medium">Acción</label>
                        <select id="navigation-action" wire:model="actionKey"
                            class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">Selecciona una acción</option>
                            @foreach (config('sidebar.actions') as $key => $actionLabel)
                                <option value="{{ $key }}">{{ $actionLabel }}</option>
                            @endforeach
                        </select>
                        @error('actionKey')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
                <div class="grid gap-2">
                    <label for="navigation-permission" class="text-sm font-medium">Permiso requerido</label>
                    <select id="navigation-permission" wire:model="permissionName"
                        class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                        <option value="">Visible para todo usuario autenticado</option>
                        @foreach ($permissions as $permission => $label)
                            <option value="{{ $permission }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('permissionName')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <label class="inline-flex min-h-11 items-center gap-2 text-sm sm:col-span-2">
                    <input type="checkbox" wire:model="isActive" class="size-4 rounded border-input accent-primary">
                    Elemento activo y visible
                </label>
                <div class="flex flex-wrap gap-2 sm:col-span-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="min-h-11 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60">
                        <span wire:loading.remove
                            wire:target="saveItem">{{ $editingItemId ? 'Guardar cambios' : 'Crear elemento' }}</span>
                        <span wire:loading wire:target="saveItem">Guardando...</span>
                    </button>
                    <button type="button" wire:click="cancelForm"
                        class="min-h-11 rounded-md border border-input px-4 text-sm hover:bg-accent">Cancelar</button>
                </div>
            </form>
        </x-modal>
    @endif

    <section class="space-y-4" aria-label="Elementos de navegación" aria-live="polite">
        @forelse ($items as $item)
            <article wire:key="navigation-parent-{{ $item->id }}"
                class="overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-sm">
                <x-navigation.item-row :item="$item" :is-first="$loop->first" :is-last="$loop->last"
                    :can-manage="$canManage" />

                @if ($item->type === 'group' && $item->children->isNotEmpty())
                    <div class="border-t border-border bg-muted/20 px-3 py-2 sm:px-5">
                        <p class="px-2 py-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Módulos del grupo
                        </p>
                        <div class="divide-y divide-border rounded-lg border border-border bg-background">
                            @foreach ($item->children as $child)
                                <x-navigation.item-row :item="$child" :nested="true" :is-first="$loop->first"
                                    :is-last="$loop->last" :can-manage="$canManage" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-border px-6 py-12 text-center">
                <p class="font-medium">No hay módulos disponibles</p>
                <p class="mt-1 text-sm text-muted-foreground">Tu rol no tiene elementos de navegación asignados.</p>
            </div>
        @endforelse
    </section>
</div>
