<?php

namespace Tests\Feature;

use App\Livewire\BrowserExtensionSettings;
use App\Livewire\PresetMessages;
use App\Models\BrowserExtension;
use App\Models\BrowserExtensionConnection;
use App\Models\PresetMessage;
use App\Models\PresetMessageUsageEvent;
use App\Models\SidebarItem;
use App\Models\User;
use Database\Seeders\BrowserExtensionSeeder;
use Database\Seeders\SidebarItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use ZipArchive;

class ProductivityInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_chat_module_blocks_direct_page_and_action_urls(): void
    {
        $this->seed(SidebarItemSeeder::class);
        SidebarItem::query()->where('route_name', 'chats')->update(['is_active' => false]);
        $this->seed(SidebarItemSeeder::class);
        $this->assertFalse(SidebarItem::query()->where('route_name', 'chats')->firstOrFail()->is_active);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/chats')->assertForbidden();
        $this->actingAs($user)->post('/chats/direct', [])->assertForbidden();
    }

    public function test_browser_extension_page_explains_setup_and_can_be_connected(): void
    {
        $this->seed(BrowserExtensionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('browser_extensions.view', 'web'),
            Permission::findOrCreate('browser_extensions.manage', 'web'),
        ]);

        $this->actingAs($user)->get('/extensions/browser')
            ->assertOk()
            ->assertSee('Catálogo de extensiones')
            ->assertSee('Snippetdesk para navegador')
            ->assertSee('Configurar una extensión en Chrome o Edge');

        Livewire::actingAs($user)->test(BrowserExtensionSettings::class)
            ->dispatch('browser-extension-detected', browser: 'Chrome')
            ->assertSet('connected', true)
            ->assertSee('Extensión detectada y vinculada correctamente.');

        $this->assertDatabaseHas('browser_extension_connections', [
            'user_id' => $user->id,
            'browser_name' => 'Chrome',
        ]);
    }

    public function test_manager_can_add_a_valid_extension_zip_to_the_catalog(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('browser_extensions.view', 'web'),
            Permission::findOrCreate('browser_extensions.manage', 'web'),
        ]);

        Livewire::actingAs($user)->test(BrowserExtensionSettings::class)
            ->call('openExtensionForm')
            ->set('extensionName', 'Asistente de tickets')
            ->set('extensionVersion', '2.1.0')
            ->set('extensionDescription', 'Captura datos del ticket y prepara respuestas frecuentes.')
            ->set('extensionFeatures', "Captura el folio\nInserta respuestas")
            ->set('extensionBrowsers', 'Chrome, Edge')
            ->set('extensionZip', $this->makeExtensionZip())
            ->call('saveExtension')
            ->assertHasNoErrors()
            ->assertSet('formOpen', false)
            ->assertSee('Asistente de tickets');

        $extension = BrowserExtension::query()->where('slug', 'asistente-de-tickets')->firstOrFail();
        Storage::disk('public')->assertExists($extension->zip_path);
        $this->assertSame(['Captura el folio', 'Inserta respuestas'], $extension->features);
    }

    public function test_inactive_extension_download_is_hidden_from_regular_users(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('browser-extensions/private.zip', 'zip-content');
        $extension = BrowserExtension::query()->create([
            'name' => 'Extensión privada',
            'slug' => 'extension-privada',
            'version' => '1.0.0',
            'description' => 'Todavía no está publicada.',
            'supported_browsers' => 'Chrome',
            'zip_path' => 'browser-extensions/private.zip',
            'zip_size' => 11,
            'is_active' => false,
        ]);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permission::findOrCreate('browser_extensions.view', 'web'));

        $this->actingAs($viewer)
            ->get(route('browser-extensions.download', $extension))
            ->assertNotFound();
    }

    public function test_browser_paste_action_only_appears_after_extension_is_configured(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('preset_messages.view', 'web'));
        PresetMessage::query()->create([
            'created_by' => $user->id,
            'title' => 'Seguimiento',
            'body' => 'Hola',
            'variables' => [],
        ]);

        Livewire::actingAs($user)->test(PresetMessages::class)->assertDontSee('Pegar en navegador');

        BrowserExtensionConnection::query()->create([
            'user_id' => $user->id,
            'browser_name' => 'Edge',
            'connected_at' => now(),
            'last_seen_at' => now(),
        ]);

        Livewire::actingAs($user)->test(PresetMessages::class)->assertSee('Pegar en navegador');
    }

    public function test_copy_events_and_empty_searches_feed_dashboard_analytics(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('preset_messages.view', 'web'),
            Permission::findOrCreate('dashboard.view', 'web'),
        ]);
        $message = PresetMessage::query()->create([
            'created_by' => $user->id,
            'title' => 'Respuesta frecuente',
            'body' => 'Hola, estamos revisando tu solicitud y responderemos lo antes posible.',
            'variables' => [],
        ]);

        Livewire::actingAs($user)->test(PresetMessages::class)
            ->dispatch('preset-message-used', messageId: $message->id, channel: 'clipboard')
            ->set('search', 'consulta inexistente');

        $this->assertDatabaseHas('preset_message_usage_events', [
            'user_id' => $user->id,
            'preset_message_id' => $message->id,
            'event_type' => PresetMessageUsageEvent::TYPE_COPIED,
        ]);
        $this->assertDatabaseHas('preset_message_usage_events', [
            'user_id' => $user->id,
            'event_type' => PresetMessageUsageEvent::TYPE_SEARCH_NO_RESULTS,
            'search_query' => 'consulta inexistente',
        ]);

        $this->actingAs($user)->get('/home')
            ->assertOk()
            ->assertSee('Analíticas de uso')
            ->assertSee('Respuesta frecuente')
            ->assertSee('consulta inexistente');
    }

    public function test_ninety_day_variable_renders_a_date_calculation_input(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('preset_messages.view', 'web'));
        PresetMessage::query()->create([
            'created_by' => $user->id,
            'title' => 'Desbloqueo',
            'body' => 'El usuario se desbloqueará en {{dias_para_90}}.',
            'variables' => ['dias_para_90'],
        ]);

        Livewire::actingAs($user)->test(PresetMessages::class)
            ->assertSee('Fecha inicial para calcular 90 días')
            ->assertSee('data-preset-variable-calculation="days-to-90"', false)
            ->assertSee('type="date"', false);
    }

    private function makeExtensionZip(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'snippetdesk-extension-');
        $archive = new ZipArchive;
        $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $archive->addFromString('manifest.json', json_encode([
            'manifest_version' => 3,
            'name' => 'Asistente de tickets',
            'version' => '2.1.0',
        ], JSON_THROW_ON_ERROR));
        $archive->addFromString('service-worker.js', '');
        $archive->close();

        $contents = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent('asistente-tickets.zip', $contents);
    }
}
