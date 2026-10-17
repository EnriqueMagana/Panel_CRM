<?php

namespace Tests\Feature;

use App\Actions\Fortify\ResetUserPassword;
use App\Livewire\NavigationManager;
use App\Livewire\Roles;
use App\Livewire\Users;
use App\Models\SidebarItem;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Database\Seeders\SidebarItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_pages_require_module_permissions(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/home')->assertForbidden();
        $this->get('/users')->assertForbidden();
        $this->get('/roles')->assertForbidden();
        $this->get('/navigation')->assertForbidden();
        $this->get('/technical-center')->assertForbidden();
        $this->get('/snapshots')->assertForbidden();
    }

    public function test_sidebar_seeder_creates_ordered_parent_and_child_items(): void
    {
        $this->seed(SidebarItemSeeder::class);

        $administration = SidebarItem::where('label', 'Administración')->firstOrFail();
        $this->assertSame('group', $administration->type);
        $this->assertDatabaseHas('sidebar_items', [
            'label' => 'Usuarios',
            'parent_id' => $administration->id,
            'permission_name' => 'users.view',
            'sort_order' => 10,
        ]);
        $iam = SidebarItem::where('label', 'IAM')->firstOrFail();
        $this->assertSame('group', $iam->type);
        $this->assertDatabaseHas('sidebar_items', [
            'label' => 'Herramientas IAM',
            'parent_id' => $iam->id,
            'permission_name' => 'technical_center.view',
            'sort_order' => 10,
        ]);

        $operations = SidebarItem::where('label', 'Operación')->firstOrFail();
        $this->assertDatabaseHas('sidebar_items', [
            'label' => 'Mensajes predeterminados',
            'parent_id' => $operations->id,
            'sort_order' => 10,
        ]);
        $this->assertDatabaseHas('sidebar_items', [
            'label' => 'Escalado de tickets',
            'parent_id' => $operations->id,
            'sort_order' => 20,
        ]);
        $this->assertDatabaseHas('sidebar_items', [
            'label' => 'Snapshots',
            'parent_id' => $operations->id,
            'permission_name' => 'snapshots.view',
            'sort_order' => 30,
        ]);
    }

    public function test_snapshot_workspace_requires_permission_and_renders_its_tools(): void
    {
        $this->seed(SidebarItemSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/snapshots')->assertForbidden();

        $user->givePermissionTo(Permission::findOrCreate('snapshots.view', 'web'));

        $this->actingAs($user)
            ->get('/snapshots')
            ->assertOk()
            ->assertSee('Snapshots')
            ->assertSee('data-snapshot-page', false)
            ->assertSee('Pega con Ctrl+V')
            ->assertSee('Descargar ZIP');
    }

    public function test_navigation_manager_requires_permissions_and_lists_menu(): void
    {
        $this->seed(SidebarItemSeeder::class);
        $user = User::factory()->create();
        $this->actingAs($user)->get('/navigation')->assertForbidden();

        $admin = $this->makeSuperAdmin();
        $this->actingAs($admin)
            ->get('/navigation')
            ->assertOk()
            ->assertSee('Administración')
            ->assertSee('Nuevo grupo')
            ->assertSee('Nuevo módulo')
            ->assertDontSee('navigation-order', false);
    }

    public function test_sidebar_renders_only_active_items_allowed_by_permissions(): void
    {
        $this->seed(SidebarItemSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('dashboard.view', 'web'));
        $this->actingAs($user);

        $response = $this->get('/home')->assertOk();
        $this->assertStringContainsString('href="/home"', $response->getContent());
        $this->assertStringNotContainsString('href="/users"', $response->getContent());

        SidebarItem::where('label', 'Inicio')->update(['is_active' => false]);
        $this->get('/home')->assertForbidden();
    }

    public function test_navigation_manager_creates_child_items_with_permissions(): void
    {
        $admin = $this->makeSuperAdmin();
        $parent = SidebarItem::create([
            'type' => 'group',
            'label' => 'Reportes',
            'sort_order' => 50,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(NavigationManager::class)
            ->call('createItem')
            ->set('parentId', $parent->id)
            ->set('type', 'link')
            ->set('label', 'Resumen')
            ->set('icon', 'layout')
            ->set('routeName', 'home')
            ->set('permissionName', 'dashboard.view')
            ->call('saveItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sidebar_items', [
            'parent_id' => $parent->id,
            'label' => 'Resumen',
            'permission_name' => 'dashboard.view',
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    public function test_navigation_viewer_only_sees_items_assigned_by_permission(): void
    {
        $this->seed(SidebarItemSeeder::class);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo([
            Permission::findOrCreate('navigation.view', 'web'),
            Permission::findOrCreate('dashboard.view', 'web'),
        ]);

        Livewire::actingAs($viewer)
            ->test(NavigationManager::class)
            ->assertSee('Vista limitada a tus permisos')
            ->assertSee('Inicio')
            ->assertSee('Navegación')
            ->assertDontSee('Usuarios')
            ->assertDontSee('Roles')
            ->assertDontSee('Nuevo grupo');
    }

    public function test_sidebar_permission_is_enforced_on_direct_urls(): void
    {
        $this->seed(SidebarItemSeeder::class);
        $user = User::factory()->create();
        $chat = SidebarItem::query()->where('route_name', 'chats')->firstOrFail();
        $chat->update(['permission_name' => 'dashboard.view']);

        $this->actingAs($user)->get('/chats')->assertForbidden();

        $user->givePermissionTo(Permission::findOrCreate('dashboard.view', 'web'));

        $this->actingAs($user)->get('/chats')->assertOk();
    }

    public function test_parent_group_permission_is_enforced_on_direct_urls(): void
    {
        $this->seed(SidebarItemSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('preset_messages.view', 'web'));
        $operations = SidebarItem::query()->where('label', 'Operación')->firstOrFail();
        $operations->update(['permission_name' => 'users.view']);

        $this->actingAs($user)->get('/preset-messages')->assertForbidden();

        $user->givePermissionTo(Permission::findOrCreate('users.view', 'web'));

        $this->actingAs($user)->get('/preset-messages')->assertOk();
    }

    public function test_navigation_manager_creates_parent_groups_and_orders_with_arrows(): void
    {
        $admin = $this->makeSuperAdmin();
        $first = SidebarItem::create([
            'type' => 'group',
            'label' => 'Primero',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $second = SidebarItem::create([
            'type' => 'group',
            'label' => 'Segundo',
            'sort_order' => 20,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(NavigationManager::class)
            ->call('createGroup')
            ->assertSet('type', 'group')
            ->set('label', 'Tercero')
            ->call('saveItem')
            ->assertHasNoErrors()
            ->call('moveDown', $first->id)
            ->assertSee('Orden actualizado.');

        $this->assertDatabaseHas('sidebar_items', [
            'type' => 'group',
            'label' => 'Tercero',
            'parent_id' => null,
            'sort_order' => 30,
        ]);
        $this->assertSame(20, $first->fresh()->sort_order);
        $this->assertSame(10, $second->fresh()->sort_order);
    }

    public function test_navigation_manager_can_edit_current_application_modules(): void
    {
        $this->seed(SidebarItemSeeder::class);
        $admin = $this->makeSuperAdmin();
        $messages = SidebarItem::query()->where('route_name', 'preset-messages')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(NavigationManager::class)
            ->call('editItem', $messages->id)
            ->assertSet('formOpen', true)
            ->assertSee('Mensajes predeterminados (preset-messages)')
            ->assertSet('routeName', 'preset-messages')
            ->assertSet('icon', 'messages')
            ->set('label', 'Respuestas predeterminadas')
            ->set('isActive', false)
            ->call('saveItem')
            ->assertHasNoErrors()
            ->assertSet('formOpen', false);

        $this->assertDatabaseHas('sidebar_items', [
            'id' => $messages->id,
            'label' => 'Respuestas predeterminadas',
            'route_name' => 'preset-messages',
            'icon' => 'messages',
            'is_active' => false,
        ]);
    }

    public function test_navigation_manager_can_toggle_a_module_directly(): void
    {
        $this->seed(SidebarItemSeeder::class);
        $admin = $this->makeSuperAdmin();
        $chat = SidebarItem::query()->where('route_name', 'chats')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(NavigationManager::class)
            ->assertSee('Desactivar Chats')
            ->call('toggleActive', $chat->id)
            ->assertSee('Elemento desactivado.')
            ->assertSee('Activar Chats')
            ->call('toggleActive', $chat->id)
            ->assertSee('Elemento activado.');

        $this->assertTrue($chat->fresh()->is_active);
    }

    public function test_new_users_without_profile_photos_receive_random_avatar_seeds(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Str::isUuid($user->avatar_seed));
        $this->assertSame('active', $user->status);
        $this->assertNotEmpty($user->username);
    }

    public function test_new_users_with_profile_photos_do_not_receive_generated_avatar_seeds(): void
    {
        $user = User::factory()->make();
        $user->forceFill(['profile_photo_path' => 'profile-photos/custom.jpg'])->save();

        $this->assertNull($user->avatar_seed);
    }

    public function test_suspended_users_are_logged_out_of_protected_pages(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['status' => 'suspended'])->save();

        $this->actingAs($user)->get('/users')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_role_can_be_created_with_permissions_by_module(): void
    {
        $admin = $this->makeSuperAdmin();
        $this->actingAs($admin);

        $this->get('/users')->assertOk()->assertSee('Invitar usuario')->assertSee('Agregar usuario');
        $this->get('/roles')->assertOk()->assertSee('Nuevo rol')->assertSee('Inicio: Ver');

        Livewire::test(Roles::class)
            ->call('createRole')
            ->set('name', 'Editor')
            ->set('selectedPermissions', ['users.view', 'users.update'])
            ->call('saveRole')
            ->assertHasNoErrors();

        $role = Role::findByName('Editor', 'web');
        $this->assertTrue($role->hasPermissionTo('users.view'));
        $this->assertTrue($role->hasPermissionTo('users.update'));
        $this->assertFalse($role->hasPermissionTo('roles.delete'));
    }

    public function test_user_can_be_created_with_an_assignable_role(): void
    {
        $viewUsers = Permission::findOrCreate('users.view', 'web');
        $createUsers = Permission::findOrCreate('users.create', 'web');
        $assignRoles = Permission::findOrCreate('users.assign_roles', 'web');
        $actor = User::factory()->create();
        $actor->givePermissionTo([$viewUsers, $createUsers, $assignRoles]);
        session()->put('auth.password_confirmed_at', time());

        $editor = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
        $editor->givePermissionTo($viewUsers);
        $avatarSeed = (string) Str::uuid();

        Livewire::actingAs($actor)
            ->test(Users::class)
            ->call('createUser')
            ->assertDontSee('Avatar aleatorio')
            ->set('name', 'Ana Editor')
            ->set('username', 'ana.editor')
            ->set('email', 'ana@example.com')
            ->set('phoneNumber', '+34911000000')
            ->set('password', 'StrongPassword123')
            ->set('passwordConfirmation', 'StrongPassword123')
            ->set('selectedRoles', [$editor->id])
            ->call('saveUser')
            ->assertHasNoErrors();

        $createdUser = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertTrue($createdUser->hasRole('Editor'));
        $this->assertTrue(Str::isUuid($createdUser->avatar_seed));
        $this->assertSame('ana.editor', $createdUser->username);
        $this->assertSame('+34911000000', $createdUser->phone_number);
        $this->assertSame('active', $createdUser->status);
    }

    public function test_user_manager_sends_an_invitation_and_password_setup_activates_the_account(): void
    {
        Notification::fake();
        $admin = $this->makeSuperAdmin();
        $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::findOrCreate('dashboard.view', 'web'));

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('createInvitation')
            ->set('invitationEmail', 'invite@example.com')
            ->set('invitationRoleId', $role->id)
            ->set('invitationNote', 'Welcome aboard')
            ->call('sendInvitation')
            ->assertHasNoErrors()
            ->assertSet('invitationFormOpen', false);

        $invited = User::where('email', 'invite@example.com')->firstOrFail();
        $this->assertSame('invited', $invited->status);
        $this->assertNull($invited->email_verified_at);
        $this->assertTrue($invited->hasRole('Editor'));

        Notification::assertSentOnDemand(UserInvitationNotification::class, function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === 'invite@example.com'
                && $notification->roleName === 'Editor'
                && $notification->note === 'Welcome aboard';
        });

        (new ResetUserPassword)->reset($invited, [
            'password' => 'InvitedPassword123',
            'password_confirmation' => 'InvitedPassword123',
        ]);

        $this->assertSame('active', $invited->fresh()->status);
        $this->assertNotNull($invited->fresh()->email_verified_at);
    }

    public function test_user_table_has_status_role_and_column_controls(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->get('/users')
            ->assertOk()
            ->assertSee('Avatar')
            ->assertSee('Nombre')
            ->assertSee('Correo')
            ->assertSee('Teléfono')
            ->assertSee('Estado')
            ->assertSee('Rol')
            ->assertSee('Acciones')
            ->assertDontSee("wire:click=\"sortBy('username')\"", false)
            ->assertSee('Columnas')
            ->assertSee('Invitar usuario')
            ->assertSee('Agregar usuario');
    }

    public function test_user_table_uses_a_global_confirmation_modal_for_deletes(): void
    {
        $admin = $this->makeSuperAdmin();
        User::factory()->create(['name' => 'Usuario de prueba', 'username' => 'usuario.prueba', 'email' => 'usuario.prueba@example.com']);

        $this->actingAs($admin)
            ->get('/users')
            ->assertOk()
            ->assertSee('data-confirm-action')
            ->assertSee('Confirmación requerida')
            ->assertSee('Cancelar');
    }

    public function test_user_table_filters_by_status_and_role(): void
    {
        $admin = $this->makeSuperAdmin();
        $managerRole = Role::create(['name' => 'Manager', 'guard_name' => 'web']);
        $invited = User::factory()->create(['name' => 'Invited Person', 'status' => 'invited']);
        $invited->assignRole($managerRole);
        User::factory()->create(['name' => 'Active Person', 'status' => 'active']);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('statusFilter', 'invited')
            ->assertSee('Invited Person')
            ->assertDontSee('Active Person')
            ->set('statusFilter', '')
            ->set('roleFilter', (string) $managerRole->id)
            ->assertSee('Invited Person')
            ->assertDontSee('Active Person');
    }

    public function test_user_can_be_suspended_and_reactivated_from_the_table_action(): void
    {
        $admin = $this->makeSuperAdmin();
        $target = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('toggleUserStatus', $target->id)
            ->assertHasNoErrors();

        $this->assertSame('suspended', $target->fresh()->status);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('toggleUserStatus', $target->id)
            ->assertHasNoErrors();

        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_role_assignment_cannot_grant_permissions_the_actor_does_not_have(): void
    {
        $viewUsers = Permission::findOrCreate('users.view', 'web');
        $createUsers = Permission::findOrCreate('users.create', 'web');
        $assignRoles = Permission::findOrCreate('users.assign_roles', 'web');
        $actor = User::factory()->create();
        $actor->givePermissionTo([$viewUsers, $createUsers, $assignRoles]);

        $elevated = Role::create(['name' => 'Elevated', 'guard_name' => 'web']);
        $elevated->givePermissionTo(Permission::findOrCreate('users.delete', 'web'));

        Livewire::actingAs($actor)
            ->test(Users::class)
            ->call('createUser')
            ->set('name', 'Blocked User')
            ->set('email', 'blocked@example.com')
            ->set('password', 'StrongPassword123')
            ->set('passwordConfirmation', 'StrongPassword123')
            ->set('selectedRoles', [$elevated->id])
            ->call('saveUser')
            ->assertHasErrors('selectedRoles.0');

        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    public function test_user_deletion_detaches_spatie_assignments(): void
    {
        $admin = $this->makeSuperAdmin();
        $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
        $target = User::factory()->create();
        $target->assignRole($role);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('deleteUser', $target->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('model_has_roles', [
            'model_id' => $target->id,
            'model_type' => User::class,
        ]);
    }

    public function test_role_assigned_to_a_user_cannot_be_deleted(): void
    {
        $admin = $this->makeSuperAdmin();
        $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
        User::factory()->create()->assignRole($role);

        Livewire::actingAs($admin)
            ->test(Roles::class)
            ->call('deleteRole', $role->id)
            ->assertHasErrors('deleteRole');

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Editor']);
    }

    private function makeSuperAdmin(): User
    {
        $permissions = collect(config('access.modules'))
            ->flatMap(fn (array $module, string $key) => collect($module['permissions'])
                ->keys()
                ->map(fn (string $action) => Permission::findOrCreate("{$key}.{$action}", 'web')));
        $role = Role::findOrCreate('Super Admin', 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);
        session()->put('auth.password_confirmed_at', time());

        return $user;
    }
}
