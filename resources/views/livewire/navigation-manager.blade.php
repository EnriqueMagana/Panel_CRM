<div class="space-y-7">
    <header class="flex flex-col gap-4 border-b border-border pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-muted-foreground">Administración</p>
            <h1 class="mt-1 text-2xl font-semibold">Navegación</h1>
            <p class="mt-1 text-sm text-muted-foreground">Gestiona grupos, enlaces, iconos, permisos y orden del menú.
            </p>
        </div>
        @can('navigation.manage')
            <button type="button" wire:click="createItem"
                class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 sm:self-auto">
                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                    <path d="M12 5v14m-7-7h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                Nuevo elemento
            </button>
        @endcan
    </header>

    @if (session('navigation-status'))
        <p role="status" class="border-l-2 border-primary pl-4 text-sm">{{ session('navigation-status') }}</p>
    @endif

    @if ($formOpen)
        <x-modal id="navigation-form-heading" :title="$editingItemId ? 'Editar elemento' : 'Nuevo elemento'"
            description="Configura la ubicación, el destino y el acceso del elemento." close-action="cancelForm"
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
                            @foreach (\Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName() as $routeName => $route)
                                @if (str_starts_with($routeName, 'home') ||
                                        str_starts_with($routeName, 'profile') ||
                                        str_starts_with($routeName, 'users') ||
                                        str_starts_with($routeName, 'roles') ||
                                        str_starts_with($routeName, 'navigation') ||
                                        str_starts_with($routeName, 'technical-center'))
                                    <option value="{{ $routeName }}">{{ $routeName }}</option>
                                @endif
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
                <div class="grid gap-2">
                    <label for="navigation-order" class="text-sm font-medium">Orden</label>
                    <input id="navigation-order" wire:model="sortOrder" type="number" min="0"
                        max="65535" required
                        class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                    @error('sortOrder')
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

    <section class="space-y-3" aria-label="Elementos de navegación">
        @foreach ($items as $item)
            <div class="border-y border-border">
                <div class="flex flex-wrap items-center gap-3 py-3">
                    <x-sidebar-icon :name="$item->icon" />
                    <span class="min-w-32 flex-1 font-medium">{{ $item->label }}</span>
                    <span class="text-xs text-muted-foreground">{{ $item->type }} ·
                        {{ $item->permission_name ?: 'sin permiso' }} · orden {{ $item->sort_order }}</span>
                    <span @class([
                        'rounded border px-2 py-1 text-xs',
                        'border-emerald-600 text-emerald-700' => $item->is_active,
                        'border-border text-muted-foreground' => !$item->is_active,
                    ])>{{ $item->is_active ? 'Activo' : 'Inactivo' }}</span>
                    @can('navigation.manage')
                        <button type="button" wire:click="editItem({{ $item->id }})"
                            class="min-h-10 rounded-md px-3 text-sm hover:bg-accent">Editar</button>
                        <button type="button" wire:click="deleteItem({{ $item->id }})"
                            wire:confirm="¿Eliminar {{ $item->label }} y sus elementos hijos?"
                            class="min-h-10 rounded-md px-3 text-sm text-destructive hover:bg-accent">Eliminar</button>
                    @endcan
                </div>
                @foreach ($item->children as $child)
                    <div class="ml-6 flex flex-wrap items-center gap-3 border-t border-border py-3 pl-4">
                        <x-sidebar-icon :name="$child->icon" />
                        <span class="min-w-32 flex-1">{{ $child->label }}</span>
                        <span class="text-xs text-muted-foreground">{{ $child->type }} ·
                            {{ $child->route_name ?? $child->action_key }} ·
                            {{ $child->permission_name ?: 'sin permiso' }} · orden {{ $child->sort_order }}</span>
                        <span @class([
                            'rounded border px-2 py-1 text-xs',
                            'border-emerald-600 text-emerald-700' => $child->is_active,
                            'border-border text-muted-foreground' => !$child->is_active,
                        ])>{{ $child->is_active ? 'Activo' : 'Inactivo' }}</span>
                        @can('navigation.manage')
                            <button type="button" wire:click="editItem({{ $child->id }})"
                                class="min-h-10 rounded-md px-3 text-sm hover:bg-accent">Editar</button>
                            <button type="button" wire:click="deleteItem({{ $child->id }})"
                                wire:confirm="¿Eliminar {{ $child->label }}?"
                                class="min-h-10 rounded-md px-3 text-sm text-destructive hover:bg-accent">Eliminar</button>
                        @endcan
                    </div>
                @endforeach
            </div>
        @endforeach
    </section>
</div>
