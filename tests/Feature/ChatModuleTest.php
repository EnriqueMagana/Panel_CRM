<?php

namespace Tests\Feature;

use App\Livewire\ChatWorkspace;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ChatModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_chat_module(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/chats')
            ->assertOk();
    }

    public function test_user_can_start_a_real_direct_conversation_from_the_ui(): void
    {
        $user = User::factory()->create();
        $contact = User::factory()->create();

        $this->actingAs($user)
            ->post('/chats/direct', [
                'contact_id' => $contact->id,
                'body' => 'Hola, quiero empezar esta conversación.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('chat_conversation_user', [
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $user->id,
            'body' => 'Hola, quiero empezar esta conversación.',
        ]);
    }

    public function test_user_can_see_message_input_when_selecting_a_new_contact_without_existing_chat(): void
    {
        $user = User::factory()->create();
        $contact = User::factory()->create();

        $this->actingAs($user)
            ->get('/chats?contact_id='.$contact->id)
            ->assertOk()
            ->assertSee('wire:submit="sendMessage"', false)
            ->assertSee('wire:model="messageBody"', false)
            ->assertSee($contact->name);
    }

    public function test_chat_page_is_a_livewire_workspace_without_traditional_chat_forms(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/chats')
            ->assertOk()
            ->assertSee('wire:name="chat-workspace"', false)
            ->assertSee('wire:click="openCreateModal"', false)
            ->assertDontSee('wire:poll', false)
            ->assertDontSee('action="'.route('chat.groups.store').'"', false);
    }

    public function test_opening_a_direct_chat_does_not_create_a_fake_message(): void
    {
        $user = User::factory()->create();
        $contact = User::factory()->create();

        $response = $this->actingAs($user)->post('/chats/direct', [
            'contact_id' => $contact->id,
        ]);

        $conversation = ChatConversation::query()->firstOrFail();

        $response->assertRedirect(route('chats', ['conversation_id' => $conversation->id]));
        $this->assertDatabaseCount('chat_messages', 0);
        $this->assertCount(2, $conversation->participants);
    }

    public function test_messages_are_rendered_in_chronological_order_and_marked_as_read(): void
    {
        $user = User::factory()->create();
        $contact = User::factory()->create();
        $conversation = ChatConversation::query()->create([
            'type' => 'direct',
            'name' => $contact->name,
            'created_by' => $user->id,
        ]);
        $conversation->participants()->attach([$user->id, $contact->id]);

        ChatMessage::query()->create([
            'chat_conversation_id' => $conversation->id,
            'user_id' => $contact->id,
            'direction' => 'incoming',
            'body' => 'Primer mensaje',
            'sent_at' => now()->subMinute(),
        ]);
        $latest = ChatMessage::query()->create([
            'chat_conversation_id' => $conversation->id,
            'user_id' => $contact->id,
            'direction' => 'incoming',
            'body' => 'Segundo mensaje',
            'sent_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/chats?conversation_id='.$conversation->id)
            ->assertOk()
            ->assertSeeInOrder(['Primer mensaje', 'Segundo mensaje']);

        $this->assertNotNull($latest->fresh()->read_at);
    }

    public function test_non_participant_cannot_send_to_a_conversation(): void
    {
        $owner = User::factory()->create();
        $contact = User::factory()->create();
        $outsider = User::factory()->create();
        $conversation = ChatConversation::query()->create([
            'type' => 'direct',
            'name' => $contact->name,
            'created_by' => $owner->id,
        ]);
        $conversation->participants()->attach([$owner->id, $contact->id]);

        $this->actingAs($outsider)
            ->post('/chats/'.$conversation->id.'/messages', ['body' => 'No autorizado'])
            ->assertForbidden();

        $this->assertDatabaseMissing('chat_messages', ['body' => 'No autorizado']);
    }

    public function test_user_can_remove_their_own_chat_thread(): void
    {
        $user = User::factory()->create();
        $contact = User::factory()->create();

        $this->actingAs($user)
            ->post('/chats/direct', [
                'contact_id' => $contact->id,
                'body' => 'Hola',
            ]);

        $conversation = ChatConversation::query()->first();

        $this->actingAs($user)
            ->delete('/chats/'.$conversation->id)
            ->assertRedirect(route('chats'));

        $this->assertDatabaseHas('chat_conversation_user', [
            'chat_conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($conversation->participants()->findOrFail($user->id)->pivot->hidden_at);
        $this->assertNull($conversation->participants()->findOrFail($contact->id)->pivot->hidden_at);
        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $conversation->id,
            'body' => 'Hola',
        ]);
    }

    public function test_user_can_edit_and_delete_own_message(): void
    {
        $user = User::factory()->create();
        $contact = User::factory()->create();

        $this->actingAs($user)
            ->post('/chats/direct', [
                'contact_id' => $contact->id,
                'body' => 'Mensaje inicial',
            ]);

        $message = ChatMessage::query()->first();

        $this->actingAs($user)
            ->put('/chats/'.$message->chat_conversation_id.'/messages/'.$message->id, [
                'body' => 'Mensaje actualizado',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('chat_messages', [
            'id' => $message->id,
            'body' => 'Mensaje actualizado',
        ]);

        $this->actingAs($user)
            ->delete('/chats/'.$message->chat_conversation_id.'/messages/'.$message->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('chat_messages', [
            'id' => $message->id,
        ]);
    }

    public function test_creator_can_create_group_with_photo_and_description_in_livewire(): void
    {
        Storage::fake('public');
        $creator = User::factory()->create();
        $member = User::factory()->create();

        Livewire::actingAs($creator)
            ->test(ChatWorkspace::class)
            ->set('groupName', 'Equipo de diseño')
            ->set('groupDescription', 'Diseño de producto y experiencia.')
            ->set('selectedParticipants', [$member->id])
            ->set('groupPhoto', UploadedFile::fake()->image('equipo.jpg', 256, 256))
            ->call('createGroup')
            ->assertHasNoErrors();

        $group = ChatConversation::query()->where('type', 'group')->firstOrFail();
        $this->assertSame('Equipo de diseño', $group->name);
        $this->assertSame('Diseño de producto y experiencia.', $group->description);
        $this->assertSame($creator->id, $group->created_by);
        $this->assertCount(2, $group->participants);
        $this->assertTrue((bool) $group->participants()->findOrFail($creator->id)->pivot->is_admin);
        Storage::disk('public')->assertExists($group->image_path);
        $this->assertSame(['small', 'medium', 'large'], array_keys($group->image_variants));
        foreach ($group->image_variants as $path) {
            $this->assertStringEndsWith('.webp', $path);
            Storage::disk('public')->assertExists($path);
        }
        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $group->id,
            'type' => 'system',
            'body' => "{$creator->name} creó el grupo",
        ]);
    }

    public function test_group_without_photo_receives_a_persistent_animated_avatar(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();

        Livewire::actingAs($creator)
            ->test(ChatWorkspace::class)
            ->set('groupName', 'Grupo sin foto')
            ->set('selectedParticipants', [$member->id])
            ->call('createGroup')
            ->assertHasNoErrors();

        $group = ChatConversation::query()->where('type', 'group')->firstOrFail();

        $this->assertNotEmpty($group->avatar_seed);

        $this->actingAs($creator)
            ->get('/chats?conversation_id='.$group->id)
            ->assertOk()
            ->assertSee('data-blobatar-seed="'.$group->avatar_seed.'"', false)
            ->assertSee('data-blobatar-animate="hover"', false);
    }

    public function test_group_photo_uses_a_relative_cacheable_media_url(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('grupo.png', 128, 128)->store('chat/group-images', 'public');
        $group = ChatConversation::query()->create([
            'type' => 'group',
            'name' => 'Grupo con foto',
            'image_path' => $path,
            'created_by' => $user->id,
        ]);
        $group->participants()->attach($user->id, ['is_admin' => true, 'joined_at' => now()]);

        $this->assertStringStartsWith('/media/chat/group-images/', $group->image_url);

        $response = $this->actingAs($user)->get($group->image_url);

        $response->assertOk();
        $this->assertStringContainsString('immutable', (string) $response->headers->get('Cache-Control'));
        $this->assertNotEmpty($response->headers->get('ETag'));
        $this->assertNotEmpty($response->headers->get('Last-Modified'));
    }

    public function test_group_creator_can_edit_group_and_manage_participants(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $newMember = User::factory()->create();
        $group = $this->groupFor($creator, [$member]);

        Livewire::actingAs($creator)
            ->test(ChatWorkspace::class)
            ->set('conversationId', $group->id)
            ->set('groupEditName', 'Nombre actualizado')
            ->set('groupEditDescription', 'Descripción actualizada')
            ->call('updateGroup')
            ->set('addParticipantIds', [$newMember->id])
            ->call('addParticipants')
            ->call('removeParticipant', $member->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('chat_conversations', [
            'id' => $group->id,
            'name' => 'Nombre actualizado',
            'description' => 'Descripción actualizada',
        ]);
        $this->assertDatabaseHas('chat_conversation_user', [
            'chat_conversation_id' => $group->id,
            'user_id' => $newMember->id,
        ]);
        $this->assertDatabaseMissing('chat_conversation_user', [
            'chat_conversation_id' => $group->id,
            'user_id' => $member->id,
        ]);
        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $group->id,
            'type' => 'system',
            'body' => "{$creator->name} agregó a {$newMember->name}",
        ]);
        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $group->id,
            'type' => 'system',
            'body' => "{$creator->name} eliminó a {$member->name}",
        ]);
    }

    public function test_non_creator_cannot_manage_group_members_or_metadata(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $another = User::factory()->create();
        $group = $this->groupFor($creator, [$member]);

        Livewire::actingAs($member)
            ->test(ChatWorkspace::class)
            ->set('conversationId', $group->id)
            ->set('groupEditName', 'Intento no autorizado')
            ->set('groupEditDescription', '')
            ->call('updateGroup')
            ->assertForbidden();

        Livewire::actingAs($member)
            ->test(ChatWorkspace::class)
            ->set('conversationId', $group->id)
            ->set('addParticipantIds', [$another->id])
            ->call('addParticipants')
            ->assertForbidden();
    }

    public function test_owner_leaving_group_transfers_ownership(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $group = $this->groupFor($creator, [$member]);

        Livewire::actingAs($creator)
            ->test(ChatWorkspace::class)
            ->set('conversationId', $group->id)
            ->call('leaveConversation')
            ->assertSet('conversationId', null);

        $this->assertSame($member->id, $group->fresh()->created_by);
        $this->assertDatabaseMissing('chat_conversation_user', [
            'chat_conversation_id' => $group->id,
            'user_id' => $creator->id,
        ]);
        $this->assertTrue((bool) $group->participants()->findOrFail($member->id)->pivot->is_admin);
        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $group->id,
            'type' => 'system',
            'body' => "{$creator->name} salió del grupo",
        ]);
    }

    public function test_participant_can_send_image_attachment_from_livewire(): void
    {
        Storage::fake('public');
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $group = $this->groupFor($creator, [$member]);

        Livewire::actingAs($member)
            ->test(ChatWorkspace::class)
            ->set('conversationId', $group->id)
            ->set('messageBody', 'Referencia visual')
            ->set('attachment', UploadedFile::fake()->image('referencia.png', 640, 480))
            ->call('sendMessage')
            ->assertHasNoErrors();

        $message = ChatMessage::query()->latest('id')->firstOrFail();
        $this->assertSame('image', $message->type);
        $this->assertSame('Referencia visual', $message->body);
        $this->assertSame('referencia.png', $message->attachment_name);
        Storage::disk('public')->assertExists($message->attachment_path);
        $this->assertSame(['small', 'medium', 'large'], array_keys($message->attachment_variants));
        foreach ($message->attachment_variants as $path) {
            $this->assertStringEndsWith('.webp', $path);
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_participant_can_send_audio_and_gif_attachments_from_livewire(): void
    {
        Storage::fake('public');
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $group = $this->groupFor($creator, [$member]);

        $component = Livewire::actingAs($member)
            ->test(ChatWorkspace::class)
            ->set('conversationId', $group->id);

        $component
            ->set('attachment', UploadedFile::fake()->create('nota-de-voz.mp3', 180, 'audio/mpeg'))
            ->call('sendMessage')
            ->assertHasNoErrors();

        $component
            ->set('attachment', UploadedFile::fake()->image('reaccion.gif', 320, 240))
            ->call('sendMessage')
            ->assertHasNoErrors();

        $audio = ChatMessage::query()->where('attachment_name', 'nota-de-voz.mp3')->firstOrFail();
        $gif = ChatMessage::query()->where('attachment_name', 'reaccion.gif')->firstOrFail();

        $this->assertSame('audio', $audio->type);
        $this->assertSame('gif', $gif->type);
        Storage::disk('public')->assertExists($audio->attachment_path);
        Storage::disk('public')->assertExists($gif->attachment_path);
        $this->assertStringEndsWith('.gif', $gif->attachment_variants['original']);
        foreach (['small', 'medium', 'large'] as $size) {
            $this->assertStringEndsWith('.webp', $gif->attachment_variants[$size]);
            Storage::disk('public')->assertExists($gif->attachment_variants[$size]);
        }
    }

    public function test_direct_chat_is_hidden_only_for_the_user_and_remains_auditable(): void
    {
        $user = User::factory()->create();
        $contact = User::factory()->create();
        $conversation = ChatConversation::query()->create([
            'type' => 'direct',
            'name' => $contact->name,
            'created_by' => $user->id,
        ]);
        $conversation->participants()->attach([
            $user->id => ['joined_at' => now()],
            $contact->id => ['joined_at' => now()],
        ]);
        $message = ChatMessage::query()->create([
            'chat_conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'direction' => 'outgoing',
            'body' => 'Mensaje para auditoría',
            'sent_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(ChatWorkspace::class)
            ->set('conversationId', $conversation->id)
            ->call('hideDirectConversation')
            ->assertSet('conversationId', null)
            ->assertSee('historial se conserva para auditoría');

        $this->assertNotNull($conversation->participants()->findOrFail($user->id)->pivot->hidden_at);
        $this->assertNull($conversation->participants()->findOrFail($contact->id)->pivot->hidden_at);
        $this->assertDatabaseHas('chat_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseHas('chat_messages', ['id' => $message->id]);

        Livewire::actingAs($contact)
            ->test(ChatWorkspace::class)
            ->assertSee('Mensaje para auditoría');
    }

    public function test_direct_chat_options_show_shared_media_and_local_delete_explanation(): void
    {
        $user = User::factory()->create();
        $contact = User::factory()->create();
        $conversation = ChatConversation::query()->create([
            'type' => 'direct',
            'name' => $contact->name,
            'created_by' => $user->id,
        ]);
        $conversation->participants()->attach([$user->id, $contact->id]);

        Livewire::actingAs($user)
            ->test(ChatWorkspace::class)
            ->set('conversationId', $conversation->id)
            ->call('openConversationInfo')
            ->assertSet('directInfoOpen', true)
            ->assertSee('Archivos compartidos')
            ->assertSee('Eliminar chat para mí')
            ->assertSee('La otra persona conservará el chat');
    }

    public function test_a_new_message_restores_a_locally_hidden_direct_chat(): void
    {
        $user = User::factory()->create();
        $contact = User::factory()->create();
        $conversation = app(ChatService::class)->findOrCreateDirect($user, $contact);
        app(ChatService::class)->hideDirectConversation($conversation, $user);

        $this->assertNotNull($conversation->participants()->findOrFail($user->id)->pivot->hidden_at);

        app(ChatService::class)->sendMessage($conversation, $contact, 'Mensaje nuevo');

        $this->assertNull($conversation->participants()->findOrFail($user->id)->pivot->hidden_at);
        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $conversation->id,
            'body' => 'Mensaje nuevo',
        ]);
    }

    public function test_group_owner_always_sees_the_add_participants_section(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $group = $this->groupFor($creator, [$member]);

        Livewire::actingAs($creator)
            ->test(ChatWorkspace::class)
            ->set('conversationId', $group->id)
            ->call('openGroupInfo')
            ->assertSee('Agregar participantes')
            ->set('addingParticipants', true)
            ->assertSee('No hay más contactos disponibles para agregar.');
    }

    public function test_membership_changes_render_as_centered_system_events(): void
    {
        $creator = User::factory()->create(['name' => 'Ana']);
        $member = User::factory()->create(['name' => 'Luis']);
        $newMember = User::factory()->create(['name' => 'María']);
        $group = $this->groupFor($creator, [$member]);

        Livewire::actingAs($creator)
            ->test(ChatWorkspace::class)
            ->set('conversationId', $group->id)
            ->set('addParticipantIds', [$newMember->id])
            ->call('addParticipants')
            ->call('removeParticipant', $member->id)
            ->assertSee('Ana agregó a María')
            ->assertSee('Ana eliminó a Luis')
            ->assertSee('chat-system-event', false)
            ->assertDontSee('wire:click="startEditingMessage', false);

        $event = ChatMessage::query()
            ->where('type', 'system')
            ->where('body', 'Ana agregó a María')
            ->firstOrFail();

        $this->assertSame('participant_added', $event->event_data['event']);
        $this->assertSame($creator->id, $event->event_data['actor_id']);
        $this->assertSame($newMember->id, $event->event_data['subject_id']);

        Livewire::actingAs($creator)
            ->test(ChatWorkspace::class)
            ->set('conversationId', $group->id)
            ->call('deleteMessage', $event->id)
            ->assertNotFound();

        $this->assertDatabaseHas('chat_messages', ['id' => $event->id, 'type' => 'system']);
    }

    private function groupFor(User $creator, array $members): ChatConversation
    {
        $group = ChatConversation::query()->create([
            'type' => 'group',
            'name' => 'Grupo de prueba',
            'created_by' => $creator->id,
        ]);

        $group->participants()->attach([
            $creator->id => ['is_admin' => true, 'joined_at' => now()->subMinute()],
        ]);

        foreach ($members as $member) {
            $group->participants()->attach($member->id, ['is_admin' => false, 'joined_at' => now()]);
        }

        return $group->refresh();
    }
}
