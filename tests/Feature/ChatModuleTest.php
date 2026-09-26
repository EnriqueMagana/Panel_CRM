<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->get('/chats?contact_id=' . $contact->id)
            ->assertOk()
            ->assertSee('name="contact_id"', false)
            ->assertSee('name="body"', false)
            ->assertSee($contact->name);
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

        $conversation = \App\Models\ChatConversation::query()->first();

        $this->actingAs($user)
            ->delete('/chats/' . $conversation->id)
            ->assertRedirect(route('chats'));

        $this->assertDatabaseMissing('chat_conversation_user', [
            'chat_conversation_id' => $conversation->id,
            'user_id' => $user->id,
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

        $message = \App\Models\ChatMessage::query()->first();

        $this->actingAs($user)
            ->put('/chats/' . $message->chat_conversation_id . '/messages/' . $message->id, [
                'body' => 'Mensaje actualizado',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('chat_messages', [
            'id' => $message->id,
            'body' => 'Mensaje actualizado',
        ]);

        $this->actingAs($user)
            ->delete('/chats/' . $message->chat_conversation_id . '/messages/' . $message->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('chat_messages', [
            'id' => $message->id,
        ]);
    }
}
