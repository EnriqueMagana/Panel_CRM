<?php

namespace Tests\Feature;

use App\Enums\ChatHistoryVisibility;
use App\Livewire\ChatWorkspace;
use App\Livewire\TechnicalCenter;
use App\Models\User;
use App\Services\ChatService;
use App\Services\TechnicalSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TechnicalCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_technical_center_requires_permission_and_saves_chat_policy_with_livewire(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/technical-center')->assertForbidden();

        $user->givePermissionTo([
            Permission::findOrCreate('technical_center.view', 'web'),
            Permission::findOrCreate('technical_center.manage', 'web'),
        ]);

        $this->actingAs($user)
            ->get('/technical-center')
            ->assertOk()
            ->assertSee('Centro técnico')
            ->assertSee('Historial para nuevos participantes');

        Livewire::actingAs($user)
            ->test(TechnicalCenter::class)
            ->set('chatHistoryVisibility', ChatHistoryVisibility::SinceAdded->value)
            ->call('saveChatSettings')
            ->assertHasNoErrors()
            ->assertSet('saved', true)
            ->assertSee('Configuración guardada.');

        $this->assertDatabaseHas('system_settings', [
            'key' => TechnicalSettings::CHAT_NEW_PARTICIPANT_HISTORY,
            'value' => ChatHistoryVisibility::SinceAdded->value,
            'updated_by' => $user->id,
        ]);
    }

    public function test_history_policy_is_snapshotted_when_a_participant_is_added(): void
    {
        $owner = User::factory()->create(['name' => 'Ana']);
        $member = User::factory()->create(['name' => 'Luis']);
        $newMember = User::factory()->create(['name' => 'María']);
        $laterMember = User::factory()->create(['name' => 'Elena']);
        $chat = app(ChatService::class);
        $settings = app(TechnicalSettings::class);
        $group = $chat->createGroup($owner, 'Equipo', null, [$member->id]);

        $oldMessage = $chat->sendMessage($group, $owner, 'Mensaje anterior');
        $settings->updateChatHistoryVisibility(ChatHistoryVisibility::SinceAdded, $owner);
        $chat->addParticipants($group, $owner, [$newMember->id]);
        $chat->sendMessage($group, $owner, 'Mensaje posterior');

        $visibleToNewMember = $group->messagesVisibleTo($newMember)->pluck('body');
        $this->assertNotContains('Mensaje anterior', $visibleToNewMember);
        $this->assertContains('Ana agregó a María', $visibleToNewMember);
        $this->assertContains('Mensaje posterior', $visibleToNewMember);
        $this->assertSame(
            $oldMessage->id,
            $group->participants()->findOrFail($newMember->id)->pivot->history_visible_after_message_id,
        );

        $settings->updateChatHistoryVisibility(ChatHistoryVisibility::All, $owner);
        $this->assertNotContains('Mensaje anterior', $group->messagesVisibleTo($newMember)->pluck('body'));

        $chat->addParticipants($group, $owner, [$laterMember->id]);
        $this->assertContains('Mensaje anterior', $group->messagesVisibleTo($laterMember)->pluck('body'));

        Livewire::actingAs($newMember)
            ->test(ChatWorkspace::class)
            ->set('conversationId', $group->id)
            ->assertDontSee('Mensaje anterior')
            ->assertSee('Mensaje posterior');
    }

    public function test_non_participant_cannot_read_messages_through_visibility_query(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $chat = app(ChatService::class);
        $group = $chat->createGroup($owner, 'Privado', null, [$member->id]);
        $chat->sendMessage($group, $owner, 'Contenido privado');

        $this->assertCount(0, $group->messagesVisibleTo($outsider)->get());
    }
}
