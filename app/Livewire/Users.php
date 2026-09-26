<?php

namespace App\Livewire;

use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role as SpatieRole;

class Users extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $formOpen = false;

    public bool $invitationFormOpen = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $phoneNumber = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public array $selectedRoles = [];

    public string $invitationEmail = '';

    public string $invitationRoleId = '';

    public string $invitationNote = '';

    public string $statusFilter = '';

    public string $roleFilter = '';

    public string $sortColumn = 'username';

    public string $sortDirection = 'asc';

    public array $visibleColumns = [
        'username' => true,
        'name' => true,
        'email' => true,
        'phone_number' => true,
        'status' => true,
        'role' => true,
    ];

    public array $selectedUsers = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function createUser(): void
    {
        $this->authorize('users.create');
        $this->resetForm();
        $this->formOpen = true;
    }

    public function createInvitation(): void
    {
        $this->authorize('users.create');
        $this->cancelForm();
        $this->reset('invitationEmail', 'invitationRoleId', 'invitationNote');
        $this->invitationFormOpen = true;
    }

    public function cancelInvitation(): void
    {
        $this->invitationFormOpen = false;
        $this->reset('invitationEmail', 'invitationRoleId', 'invitationNote');
        $this->resetValidation();
    }

    public function editUser(int $userId): void
    {
        $this->authorize('users.update');

        $user = User::with('roles')->findOrFail($userId);
        $this->ensureTargetManageable($user);
        $this->resetForm();
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username ?? '';
        $this->email = $user->email;
        $this->phoneNumber = $user->phone_number ?? '';
        $this->selectedRoles = Auth::user()->can('users.assign_roles')
            ? $user->roles->pluck('id')->all()
            : [];
        $this->formOpen = true;
    }

    public function sendInvitation(): void
    {
        $this->authorize('users.create');

        $canAssignRoles = Auth::user()->can('users.assign_roles');
        $allowedRoleIds = $canAssignRoles ? $this->assignableRoles()->modelKeys() : [];
        $validated = $this->validate([
            'invitationEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'invitationRoleId' => [
                $canAssignRoles ? 'required' : 'nullable',
                'integer',
                Rule::in($allowedRoleIds),
            ],
            'invitationNote' => ['nullable', 'string', 'max:500'],
        ]);

        $email = Str::lower(trim($validated['invitationEmail']));
        $userName = Str::of(Str::before($email, '@'))->replace(['.', '_', '-'], ' ')->title()->toString();
        $user = User::create([
            'name' => $userName,
            'email' => $email,
            'password' => Str::random(48),
            'email_verified_at' => null,
            'status' => 'invited',
        ]);

        $role = null;
        if ($canAssignRoles && $validated['invitationRoleId']) {
            $role = SpatieRole::query()->findOrFail($validated['invitationRoleId']);
            $user->assignRole($role);
        }

        $token = PasswordBroker::broker()->createToken($user);
        $acceptUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
        Notification::route('mail', $user->email)->notify(new UserInvitationNotification(
            Auth::user()->name,
            $role?->name ?? 'Sin rol',
            $validated['invitationNote'] ?: null,
            $acceptUrl,
        ));

        $this->invitationFormOpen = false;
        $this->reset('invitationEmail', 'invitationRoleId', 'invitationNote');
        session()->flash('users-status', "Invitación enviada a {$email}.");
    }

    public function resendInvitation(int $userId): void
    {
        $this->authorize('users.create');
        $user = User::with('roles')->findOrFail($userId);
        abort_unless($user->status === 'invited', 403);

        $role = $user->roles->first();
        $token = PasswordBroker::broker()->createToken($user);
        $acceptUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
        Notification::route('mail', $user->email)->notify(new UserInvitationNotification(
            Auth::user()->name,
            $role?->name ?? 'Sin rol',
            null,
            $acceptUrl,
        ));

        session()->flash('users-status', "Se reenvió la invitación a {$user->email}.");
    }

    public function toggleUserStatus(int $userId): void
    {
        $this->authorize('users.update');

        $user = User::findOrFail($userId);
        abort_unless($user->id !== Auth::id(), 403);
        $this->ensureTargetManageable($user);
        abort_if($user->status === 'invited', 403);

        $user->forceFill(['status' => $user->status === 'suspended' ? 'active' : 'suspended'])->save();
        session()->flash('users-status', $user->status === 'suspended' ? 'Usuario suspendido.' : 'Usuario reactivado.');
    }

    public function sortBy(string $column): void
    {
        abort_unless(in_array($column, ['username', 'name', 'email', 'phone_number', 'status', 'created_at'], true), 422);

        $this->sortDirection = $this->sortColumn === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortColumn = $column;
        $this->resetPage();
    }

    public function toggleSelectAll(array $userIds): void
    {
        $userIds = array_map('intval', $userIds);
        $allSelected = $userIds !== [] && collect($userIds)->every(fn (int $id) => in_array($id, $this->selectedUsers));
        $this->selectedUsers = $allSelected
            ? array_values(array_diff($this->selectedUsers, $userIds))
            : array_values(array_unique([...$this->selectedUsers, ...$userIds]));
    }

    public function deleteSelectedUsers(): void
    {
        $this->authorize('users.delete');

        $users = User::query()->whereIn('id', $this->selectedUsers)->get();
        foreach ($users as $user) {
            abort_unless($user->id !== Auth::id(), 403);
            $this->ensureTargetManageable($user);
            $user->syncPermissions([]);
            $user->syncRoles([]);
            $user->delete();
        }

        $this->selectedUsers = [];
        session()->flash('users-status', 'Usuarios seleccionados eliminados.');
    }

    public function saveUser(): void
    {
        $isEditing = $this->editingUserId !== null;
        $this->authorize($isEditing ? 'users.update' : 'users.create');

        $target = $isEditing ? User::with('roles')->findOrFail($this->editingUserId) : null;
        if ($target) {
            $this->ensureTargetManageable($target);
        }

        $canAssignRoles = Auth::user()->can('users.assign_roles');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($this->editingUserId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'phoneNumber' => ['nullable', 'string', 'max:32'],
            'password' => [$isEditing ? 'nullable' : 'required', 'string', Password::min(8)],
            'passwordConfirmation' => [$isEditing ? 'nullable' : 'required', 'same:password'],
        ];

        if ($canAssignRoles) {
            $allowedRoleIds = $this->assignableRoles()->modelKeys();
            $rules['selectedRoles'] = ['array'];
            $rules['selectedRoles.*'] = ['integer', Rule::in($allowedRoleIds)];
        }

        $validated = $this->validate($rules);
        $roleIds = array_map('intval', $validated['selectedRoles'] ?? []);
        $superAdminRole = SpatieRole::query()
            ->where('guard_name', 'web')
            ->where('name', 'Super Admin')
            ->first();

        if ($target && $target->is(Auth::user()) && $target->hasRole('Super Admin') &&
            $superAdminRole && ! in_array($superAdminRole->id, $roleIds, true)) {
            $this->addError('selectedRoles', 'No puedes retirar tu propio rol de Super Admin.');

            return;
        }

        $updates = [
            'name' => $validated['name'],
            'username' => Str::lower($validated['username']),
            'email' => $validated['email'],
            'phone_number' => $validated['phoneNumber'] ?: null,
        ];

        if ($validated['password'] !== null && $validated['password'] !== '') {
            $updates['password'] = $validated['password'];
        }

        $user = $target
            ? tap($target)->update($updates)
            : User::create($updates);

        if ($canAssignRoles) {
            $user->syncRoles(SpatieRole::query()
                ->where('guard_name', 'web')
                ->whereIn('id', $roleIds)
                ->get());
        }

        $this->closeForm();
        session()->flash('users-status', $isEditing ? 'Usuario actualizado.' : 'Usuario creado.');
    }

    public function deleteUser(int $userId): void
    {
        $this->authorize('users.delete');

        $user = User::findOrFail($userId);
        abort_unless($user->id !== Auth::id(), 403);
        $this->ensureTargetManageable($user);

        $user->syncPermissions([]);
        $user->syncRoles([]);
        $user->delete();

        session()->flash('users-status', 'Usuario eliminado.');
    }

    public function cancelForm(): void
    {
        $this->closeForm();
    }

    public function render()
    {
        $users = User::query()
            ->with('roles')
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('username', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('phone_number', 'like', '%'.$this->search.'%');
            }))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->roleFilter !== '', fn ($query) => $query->whereHas('roles', fn ($roles) => $roles->whereKey($this->roleFilter)))
            ->orderBy($this->sortColumn, $this->sortDirection)
            ->paginate(10);

        return view('livewire.users', [
            'users' => $users,
            'availableRoles' => Auth::user()->can('users.assign_roles') ? $this->assignableRoles() : collect(),
            'allRoles' => SpatieRole::query()->where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }

    private function assignableRoles()
    {
        $actor = Auth::user();

        return SpatieRole::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->filter(fn (SpatieRole $role) => $actor->hasRole('Super Admin') ||
                $role->permissions->every(fn ($permission) => $actor->can($permission->name)))
            ->values();
    }

    private function ensureTargetManageable(User $user): void
    {
        abort_if($user->hasRole('Super Admin') && ! Auth::user()->hasRole('Super Admin'), 403);
    }

    private function closeForm(): void
    {
        $this->formOpen = false;
        $this->reset(
            'editingUserId',
            'name',
            'username',
            'email',
            'phoneNumber',
            'password',
            'passwordConfirmation',
            'selectedRoles',
        );
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->closeForm();
    }
}
