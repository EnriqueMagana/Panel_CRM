<?php

namespace Tests\Feature;

use App\Livewire\TicketEscalationForm;
use App\Livewire\TicketEscalations;
use App\Livewire\TicketQuickSearch;
use App\Models\SidebarItem;
use App\Models\TicketEscalation;
use App\Models\TicketEscalationAttachment;
use App\Models\TicketEscalationComment;
use App\Models\User;
use Database\Seeders\SidebarItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TicketEscalationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_requires_module_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/ticket-escalations')->assertForbidden();

        $user->givePermissionTo(Permission::findOrCreate('ticket_escalations.view', 'web'));

        $this->actingAs($user)
            ->get('/ticket-escalations')
            ->assertOk()
            ->assertSee('Escalado de tickets');
    }

    public function test_user_can_register_and_update_an_escalated_ticket(): void
    {
        $user = $this->userWithPermissions(['view', 'create', 'update']);

        Livewire::actingAs($user)
            ->test(TicketEscalationForm::class)
            ->call('open')
            ->set('folio', 'inc-10482')
            ->set('title', 'VPN sin acceso')
            ->set('escalatedArea', 'Infraestructura')
            ->set('description', 'El usuario no puede ingresar a la red corporativa.')
            ->set('status', 'in_progress')
            ->set('escalatedAt', '2026-10-16')
            ->call('saveEscalation')
            ->assertHasNoErrors()
            ->assertSet('formOpen', false);

        $escalation = TicketEscalation::query()->firstOrFail();
        $this->assertSame($user->id, $escalation->created_by);

        Livewire::actingAs($user)
            ->test(TicketEscalationForm::class)
            ->call('open', $escalation->id)
            ->set('title', 'VPN restablecida')
            ->set('status', 'resolved')
            ->call('saveEscalation')
            ->assertHasNoErrors()
            ->assertSet('formOpen', false);

        $this->assertDatabaseHas('ticket_escalations', [
            'id' => $escalation->id,
            'title' => 'VPN restablecida',
            'status' => 'resolved',
        ]);
    }

    public function test_all_users_can_search_global_escalations_by_folio_title_or_description(): void
    {
        $creator = User::factory()->create(['name' => 'Ana Soporte']);
        $viewer = $this->userWithPermissions(['view']);

        TicketEscalation::query()->create([
            'created_by' => $creator->id,
            'folio' => 'REQ-7781',
            'title' => 'Restablecer token',
            'description' => 'El dispositivo corporativo fue reemplazado.',
            'status' => 'pending',
            'escalated_at' => '2026-10-15',
        ]);
        TicketEscalation::query()->create([
            'created_by' => $creator->id,
            'folio' => 'INC-9000',
            'title' => 'Impresora',
            'description' => 'Atasco de papel.',
            'status' => 'waiting',
            'escalated_at' => '2026-10-14',
        ]);

        foreach (['REQ-7781', 'Restablecer', 'corporativo'] as $term) {
            Livewire::actingAs($viewer)
                ->test(TicketEscalations::class)
                ->set('search', $term)
                ->assertSee('Restablecer token')
                ->assertDontSee('Impresora')
                ->assertSee('Ana Soporte');
        }
    }

    public function test_user_cannot_edit_or_delete_another_users_escalation(): void
    {
        $owner = User::factory()->create();
        $collaborator = $this->userWithPermissions(['view', 'update', 'delete']);
        $escalation = TicketEscalation::query()->create([
            'created_by' => $owner->id,
            'folio' => 'INC-403',
            'title' => 'Caso protegido',
            'description' => 'Solo el creador puede administrarlo.',
            'status' => 'pending',
            'escalated_at' => '2026-10-16',
        ]);

        Livewire::actingAs($collaborator)
            ->test(TicketEscalationForm::class)
            ->call('open', $escalation->id)
            ->assertStatus(403);

        Livewire::actingAs($collaborator)
            ->test(TicketEscalations::class)
            ->call('deleteEscalation', $escalation->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('ticket_escalations', ['id' => $escalation->id]);
    }

    public function test_owner_can_delete_own_escalation_and_sidebar_seed_includes_module(): void
    {
        $this->seed(SidebarItemSeeder::class);
        $owner = $this->userWithPermissions(['view', 'delete']);
        $escalation = TicketEscalation::query()->create([
            'created_by' => $owner->id,
            'folio' => 'INC-DELETE',
            'title' => 'Caso resuelto',
            'description' => 'Registro de prueba.',
            'status' => 'resolved',
            'escalated_at' => '2026-10-16',
        ]);

        Livewire::actingAs($owner)
            ->test(TicketEscalations::class)
            ->call('deleteEscalation', $escalation->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('ticket_escalations', ['id' => $escalation->id]);
        $this->assertDatabaseHas('sidebar_items', [
            'route_name' => 'ticket-escalations',
            'permission_name' => 'ticket_escalations.view',
            'icon' => 'ticket',
            'is_active' => true,
        ]);
    }

    public function test_ticket_accepts_area_files_and_collaborative_immutable_comments(): void
    {
        Storage::fake('public');
        $owner = $this->userWithPermissions(['view', 'create', 'update']);
        $viewer = $this->userWithPermissions(['view']);

        Livewire::actingAs($owner)
            ->test(TicketEscalationForm::class)
            ->call('open')
            ->set('folio', 'INC-IMAGES')
            ->set('title', 'Captura del incidente')
            ->set('escalatedArea', 'Seguridad de la información')
            ->set('description', 'Descripción original del ticket.')
            ->set('status', 'pending')
            ->set('escalatedAt', '2026-10-16')
            ->set('ticketUploads', [UploadedFile::fake()->image('evidencia.png')])
            ->call('saveEscalation')
            ->assertHasNoErrors();

        $escalation = TicketEscalation::query()->firstOrFail();
        $this->assertSame('Seguridad de la información', $escalation->escalated_area);
        $this->assertCount(1, $escalation->ticketAttachments);
        Storage::disk('public')->assertExists($escalation->ticketAttachments->first()->path);

        Livewire::actingAs($viewer)
            ->test(TicketEscalations::class)
            ->set("commentBodies.{$escalation->id}", 'El área confirma que inició la revisión.')
            ->set("commentUploads.{$escalation->id}", [UploadedFile::fake()->image('respuesta.png')])
            ->call('addComment', $escalation->id)
            ->assertHasNoErrors()
            ->assertSee('El área confirma que inició la revisión.');

        $comment = $escalation->comments()->firstOrFail();
        $this->assertSame($viewer->id, $comment->user_id);
        $this->assertCount(1, $comment->attachments);
        $this->assertSame('Descripción original del ticket.', $escalation->fresh()->description);
    }

    public function test_ticket_form_is_persistent_until_cancel_or_save(): void
    {
        $user = $this->userWithPermissions(['view', 'create']);

        Livewire::actingAs($user)
            ->test(TicketEscalationForm::class)
            ->call('open')
            ->assertSet('formOpen', true)
            ->assertSeeHtml('data-modal-dismissible="false"')
            ->set('title', 'Borrador persistente')
            ->set('description', 'Contenido que no debe perderse')
            ->assertSet('formOpen', true)
            ->assertSet('title', 'Borrador persistente')
            ->call('cancelForm')
            ->assertSet('formOpen', false);
    }

    public function test_ticket_avatars_are_preserved_during_livewire_updates(): void
    {
        $owner = $this->userWithPermissions(['view', 'update']);
        TicketEscalation::query()->create([
            'created_by' => $owner->id,
            'folio' => 'INC-AVATAR',
            'title' => 'Avatar persistente',
            'description' => 'El avatar no debe ser reconstruido por el morph de Livewire.',
            'status' => 'pending',
            'escalated_at' => '2026-10-16',
        ]);

        Livewire::actingAs($owner)
            ->test(TicketEscalations::class)
            ->assertSeeHtml('wire:ignore')
            ->assertSeeHtml('data-user-avatar-id="'.$owner->id.'"')
            ->assertSee('$dispatchTo', false);
    }

    public function test_quick_search_displays_ticket_comments_and_evidence_without_navigation(): void
    {
        $viewer = $this->userWithPermissions(['view']);
        $creator = User::factory()->create(['name' => 'Creador del ticket']);
        $commenter = User::factory()->create(['name' => 'Analista colaborador']);
        $ticket = TicketEscalation::query()->create([
            'created_by' => $creator->id,
            'folio' => 'INC-QUICK-908',
            'title' => 'Consulta desde mensajes',
            'escalated_area' => 'Infraestructura',
            'description' => 'Contenido completo visible desde el buscador rápido.',
            'status' => 'in_progress',
            'escalated_at' => '2026-10-16',
        ]);
        $comment = TicketEscalationComment::query()->create([
            'ticket_escalation_id' => $ticket->id,
            'user_id' => $commenter->id,
            'body' => 'Se adjuntó la validación del servidor.',
        ]);
        TicketEscalationAttachment::query()->create([
            'ticket_escalation_id' => $ticket->id,
            'ticket_escalation_comment_id' => null,
            'uploaded_by' => $creator->id,
            'path' => 'ticket-escalations/evidencia.png',
            'original_name' => 'evidencia-inicial.png',
            'mime_type' => 'image/png',
            'size' => 2048,
        ]);
        TicketEscalationAttachment::query()->create([
            'ticket_escalation_id' => $ticket->id,
            'ticket_escalation_comment_id' => $comment->id,
            'uploaded_by' => $commenter->id,
            'path' => 'ticket-escalations/validacion.pdf',
            'original_name' => 'validacion-servidor.pdf',
            'mime_type' => 'application/pdf',
            'size' => 4096,
        ]);

        Livewire::actingAs($viewer)
            ->test(TicketQuickSearch::class)
            ->call('openSearch')
            ->assertSet('open', true)
            ->assertSee('Consulta desde mensajes')
            ->set('search', 'QUICK-908')
            ->assertSee('INC-QUICK-908')
            ->call('selectTicket', $ticket->id)
            ->assertSee('Contenido completo visible desde el buscador rápido.')
            ->assertSee('Infraestructura')
            ->assertSee('Creador del ticket')
            ->assertSee('Se adjuntó la validación del servidor.')
            ->assertSee('Analista colaborador')
            ->assertSee('evidencia-inicial.png')
            ->assertSee('validacion-servidor.pdf');
    }

    public function test_quick_search_respects_disabled_module_and_is_available_from_productivity_fab(): void
    {
        $this->seed(SidebarItemSeeder::class);
        $viewer = $this->userWithPermissions(['view']);
        $viewer->givePermissionTo(Permission::findOrCreate('preset_messages.view', 'web'));

        $response = $this->actingAs($viewer)->get('/preset-messages');
        $response
            ->assertOk()
            ->assertSee('data-fab-ticket-search', false)
            ->assertSeeLivewire(TicketQuickSearch::class);
        $this->assertSame(1, substr_count($response->getContent(), 'data-fab-ticket-search'));

        SidebarItem::query()->where('route_name', 'ticket-escalations')->update(['is_active' => false]);

        Livewire::actingAs($viewer)
            ->test(TicketQuickSearch::class)
            ->call('openSearch')
            ->assertStatus(403);
    }

    private function userWithPermissions(array $actions): User
    {
        $user = User::factory()->create();
        $permissions = collect($actions)
            ->map(fn (string $action) => Permission::findOrCreate("ticket_escalations.{$action}", 'web'));
        $user->givePermissionTo($permissions);

        return $user;
    }
}
