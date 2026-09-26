<?php

namespace Tests\Feature;

use App\Livewire\ProfileSecurity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_root_redirects_to_home(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/home');
    }

    public function test_dashboard_and_profile_require_authentication(): void
    {
        $this->get('/home')->assertRedirect('/login');
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('dashboard.view', 'web'));
        $this->actingAs($user);

        $this->get('/login')->assertRedirect('/home');
    }

    public function test_authenticated_pages_render_without_browser_caching(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('dashboard.view', 'web'));
        $this->actingAs($user);

        $home = $this->get('/home')->assertOk();
        $this->assertStringContainsString('no-store', $home->headers->get('Cache-Control'));

        $profile = $this->get('/profile')
            ->assertOk()
            ->assertSee('Cambiar contraseña')
            ->assertSee('Resumen de cuenta')
            ->assertSee('data-user-avatar-id="'.$user->id.'"', false)
            ->assertSee('Menú');
        $this->assertStringContainsString('no-store', $profile->headers->get('Cache-Control'));
    }

    public function test_profile_can_update_name_and_avatar_and_shows_assigned_role(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => 'Editor', 'guard_name' => 'web']));

        $this->actingAs($user);

        Livewire::test(ProfileSecurity::class)
            ->set('name', 'Ana Pérez')
            ->set('photo', UploadedFile::fake()->image('profile.png'))
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertDispatched('profile-avatar-updated')
            ->assertSee('Ana Pérez')
            ->assertSee('Editor');

        $user->refresh();

        $this->assertSame('Ana Pérez', $user->name);
        $this->assertNotEmpty($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }

    public function test_profile_can_choose_and_regenerate_eight_avatars(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(ProfileSecurity::class)
            ->assertSet('profileMode', 'avatar');
        $firstChoices = $component->get('avatarChoices');

        $this->assertCount(8, $firstChoices);

        $component->call('generateAvatarChoices');
        $newChoices = $component->get('avatarChoices');
        $selectedSeed = $newChoices[4];

        $this->assertCount(8, $newChoices);
        $this->assertNotSame($firstChoices, $newChoices);

        $component
            ->set('selectedAvatarSeed', $selectedSeed)
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertDispatched('profile-avatar-updated');

        $user->refresh();
        $this->assertSame($selectedSeed, $user->avatar_seed);
        $this->assertNull($user->profile_photo_path);
    }

    public function test_choosing_an_avatar_replaces_and_removes_an_uploaded_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $oldPhoto = UploadedFile::fake()->image('old-profile.png')->store('profile-photos', 'public');
        $user->forceFill(['profile_photo_path' => $oldPhoto])->save();
        $selectedSeed = (string) Str::uuid();

        Livewire::actingAs($user)
            ->test(ProfileSecurity::class)
            ->call('selectProfileAvatar')
            ->set('selectedAvatarSeed', $selectedSeed)
            ->call('updateProfile')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertNull($user->profile_photo_path);
        $this->assertSame($selectedSeed, $user->avatar_seed);
        Storage::disk('public')->assertMissing($oldPhoto);
    }

    public function test_fortify_displays_the_login_view(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('Iniciar sesión')
            ->assertSee(route('login.store'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
