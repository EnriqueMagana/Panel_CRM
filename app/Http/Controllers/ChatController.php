<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $contacts = User::query()
            ->whereKeyNot($user->id)
            ->orderBy('name')
            ->get();

        $conversations = ChatConversation::query()
            ->with(['participants', 'latestMessage'])
            ->withCount([
                'messages as unread_count' => fn ($query) => $query
                    ->whereNull('read_at')
                    ->where('user_id', '!=', $user->id),
            ])
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        $selectedConversation = null;
        $selectedContact = null;
        $messages = collect();

        if ($request->filled('conversation_id')) {
            $selectedConversation = $conversations->firstWhere('id', $request->integer('conversation_id'));
            $selectedContact = $selectedConversation?->participants->first(fn ($participant) => $participant->id !== $user->id);
        }

        if (! $selectedConversation && $request->filled('contact_id')) {
            $selectedContact = $contacts->firstWhere('id', $request->integer('contact_id'));
            $selectedConversation = $conversations->first(function ($conversation) use ($selectedContact, $user) {
                return $selectedContact
                    && $conversation->type === 'direct'
                    && $conversation->participants->count() === 2
                    && $conversation->participants->contains('id', $selectedContact->id)
                    && $conversation->participants->contains('id', $user->id);
            });
        }

        if ($selectedConversation) {
            if ($selectedConversation->type === 'direct') {
                $selectedContact ??= $selectedConversation->participants
                    ->first(fn ($participant) => $participant->id !== $user->id);
            }

            $selectedConversation->messagesVisibleTo($user)
                ->where('user_id', '!=', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $messages = $selectedConversation->messagesVisibleTo($user)
                ->with('user')
                ->orderBy('sent_at')
                ->orderBy('id')
                ->get();
        }

        return view('chats', [
            'user' => $user,
            'contacts' => $contacts,
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'selectedContact' => $selectedContact,
            'messages' => $messages,
        ]);
    }

    public function storeDirect(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = Auth::user();

        if ((int) $data['contact_id'] === (int) $user->id) {
            abort(422, 'No puedes iniciar un chat contigo mismo.');
        }

        $contact = User::query()->findOrFail($data['contact_id']);

        $conversation = ChatConversation::query()
            ->where('type', 'direct')
            ->has('participants', '=', 2)
            ->whereHas('participants', fn ($query) => $query->where('users.id', $user->id))
            ->whereHas('participants', fn ($query) => $query->where('users.id', $contact->id))
            ->first();

        if (! $conversation) {
            $conversation = ChatConversation::query()->create([
                'type' => 'direct',
                'name' => $contact->name,
                'created_by' => $user->id,
            ]);

            $conversation->participants()->syncWithoutDetaching([$user->id, $contact->id]);
        } else {
            $conversation->participants()->updateExistingPivot($user->id, ['hidden_at' => null]);
        }

        if (filled($data['body'] ?? null)) {
            $message = ChatMessage::query()->create([
                'chat_conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'direction' => 'outgoing',
                'body' => trim($data['body']),
                'sent_at' => now(),
            ]);

            $conversation->update(['last_message_at' => $message->sent_at]);
            $conversation->participants()->newPivotStatement()
                ->where('chat_conversation_id', $conversation->id)
                ->update(['hidden_at' => null, 'updated_at' => now()]);
        }

        return redirect()->route('chats', ['conversation_id' => $conversation->id]);
    }

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $user = Auth::user();
        $participants = array_values(array_unique([...$data['participants'], $user->id]));

        $group = ChatConversation::query()->create([
            'type' => 'group',
            'name' => $data['name'],
            'created_by' => $user->id,
        ]);

        $group->participants()->sync($participants);

        return redirect()->route('chats', ['conversation_id' => $group->id])
            ->with('status', 'Grupo creado correctamente.');
    }

    public function storeMessage(Request $request, ChatConversation $conversation)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $user = Auth::user();

        if (! $conversation->participants->contains($user->id)) {
            abort(403);
        }

        $message = ChatMessage::query()->create([
            'chat_conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'direction' => 'outgoing',
            'body' => trim($data['body']),
            'sent_at' => now(),
        ]);

        $conversation->update(['last_message_at' => $message->sent_at]);
        $conversation->participants()->newPivotStatement()
            ->where('chat_conversation_id', $conversation->id)
            ->update(['hidden_at' => null, 'updated_at' => now()]);

        return redirect()->route('chats', ['conversation_id' => $conversation->id]);
    }

    public function destroyConversation(ChatConversation $conversation, ChatService $chat)
    {
        $user = Auth::user();

        if (! $conversation->participants->contains($user->id)) {
            abort(403);
        }

        if ($conversation->type === 'direct') {
            $chat->hideDirectConversation($conversation, $user);
        } else {
            $chat->leave($conversation, $user);
        }

        return redirect()->route('chats');
    }

    public function updateMessage(Request $request, ChatConversation $conversation, ChatMessage $message)
    {
        $user = Auth::user();

        if ($message->type === 'system' || $message->user_id !== $user->id || $message->chat_conversation_id !== $conversation->id) {
            abort(403);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $message->update([
            'body' => trim($data['body']),
        ]);

        return redirect()->route('chats', ['conversation_id' => $conversation->id]);
    }

    public function destroyMessage(ChatConversation $conversation, ChatMessage $message)
    {
        $user = Auth::user();

        if ($message->type === 'system' || $message->user_id !== $user->id || $message->chat_conversation_id !== $conversation->id) {
            abort(403);
        }

        $message->delete();

        $lastMessage = $conversation->messages()->orderByDesc('sent_at')->orderByDesc('id')->first();
        $conversation->update(['last_message_at' => $lastMessage?->sent_at]);

        return redirect()->route('chats', ['conversation_id' => $conversation->id]);
    }
}
