<?php

namespace Tests\Feature;

use App\Livewire\PresetMessages;
use App\Models\PresetMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PresetMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_requires_view_permission(): void
    {
        $this->actingAs(User::factory()->create())->get('/preset-messages')->assertForbidden();
    }

    public function test_page_uses_productivity_fab_without_the_old_header(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('preset_messages.view', 'web'),
            Permission::findOrCreate('preset_messages.create', 'web'),
            Permission::findOrCreate('browser_extensions.view', 'web'),
        ]);

        $this->actingAs($user)->get('/preset-messages')
            ->assertOk()
            ->assertSee('Agregar mensaje')
            ->assertSee('Extensiones')
            ->assertSee('Chat')
            ->assertSee('Snapshot')
            ->assertSee('data-fab-avatar-shell', false)
            ->assertSee('data-fab-expression="idle"', false)
            ->assertSee('data-blobatar-animate="always"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertDontSee('data-fab-wave', false)
            ->assertDontSee('Encuentra, personaliza y copia respuestas frecuentes');
    }

    public function test_authorized_user_can_create_message_and_variables_are_extracted(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('preset_messages.view', 'web'),
            Permission::findOrCreate('preset_messages.create', 'web'),
        ]);

        Livewire::actingAs($user)->test(PresetMessages::class)
            ->call('createMessage')
            ->set('title', 'Confirmación de cita')
            ->set('body', 'Hola {{nombre}}, tu cita es el {{fecha}}. Gracias, {{nombre}}.')
            ->call('saveMessage')
            ->assertHasNoErrors();

        $message = PresetMessage::query()->firstOrFail();
        $this->assertSame(['nombre', 'fecha'], $message->variables);
        $this->assertSame($user->id, $message->created_by);
    }

    public function test_automatic_variables_do_not_generate_manual_inputs(): void
    {
        $message = new PresetMessage([
            'variables' => ['fecha', 'dia', 'hora', 'saludo', 'mi_nombre', 'nombre', 'folio'],
        ]);

        $this->assertSame(['nombre', 'folio'], $message->manualVariables());
    }

    public function test_ninety_day_variable_is_a_calculated_manual_input(): void
    {
        $message = new PresetMessage(['variables' => ['fecha', 'dias_para_90']]);

        $this->assertSame(['dias_para_90'], $message->manualVariables());
        $this->assertSame('days-to-90', PresetMessage::calculatedVariable('dias_para_90')['calculation']);
    }

    public function test_create_form_renders_safe_description_and_variable_options(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('preset_messages.view', 'web'),
            Permission::findOrCreate('preset_messages.create', 'web'),
        ]);

        Livewire::actingAs($user)->test(PresetMessages::class)
            ->call('createMessage')
            ->assertSee('Añade variables manuales o automáticas')
            ->assertSee('Variables automáticas')
            ->assertSee('Variables calculadas')
            ->assertSee('+ 90 días')
            ->assertSee('+ Fecha')
            ->assertSee('+ Nombre')
            ->assertDontSee('echo e(nombre)');
    }

    public function test_search_filters_messages(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('preset_messages.view', 'web'));
        PresetMessage::create(['created_by' => $user->id, 'title' => 'Ventas', 'body' => 'Cotización lista', 'variables' => []]);
        PresetMessage::create(['created_by' => $user->id, 'title' => 'Soporte', 'body' => 'Revisaremos tu caso', 'variables' => []]);

        Livewire::actingAs($user)->test(PresetMessages::class)
            ->set('search', 'Cotización')
            ->assertSee('Ventas')
            ->assertDontSee('Soporte');
    }

    public function test_user_sees_own_messages_separately_from_active_coworker_messages(): void
    {
        $user = User::factory()->create();
        $coworker = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('preset_messages.view', 'web'));

        PresetMessage::create([
            'created_by' => $user->id,
            'title' => 'Mensaje propio',
            'body' => 'Contenido propio',
            'variables' => [],
        ]);
        PresetMessage::create([
            'created_by' => $coworker->id,
            'title' => 'Mensaje compartido',
            'body' => 'Contenido compartido',
            'variables' => [],
            'is_active' => true,
        ]);
        PresetMessage::create([
            'created_by' => $coworker->id,
            'title' => 'Mensaje privado inactivo',
            'body' => 'No debe compartirse',
            'variables' => [],
            'is_active' => false,
        ]);

        Livewire::actingAs($user)->test(PresetMessages::class)
            ->assertSee('Mensaje propio')
            ->assertDontSee('Mensaje compartido')
            ->call('selectLibrary', 'global')
            ->assertDontSee('Mensaje propio')
            ->assertSee('Mensaje compartido')
            ->assertSee($coworker->name)
            ->assertDontSee('Mensaje privado inactivo');
    }

    public function test_user_cannot_modify_or_delete_a_coworker_message(): void
    {
        $user = User::factory()->create();
        $coworker = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('preset_messages.view', 'web'),
            Permission::findOrCreate('preset_messages.update', 'web'),
            Permission::findOrCreate('preset_messages.delete', 'web'),
        ]);
        $message = PresetMessage::create([
            'created_by' => $coworker->id,
            'title' => 'Mensaje de compañero',
            'body' => 'Solo lectura',
            'variables' => [],
        ]);

        Livewire::actingAs($user)->test(PresetMessages::class)
            ->call('editMessage', $message->id)
            ->assertForbidden();

        Livewire::actingAs($user)->test(PresetMessages::class)
            ->call('deleteMessage', $message->id)
            ->assertForbidden();

        $this->assertDatabaseHas('preset_messages', [
            'id' => $message->id,
            'body' => 'Solo lectura',
        ]);
    }

    public function test_user_can_pin_and_reorder_messages_without_affecting_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('preset_messages.view', 'web'));
        $first = PresetMessage::create(['created_by' => $user->id, 'title' => 'Primero', 'body' => 'Uno', 'variables' => []]);
        $second = PresetMessage::create(['created_by' => $user->id, 'title' => 'Segundo', 'body' => 'Dos', 'variables' => []]);

        Livewire::actingAs($user)->test(PresetMessages::class)
            ->call('togglePin', $first->id)
            ->call('togglePin', $second->id)
            ->assertSee('Fijados')
            ->call('movePinned', $second->id, $first->id, 'up')
            ->call('reorderPinned', [$first->id, $second->id]);

        $this->assertDatabaseHas('preset_message_user', [
            'user_id' => $user->id,
            'preset_message_id' => $first->id,
            'pin_order' => 1,
        ]);
        $this->assertDatabaseHas('preset_message_user', [
            'user_id' => $user->id,
            'preset_message_id' => $second->id,
            'pin_order' => 2,
        ]);
        $this->assertDatabaseMissing('preset_message_user', [
            'user_id' => $otherUser->id,
            'preset_message_id' => $first->id,
        ]);
    }

    public function test_message_appearance_preferences_are_persisted_per_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('preset_messages.view', 'web'));
        $otherUser->givePermissionTo(Permission::findOrCreate('preset_messages.view', 'web'));

        Livewire::actingAs($user)->test(PresetMessages::class)
            ->call('openAppearance')
            ->assertSee('Personalizar mensajes')
            ->assertSee('Guardar cambios')
            ->assertSee('Océano')
            ->assertSee('Lavanda')
            ->assertSee('Bosque')
            ->assertSee('Atardecer')
            ->assertSee('Minimalista')
            ->assertSee('Tamaño del texto')
            ->assertSee('type="range"', false)
            ->assertSee('min="12" max="24"', false)
            ->set('draftTheme', 'custom')
            ->set('draftLayout', 'list')
            ->set('draftDensity', 'compact')
            ->set('draftAccent', 'custom')
            ->set('draftAccentColor', '#123456')
            ->set('draftBackground', 'soft')
            ->set('draftBackgroundColor', '#f1f5f9')
            ->set('draftCardStyle', 'glass')
            ->set('draftRadius', 'large')
            ->set('draftFontSize', 18)
            ->assertSet('appearanceOpen', true)
            ->assertSet('messageLayout', 'grid')
            ->call('saveAppearance')
            ->assertSet('messageLayout', 'list')
            ->assertSet('messageDensity', 'compact')
            ->assertSet('messageAccent', 'custom')
            ->assertSet('messageBackground', 'soft')
            ->assertSet('messageCardStyle', 'glass')
            ->assertSet('appearanceOpen', false)
            ->assertSee('data-preset-layout="list"', false)
            ->assertSee('data-preset-accent="custom"', false)
            ->assertSee('--preset-message-font-size: 18px', false);

        $this->assertDatabaseHas('preset_message_preferences', [
            'user_id' => $user->id,
            'layout' => 'list',
            'density' => 'compact',
            'accent' => 'custom',
            'accent_color' => '#123456',
            'background' => 'soft',
            'background_color' => '#f1f5f9',
            'card_style' => 'glass',
            'radius' => 'large',
            'font_size' => 18,
        ]);

        Livewire::actingAs($otherUser)->test(PresetMessages::class)
            ->assertSet('messageLayout', 'grid')
            ->assertSet('messageDensity', 'comfortable')
            ->assertSet('messageAccent', 'blue')
            ->assertSet('messageBackground', 'plain');
    }

    public function test_background_image_is_saved_only_when_appearance_is_submitted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('preset_messages.view', 'web'));
        $image = UploadedFile::fake()->image('workspace.jpg', 1200, 800)->size(400);

        $component = Livewire::actingAs($user)->test(PresetMessages::class)
            ->call('openAppearance')
            ->set('draftTheme', 'custom')
            ->set('backgroundImageUpload', $image);

        $this->assertDatabaseMissing('preset_message_preferences', [
            'user_id' => $user->id,
            'background' => 'image',
        ]);

        $component->call('saveAppearance')
            ->assertHasNoErrors()
            ->assertSet('messageBackground', 'image');

        $preference = $user->presetMessagePreference()->firstOrFail();
        $this->assertNotNull($preference->background_image_path);
        Storage::disk('public')->assertExists($preference->background_image_path);
    }

    public function test_hiding_a_message_removes_its_pin_and_allows_restoring_it(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('preset_messages.view', 'web'));
        $message = PresetMessage::create([
            'created_by' => $user->id,
            'title' => 'Mensaje temporal',
            'body' => 'Contenido',
            'variables' => [],
        ]);

        Livewire::actingAs($user)->test(PresetMessages::class)
            ->call('togglePin', $message->id)
            ->call('hideMessage', $message->id)
            ->assertDontSee('Mensaje temporal')
            ->call('selectLibrary', 'hidden')
            ->assertSee('Mensaje temporal')
            ->call('restoreMessage', $message->id)
            ->assertDontSee('Mensaje temporal')
            ->call('selectLibrary', 'mine')
            ->assertSee('Mensaje temporal');

        $this->assertDatabaseHas('preset_message_user', [
            'user_id' => $user->id,
            'preset_message_id' => $message->id,
            'pinned_at' => null,
            'pin_order' => null,
            'hidden_at' => null,
        ]);
    }
}
