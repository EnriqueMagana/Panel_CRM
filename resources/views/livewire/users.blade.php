<div class="space-y-7">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Lista de usuarios</h1>
            <p class="mt-1 text-sm text-muted-foreground">Administra las cuentas y sus roles de acceso.</p>
        </div>
        @can('users.create')
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="createInvitation"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                        <path d="M3 6h18v12H3zM3 7l9 7 9-7m-6-2 3-3m0 0v4m0-4h-4" stroke="currentColor" stroke-width="1.6"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Invitar usuario
                </button>
                <button type="button" wire:click="createUser"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                        <path d="M12 5v14m-7-7h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                    Agregar usuario
                </button>
            </div>
        @endcan
    </header>

    @if (session('users-status'))
        <p role="status" class="border-l-2 border-primary pl-4 text-sm" aria-live="polite">
            {{ session('users-status') }}</p>
    @endif

    @if ($formOpen)
        <x-modal id="user-form-heading" :title="$editingUserId ? 'Editar usuario' : 'Agregar usuario'" description="Crea la cuenta y configura sus datos de acceso."
            close-action="cancelForm" size="xl">
            <form wire:submit="saveUser" class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-2">
                    <label for="user-name" class="text-sm font-medium">Nombre completo</label>
                    <input id="user-name" wire:model="name" autocomplete="name" required
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring/30">
                    @error('name')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid gap-2">
                    <label for="user-username" class="text-sm font-medium">Usuario</label>
                    <input id="user-username" wire:model="username" autocomplete="username" required
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring/30">
                    @error('username')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid gap-2">
                    <label for="user-email" class="text-sm font-medium">Correo electrónico</label>
                    <input id="user-email" wire:model="email" type="email" autocomplete="email" required
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring/30">
                    @error('email')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid gap-2">
                    <label for="user-phone" class="text-sm font-medium">Teléfono <span
                            class="text-muted-foreground">(opcional)</span></label>
                    <input id="user-phone" wire:model="phoneNumber" type="tel" autocomplete="tel"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring/30">
                    @error('phoneNumber')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid gap-2">
                    <label for="user-password" class="text-sm font-medium">
                        Contraseña {{ $editingUserId ? '(opcional)' : '' }}
                    </label>
                    <input id="user-password" wire:model="password" type="password" autocomplete="new-password"
                        @required(!$editingUserId)
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring/30">
                    @error('password')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid gap-2">
                    <label for="user-password-confirmation" class="text-sm font-medium">Confirmar contraseña</label>
                    <input id="user-password-confirmation" wire:model="passwordConfirmation" type="password"
                        autocomplete="new-password" @required(!$editingUserId)
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring/30">
                    @error('passwordConfirmation')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                @can('users.assign_roles')
                    <fieldset class="grid gap-3 sm:col-span-2">
                        <legend class="text-sm font-medium">Roles</legend>
                        @if ($availableRoles->isNotEmpty())
                            <div class="flex flex-wrap gap-x-5 gap-y-3">
                                @foreach ($availableRoles as $role)
                                    <label class="inline-flex min-h-11 items-center gap-2 text-sm">
                                        <input type="checkbox" wire:model="selectedRoles" value="{{ $role->id }}"
                                            class="size-4 rounded border-input accent-primary">
                                        {{ $role->name }}
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-muted-foreground">No hay roles que puedas asignar.</p>
                        @endif
                        @error('selectedRoles')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                        @error('selectedRoles.*')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </fieldset>
                @endcan

                <div class="flex flex-wrap gap-2 sm:col-span-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex min-h-11 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60">
                        <span wire:loading.remove
                            wire:target="saveUser">{{ $editingUserId ? 'Guardar cambios' : 'Crear usuario' }}</span>
                        <span wire:loading wire:target="saveUser">Guardando...</span>
                    </button>
                    <button type="button" wire:click="cancelForm"
                        class="min-h-11 rounded-md border border-input px-4 text-sm font-medium hover:bg-accent">Cancelar</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($invitationFormOpen)
        <x-modal id="invitation-form-heading" title="Invitar usuario"
            description="Envía una invitación para unirse al equipo y define su nivel de acceso."
            close-action="cancelInvitation" size="md">
            <form wire:submit="sendInvitation" class="grid gap-5">
                <div class="grid gap-2">
                    <label for="invitation-email" class="text-sm font-medium">Correo electrónico</label>
                    <input id="invitation-email" wire:model="invitationEmail" type="email" autocomplete="email"
                        required placeholder="nombre@empresa.com"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring/30">
                    @error('invitationEmail')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                @can('users.assign_roles')
                    <div class="grid gap-2">
                        <label for="invitation-role" class="text-sm font-medium">Rol</label>
                        <select id="invitation-role" wire:model="invitationRoleId" required
                            class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">Selecciona un rol</option>
                            @foreach ($availableRoles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('invitationRoleId')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                @endcan
                <div class="grid gap-2">
                    <label for="invitation-note" class="text-sm font-medium">Nota <span
                            class="text-muted-foreground">(opcional)</span></label>
                    <textarea id="invitation-note" wire:model="invitationNote" rows="3" maxlength="500"
                        placeholder="Añade un mensaje personal a la invitación"
                        class="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring/30"></textarea>
                    @error('invitationNote')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cancelInvitation"
                        class="min-h-11 rounded-md border border-input px-4 text-sm hover:bg-accent">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex min-h-11 items-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60">
                        <span wire:loading.remove wire:target="sendInvitation">Enviar invitación</span>
                        <span wire:loading wire:target="sendInvitation">Enviando...</span>
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    <section class="space-y-4" aria-label="Listado de usuarios">
        <div class="flex flex-wrap items-center gap-2">
            <label class="relative block min-w-56 flex-1 sm:max-w-xs">
                <span class="sr-only">Buscar usuarios</span>
                <svg viewBox="0 0 24 24" fill="none"
                    class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true">
                    <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.6" />
                    <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                </svg>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Filtrar usuarios..."
                    class="h-10 w-full rounded-md border border-input bg-background pl-9 pr-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring/30">
            </label>
            <details class="relative">
                <summary
                    class="flex min-h-10 list-none cursor-pointer items-center gap-2 rounded-md border border-dashed border-border px-3 text-sm hover:bg-accent">
                    ⊕ Estado</summary>
                <div
                    class="absolute left-0 top-full z-20 mt-2 grid min-w-44 gap-1 rounded-md border border-border bg-popover p-2 text-popover-foreground shadow-lg">
                    @foreach (['' => 'Todos', 'active' => 'Activo', 'invited' => 'Invitado', 'suspended' => 'Suspendido'] as $value => $label)
                        <label class="flex min-h-10 items-center gap-2 rounded-sm px-2 text-sm hover:bg-accent"><input
                                type="radio" wire:model.live="statusFilter" value="{{ $value }}"
                                class="accent-primary">{{ $label }}</label>
                    @endforeach
                </div>
            </details>
            <details class="relative">
                <summary
                    class="flex min-h-10 list-none cursor-pointer items-center gap-2 rounded-md border border-dashed border-border px-3 text-sm hover:bg-accent">
                    ⊕ Rol</summary>
                <div
                    class="absolute left-0 top-full z-20 mt-2 grid min-w-44 gap-1 rounded-md border border-border bg-popover p-2 text-popover-foreground shadow-lg">
                    <label class="flex min-h-10 items-center gap-2 rounded-sm px-2 text-sm hover:bg-accent"><input
                            type="radio" wire:model.live="roleFilter" value=""
                            class="accent-primary">Todos</label>
                    @foreach ($allRoles as $role)
                        <label class="flex min-h-10 items-center gap-2 rounded-sm px-2 text-sm hover:bg-accent"><input
                                type="radio" wire:model.live="roleFilter" value="{{ $role->id }}"
                                class="accent-primary">{{ $role->name }}</label>
                    @endforeach
                </div>
            </details>
            <details class="relative ml-auto">
                <summary
                    class="flex min-h-10 list-none cursor-pointer items-center gap-2 rounded-md border border-input px-3 text-sm hover:bg-accent">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                        <path d="M4 7h16M7 12h10m-7 5h4M8 4v6m8 1v6" stroke="currentColor" stroke-width="1.6"
                            stroke-linecap="round" />
                    </svg>
                    Columnas
                </summary>
                <div
                    class="absolute right-0 top-full z-20 mt-2 grid min-w-48 gap-1 rounded-md border border-border bg-popover p-2 text-popover-foreground shadow-lg">
                    <p class="px-2 py-1 text-xs font-medium text-muted-foreground">Mostrar columnas</p>
                    @foreach (['username' => 'Usuario', 'name' => 'Nombre', 'email' => 'Correo', 'phone_number' => 'Teléfono', 'status' => 'Estado', 'role' => 'Rol'] as $column => $label)
                        <label class="flex min-h-10 items-center gap-2 rounded-sm px-2 text-sm hover:bg-accent"><input
                                type="checkbox" wire:model.live="visibleColumns.{{ $column }}"
                                class="size-4 rounded border-input accent-primary">{{ $label }}</label>
                    @endforeach
                </div>
            </details>
        </div>

        <div class="flex min-h-8 items-center justify-between text-sm text-muted-foreground">
            <span>{{ $users->total() }} cuentas</span>
            @if (count($selectedUsers))
                <button type="button" data-confirm-action
                    data-confirm-message="¿Eliminar {{ count($selectedUsers) }} usuarios seleccionados?"
                    wire:click="deleteSelectedUsers"
                    class="min-h-9 rounded-md px-3 text-destructive hover:bg-accent">Eliminar
                    {{ count($selectedUsers) }} seleccionados</button>
            @endif
        </div>

        <div class="overflow-x-auto border-y border-border">
            <table class="w-full min-w-175 text-left text-sm">
                <thead class="border-b border-border text-xs font-medium text-muted-foreground">
                    <tr>
                        <th scope="col" class="w-12 px-3 py-3"><input type="checkbox"
                                wire:click="toggleSelectAll({{ json_encode($users->pluck('id')->all()) }})"
                                aria-label="Seleccionar usuarios de esta página"
                                class="size-4 rounded border-input accent-primary"></th>
                        @if ($visibleColumns['username'])
                            <th scope="col" class="px-3 py-3"><button type="button"
                                    wire:click="sortBy('username')" class="flex items-center gap-2">Usuario <span
                                        aria-hidden="true">↕</span></button></th>
                        @endif
                        @if ($visibleColumns['name'])
                            <th scope="col" class="px-3 py-3"><button type="button" wire:click="sortBy('name')"
                                    class="flex items-center gap-2">Nombre <span aria-hidden="true">↕</span></button>
                            </th>
                        @endif
                        @if ($visibleColumns['email'])
                            <th scope="col" class="px-3 py-3"><button type="button" wire:click="sortBy('email')"
                                    class="flex items-center gap-2">Correo <span aria-hidden="true">↕</span></button>
                            </th>
                        @endif
                        @if ($visibleColumns['phone_number'])
                            <th scope="col" class="px-3 py-3">Teléfono</th>
                        @endif
                        @if ($visibleColumns['status'])
                            <th scope="col" class="px-3 py-3">Estado</th>
                        @endif
                        @if ($visibleColumns['role'])
                            <th scope="col" class="px-3 py-3">Rol</th>
                        @endif
                        <th scope="col" class="w-14 px-3 py-3 text-right"><span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" @class([
                            'bg-muted/30' => in_array($user->id, $selectedUsers),
                            'hover:bg-muted/40' => !in_array($user->id, $selectedUsers),
                        ])>
                            <td class="px-3 py-3"><input type="checkbox" wire:model.live="selectedUsers"
                                    value="{{ $user->id }}" aria-label="Seleccionar {{ $user->username }}"
                                    class="size-4 rounded border-input accent-primary"></td>
                            @if ($visibleColumns['username'])
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3"><x-user-avatar :user="$user"
                                            size="size-9" :pixels="36" alt=""
                                            class="rounded-full" /><span
                                            class="font-medium">{{ $user->username }}</span></div>
                                </td>
                            @endif
                            @if ($visibleColumns['name'])
                                <td class="px-3 py-3">{{ $user->name }}</td>
                            @endif
                            @if ($visibleColumns['email'])
                                <td class="px-3 py-3">{{ $user->email }}</td>
                            @endif
                            @if ($visibleColumns['phone_number'])
                                <td class="px-3 py-3">{{ $user->phone_number ?: '—' }}</td>
                            @endif
                            @if ($visibleColumns['status'])
                                <td class="px-3 py-3">
                                    @php($statusStyles = ['active' => 'border-teal-200 bg-teal-50 text-teal-800', 'invited' => 'border-sky-200 bg-sky-50 text-sky-800', 'suspended' => 'border-rose-200 bg-rose-50 text-rose-700'])
                                    <span
                                        @class([
                                            'inline-flex rounded-md border px-2 py-0.5 text-xs font-medium',
                                            $statusStyles[$user->status] ??
                                            'border-border bg-muted text-muted-foreground',
                                        ])>{{ ['active' => 'Activo', 'invited' => 'Invitado', 'suspended' => 'Suspendido'][$user->status] ?? $user->status }}</span>
                                </td>
                            @endif
                            @if ($visibleColumns['role'])
                                <td class="px-3 py-3">
                                    @if ($user->roles->isNotEmpty())
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            @foreach ($user->roles as $role)
                                                <span class="inline-flex items-center gap-2 text-sm"><x-sidebar-icon
                                                        name="users" />{{ $role->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted-foreground">Sin roles</span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-3 py-3 text-right">
                                <details class="relative inline-block text-left" data-floating-menu>
                                    <summary aria-label="Acciones para {{ $user->username }}"
                                        class="grid size-9 list-none cursor-pointer place-items-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground">
                                        <svg viewBox="0 0 24 24" fill="currentColor" class="size-4"
                                            aria-hidden="true">
                                            <circle cx="5" cy="12" r="1.5" />
                                            <circle cx="12" cy="12" r="1.5" />
                                            <circle cx="19" cy="12" r="1.5" />
                                        </svg>
                                    </summary>
                                    <div data-floating-panel
                                        class="fixed grid min-w-40 rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-lg">
                                        @can('users.update')
                                            <button type="button" wire:click="editUser({{ $user->id }})"
                                                class="min-h-10 rounded-sm px-3 text-left text-sm hover:bg-accent">Editar</button>
                                            @if ($user->id !== auth()->id() && $user->status !== 'invited')
                                                <button type="button" wire:click="toggleUserStatus({{ $user->id }})"
                                                    wire:confirm="¿{{ $user->status === 'suspended' ? 'Reactivar' : 'Suspender' }} a {{ $user->name }}?"
                                                    class="min-h-10 rounded-sm px-3 text-left text-sm hover:bg-accent">{{ $user->status === 'suspended' ? 'Reactivar' : 'Suspender' }}</button>
                                            @endif
                                        @endcan
                                        @can('users.create')
                                            @if ($user->status === 'invited')
                                                <button type="button" wire:click="resendInvitation({{ $user->id }})"
                                                    class="min-h-10 rounded-sm px-3 text-left text-sm hover:bg-accent">Reenviar
                                                    invitación</button>
                                            @endif
                                        @endcan
                                        @can('users.delete')
                                            @if ($user->id !== auth()->id())
                                                <button type="button" data-confirm-action
                                                    data-confirm-message="¿Eliminar la cuenta de {{ $user->name }}?"
                                                    wire:click="deleteUser({{ $user->id }})"
                                                    class="min-h-10 rounded-sm px-3 text-left text-sm text-destructive hover:bg-accent">Eliminar</button>
                                            @endif
                                        @endcan
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-12 text-center text-sm text-muted-foreground">
                                No se encontraron usuarios.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div>{{ $users->links() }}</div>
        @endif
    </section>
</div>
