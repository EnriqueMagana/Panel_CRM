<div class="space-y-7">
    <header class="flex flex-col gap-4 border-b border-border pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-muted-foreground">Administración</p>
            <h1 class="mt-1 text-2xl font-semibold">Roles</h1>
            <p class="mt-1 text-sm text-muted-foreground">Define permisos para cada módulo y asigna los roles a usuarios.
            </p>
        </div>
        @can('roles.create')
            <button type="button" wire:click="createRole"
                class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 sm:self-auto">
                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                    <path d="M12 5v14m-7-7h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                Nuevo rol
            </button>
        @endcan
    </header>

    @if (session('roles-status'))
        <p role="status" class="border-l-2 border-primary pl-4 text-sm" aria-live="polite">
            {{ session('roles-status') }}</p>
    @endif
    @error('deleteRole')
        <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
    @enderror

    @if ($formOpen)
        <x-modal id="role-form-heading" :title="$editingRoleId ? 'Editar rol' : 'Crear rol'" description="Selecciona los permisos por módulo."
            close-action="cancelForm" size="2xl">
            <form wire:submit="saveRole" class="grid gap-5">
                <div class="grid max-w-xl gap-2">
                    <label for="role-name" class="text-sm font-medium">Nombre del rol</label>
                    <input id="role-name" wire:model="name" required maxlength="100"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring/30">
                    @error('name')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <fieldset class="grid gap-5">
                    <legend class="mb-1 text-sm font-semibold">Permisos por módulo</legend>
                    @forelse ($permissionMatrix as $module)
                        <div wire:key="module-{{ $module['key'] }}"
                            class="grid gap-2 border-t border-border pt-4 sm:grid-cols-[160px_minmax(0,1fr)]">
                            <h3 class="pt-2 text-sm font-medium">{{ $module['label'] }}</h3>
                            <div class="flex flex-wrap gap-x-5 gap-y-2">
                                @foreach ($module['permissions'] as $permission)
                                    <label class="inline-flex min-h-10 items-center gap-2 text-sm">
                                        <input type="checkbox" wire:model="selectedPermissions"
                                            value="{{ $permission['name'] }}"
                                            class="size-4 rounded border-input accent-primary">
                                        {{ $permission['label'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted-foreground">No tienes permisos disponibles para delegar.</p>
                    @endforelse
                    @error('selectedPermissions.*')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex min-h-11 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60">
                        <span wire:loading.remove
                            wire:target="saveRole">{{ $editingRoleId ? 'Guardar cambios' : 'Crear rol' }}</span>
                        <span wire:loading wire:target="saveRole">Guardando...</span>
                    </button>
                    <button type="button" wire:click="cancelForm"
                        class="min-h-11 rounded-md border border-input px-4 text-sm font-medium hover:bg-accent">Cancelar</button>
                </div>
            </form>
        </x-modal>
    @endif

    <section class="space-y-3" aria-label="Listado de roles">
        <p class="text-sm text-muted-foreground">{{ $roles->count() }} roles</p>
        <div class="overflow-x-auto border-y border-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs font-medium text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-3 py-3">Rol</th>
                        <th scope="col" class="px-3 py-3">Permisos</th>
                        <th scope="col" class="px-3 py-3">Usuarios</th>
                        <th scope="col" class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($roles as $role)
                        <tr wire:key="role-{{ $role->id }}" class="hover:bg-muted/40">
                            <td class="px-3 py-3">
                                <p class="font-medium">{{ $role->name }}</p>
                                @if ($role->name === 'Super Admin')
                                    <p class="mt-0.5 text-xs text-muted-foreground">Rol protegido del sistema</p>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if ($role->permissions->isNotEmpty())
                                    <div class="flex max-w-xl flex-wrap gap-1.5">
                                        @foreach ($role->permissions as $permission)
                                            <span
                                                class="rounded border border-border px-2 py-0.5 text-xs">{{ $permissionLabels[$permission->name] ?? $permission->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted-foreground">Sin permisos</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">{{ $role->users_count }}</td>
                            <td class="px-3 py-3">
                                <div class="flex justify-end gap-1">
                                    @can('roles.update')
                                        <button type="button" wire:click="editRole({{ $role->id }})"
                                            class="min-h-10 rounded-md px-3 text-sm hover:bg-accent">Editar</button>
                                    @endcan
                                    @can('roles.delete')
                                        @if ($role->name !== 'Super Admin' && $role->users_count === 0)
                                            <button type="button" wire:click="deleteRole({{ $role->id }})"
                                                wire:confirm="¿Eliminar el rol {{ $role->name }}?"
                                                class="min-h-10 rounded-md px-3 text-sm text-destructive hover:bg-accent">Eliminar</button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-12 text-center text-sm text-muted-foreground">No hay
                                roles creados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
