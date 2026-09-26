<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

class Roles extends Component
{
    public bool $formOpen = false;

    public ?int $editingRoleId = null;

    public string $name = '';

    public array $selectedPermissions = [];

    public function createRole(): void
    {
        $this->authorize('roles.create');
        $this->resetForm();
        $this->formOpen = true;
    }

    public function editRole(int $roleId): void
    {
        $this->authorize('roles.update');

        $role = SpatieRole::query()->with('permissions')->findOrFail($roleId);
        $this->ensureRoleManageable($role);
        $this->resetForm();
        $this->editingRoleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions
            ->pluck('name')
            ->intersect($this->availablePermissionNames())
            ->values()
            ->all();
        $this->formOpen = true;
    }

    public function saveRole(): void
    {
        $isEditing = $this->editingRoleId !== null;
        $this->authorize($isEditing ? 'roles.update' : 'roles.create');

        $role = $isEditing
            ? SpatieRole::query()->with('permissions')->findOrFail($this->editingRoleId)
            : null;
        if ($role) {
            $this->ensureRoleManageable($role);
        }

        $availablePermissions = $this->availablePermissionNames();
        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique(config('permission.table_names.roles'), 'name')
                    ->where('guard_name', 'web')
                    ->ignore($this->editingRoleId),
            ],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['string', Rule::in($availablePermissions)],
        ]);

        $role ??= SpatieRole::create(['name' => $validated['name'], 'guard_name' => 'web']);
        if ($isEditing) {
            $role->name = $validated['name'];
            $role->save();
        }

        $lockedPermissions = $role->permissions()
            ->pluck('name')
            ->diff($availablePermissions);
        $permissionNames = collect($validated['selectedPermissions'] ?? [])
            ->merge($lockedPermissions)
            ->unique();

        $role->syncPermissions(SpatiePermission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $permissionNames)
            ->get());

        $this->closeForm();
        session()->flash('roles-status', $isEditing ? 'Rol actualizado.' : 'Rol creado.');
    }

    public function deleteRole(int $roleId): void
    {
        $this->authorize('roles.delete');

        $role = SpatieRole::query()->findOrFail($roleId);
        abort_if($role->name === 'Super Admin', 403);

        if ($role->users()->exists()) {
            $this->addError('deleteRole', 'No puedes eliminar un rol que está asignado a usuarios.');

            return;
        }

        $role->delete();
        session()->flash('roles-status', 'Rol eliminado.');
    }

    public function cancelForm(): void
    {
        $this->closeForm();
    }

    public function render()
    {
        $modules = collect(config('access.modules'));
        $availablePermissions = collect($this->availablePermissionNames());
        $permissionLabels = $modules->flatMap(fn (array $module, string $key) => collect($module['permissions'])
            ->mapWithKeys(fn (string $label, string $action) => ["{$key}.{$action}" => "{$module['label']}: {$label}"]));
        $permissionMatrix = $modules->map(function (array $module, string $key) use ($availablePermissions) {
            return [
                'key' => $key,
                'label' => $module['label'],
                'permissions' => collect($module['permissions'])
                    ->map(fn (string $label, string $action) => [
                        'name' => "{$key}.{$action}",
                        'label' => $label,
                    ])
                    ->filter(fn (array $permission) => $availablePermissions->contains($permission['name']))
                    ->values(),
            ];
        })->filter(fn (array $module) => $module['permissions']->isNotEmpty())->values();

        return view('livewire.roles', [
            'roles' => SpatieRole::query()
                ->where('guard_name', 'web')
                ->with('permissions')
                ->withCount('users')
                ->orderBy('name')
                ->get(),
            'permissionMatrix' => $permissionMatrix,
            'permissionLabels' => $permissionLabels,
        ]);
    }

    private function availablePermissionNames(): array
    {
        $names = collect(config('access.modules'))
            ->flatMap(fn (array $module, string $key) => collect($module['permissions'])
                ->keys()
                ->map(fn (string $action) => "{$key}.{$action}"));

        if (Auth::user()->hasRole('Super Admin')) {
            return $names->values()->all();
        }

        return $names
            ->filter(fn (string $name) => Auth::user()->can($name))
            ->values()
            ->all();
    }

    private function ensureRoleManageable(SpatieRole $role): void
    {
        abort_if($role->name === 'Super Admin' && ! Auth::user()->hasRole('Super Admin'), 403);
    }

    private function closeForm(): void
    {
        $this->formOpen = false;
        $this->reset('editingRoleId', 'name', 'selectedPermissions');
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->closeForm();
    }
}
