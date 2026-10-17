<?php

namespace Tests\Feature;

use App\Actions\Fortify\UpdateUserPassword;
use App\Livewire\IamSecurityCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class IamSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_is_terminated_after_the_idle_timeout(): void
    {
        config([
            'iam.session.idle_timeout_minutes' => 10,
            'iam.session.absolute_timeout_minutes' => 60,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertOk();
        $this->travel(11)->minutes();

        $this->get('/profile')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Tu sesión terminó por seguridad. Inicia sesión nuevamente.');
        $this->assertGuest();
    }

    public function test_authenticated_requests_renew_idle_time_but_not_absolute_time(): void
    {
        config([
            'iam.session.idle_timeout_minutes' => 10,
            'iam.session.absolute_timeout_minutes' => 20,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertOk();
        $this->travel(9)->minutes();
        $this->post('/session/keep-alive')->assertNoContent();
        $this->travel(9)->minutes();
        $this->get('/profile')->assertOk();
        $this->travel(3)->minutes();

        $this->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_internal_accounts_cannot_be_registered_publicly(): void
    {
        $this->post('/register', [
            'name' => 'External User',
            'email' => 'external@example.com',
            'password' => 'CorrectHorse!2026',
            'password_confirmation' => 'CorrectHorse!2026',
        ])->assertNotFound();
    }

    public function test_default_password_policy_requires_at_least_twelve_characters(): void
    {
        $short = Validator::make(
            ['password' => 'Short!123'],
            ['password' => ['required', Password::default()]],
        );
        $long = Validator::make(
            ['password' => 'CorrectHorse!2026'],
            ['password' => ['required', Password::default()]],
        );

        $this->assertTrue($short->fails());
        $this->assertFalse($long->fails());
    }

    public function test_authenticated_layout_contains_session_expiration_warning(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk()
            ->assertSee('data-session-timeout-dialog', false)
            ->assertSee('data-session-status-url', false)
            ->assertSee('Tu sesión está por terminar');
    }

    public function test_session_status_revalidates_authentication_without_polling(): void
    {
        $this->getJson('/session/status')->assertUnauthorized();

        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson('/session/status')->assertNoContent();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_logout_invalidates_session_before_a_protected_page_can_be_revisited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertOk();
        $this->post('/logout')->assertRedirect();
        $this->assertGuest();

        $this->get('/profile')->assertRedirect(route('login'));
        $this->getJson('/session/status')->assertUnauthorized();
    }

    public function test_successful_and_failed_logins_are_written_to_the_iam_audit_log(): void
    {
        $user = User::factory()->create(['password' => Hash::make('CorrectHorse!2026')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'incorrect-password']);
        $this->post('/login', ['email' => $user->email, 'password' => 'CorrectHorse!2026']);

        $this->assertDatabaseHas('iam_audit_logs', ['event' => 'auth.failed', 'subject_user_id' => $user->id]);
        $this->assertDatabaseHas('iam_audit_logs', ['event' => 'auth.login', 'actor_id' => $user->id]);
    }

    public function test_imported_account_must_change_its_password_before_using_the_application(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->get('/home')
            ->assertRedirect(route('profile').'#password-heading');
        $this->get('/profile')->assertOk()->assertSee('Debes cambiar tu contrase');

        app(UpdateUserPassword::class)->update($user, [
            'current_password' => 'password',
            'password' => 'NewSecurePassword!2026',
            'password_confirmation' => 'NewSecurePassword!2026',
        ]);

        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertNotNull($user->fresh()->password_changed_at);
        $this->assertDatabaseHas('iam_audit_logs', ['event' => 'password.changed', 'subject_user_id' => $user->id]);
    }

    public function test_authorized_administrator_can_inspect_and_remotely_revoke_a_session(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create(['name' => 'Agente remoto']);
        $admin->givePermissionTo([
            Permission::findOrCreate('technical_center.view_sessions', 'web'),
            Permission::findOrCreate('technical_center.revoke_sessions', 'web'),
        ]);
        DB::table('sessions')->insert([
            'id' => 'remote-session-id',
            'user_id' => $target->id,
            'ip_address' => '10.20.30.40',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/140.0 Safari/537.36 Edg/140.0',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);
        DB::table('sessions')->insert([
            'id' => 'remote-session-duplicate',
            'user_id' => $target->id,
            'ip_address' => '10.20.30.40',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/140.0 Safari/537.36 Edg/140.0',
            'payload' => '',
            'last_activity' => now()->subMinute()->timestamp,
        ]);
        DB::table('sessions')->insert([
            'id' => 'expired-session-id',
            'user_id' => $target->id,
            'ip_address' => '192.0.2.10',
            'user_agent' => 'Mozilla/5.0 (Linux) Firefox/140.0',
            'payload' => '',
            'last_activity' => now()->subHours(2)->timestamp,
        ]);
        session()->put('auth.password_confirmed_at', time());

        Livewire::actingAs($admin)
            ->test(IamSecurityCenter::class)
            ->call('selectTool', 'sessions')
            ->assertSee('Agente remoto')
            ->assertSee('10.20.30.40')
            ->assertSee('2 registros agrupados')
            ->assertDontSee('192.0.2.10')
            ->assertSee('Windows')
            ->assertSee('Microsoft Edge')
            ->call('revokeSession', 'remote-session-id')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'remote-session-id']);
        $this->assertDatabaseMissing('sessions', ['id' => 'remote-session-duplicate']);
        $this->assertDatabaseHas('iam_audit_logs', [
            'event' => 'session.revoked',
            'actor_id' => $admin->id,
            'subject_user_id' => $target->id,
        ]);
    }

    public function test_remote_session_revocation_requires_recent_authentication(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('technical_center.revoke_sessions', 'web'));
        DB::table('sessions')->insert([
            'id' => 'protected-session-id',
            'user_id' => User::factory()->create()->id,
            'ip_address' => '10.0.0.2',
            'user_agent' => 'Mozilla/5.0',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        Livewire::actingAs($admin)
            ->test(IamSecurityCenter::class)
            ->call('revokeSession', 'protected-session-id')
            ->assertRedirect(route('password.confirm'));

        $this->assertDatabaseHas('sessions', ['id' => 'protected-session-id']);
    }

    public function test_application_has_no_network_polling(): void
    {
        $javascript = file_get_contents(resource_path('js/app.js'));
        $views = collect(glob(resource_path('views/**/*.blade.php')))
            ->map(fn (string $path) => file_get_contents($path))
            ->implode("\n");

        $this->assertStringNotContainsString('scheduleChatRefresh', $javascript);
        $this->assertStringNotContainsString('setInterval(', $javascript);
        $this->assertStringNotContainsString('wire:poll', $views);
    }
}
