<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
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
            ->with(['participants', 'messages' => fn ($query) => $query->orderByDesc('sent_at')->orderByDesc('id')])
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        $selectedConversation = null;
        $selectedContact = null;

        if ($request->filled('conversation_id')) {
            $selectedConversation = $conversations->firstWhere('id', $request->integer('conversation_id'));
            $selectedContact = $selectedConversation?->participants->first(fn ($participant) => $participant->id !== $user->id);
        }

        if (! $selectedConversation && $request->filled('contact_id')) {
            $selectedContact = $contacts->firstWhere('id', $request->integer('contact_id'));
            $selectedConversation = $conversations->first(function ($conversation) use ($selectedContact) {
                return $selectedContact && $conversation->participants->contains('id', $selectedContact->id);
            });
        }

        return view('chats', [
            'user' => $user,
            'contacts' => $contacts,
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'selectedContact' => $selectedContact,
        ]);
    }

    public function storeDirect(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $user = Auth::user();

        if ((int) $data['contact_id'] === (int) $user->id) {
            abort(422, 'No puedes iniciar un chat contigo mismo.');
        }

        $contact = User::query()->findOrFail($data['contact_id']);

        $conversation = ChatConversation::query()
            ->where('type', 'direct')
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
        }

        $message = ChatMessage::query()->create([
            'chat_conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'direction' => 'outgoing',
            'body' => trim($data['body']),
            'sent_at' => now(),
        ]);

        $conversation->update(['last_message_at' => $message->sent_at]);

        return redirect()->route('chats', ['contact_id' => $contact->id]);
    }

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'participants' => ['required', 'array'],
            'participants.*' => ['integer', 'exists:users,id'],
        ]);

        $user = Auth::user();
        $participants = array_values(array_unique([...$data['participants'], $user->id]));

        $group = ChatConversation::query()->create([
            'type' => 'group',
            'name' => $data['name'],
            'created_by' => $user->id,
        ]);

        $group->participants()->sync($participants);

        return back()->with('status', 'Grupo creado correctamente.');
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

        return redirect()->route('chats', ['conversation_id' => $conversation->id]);
    }

    public function destroyConversation(ChatConversation $conversation)
    {
        $user = Auth::user();

        if (! $conversation->participants->contains($user->id)) {
            abort(403);
        }

        $conversation->participants()->detach($user->id);

        if ($conversation->participants()->count() === 0) {
            $conversation->messages()->delete();
            $conversation->delete();
        }

        return redirect()->route('chats');
    }

    public function updateMessage(Request $request, ChatConversation $conversation, ChatMessage $message)
    {
        $user = Auth::user();

        if ($message->user_id !== $user->id || $message->chat_conversation_id !== $conversation->id) {
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

        if ($message->user_id !== $user->id || $message->chat_conversation_id !== $conversation->id) {
            abort(403);
        }

        $message->delete();

        $lastMessage = $conversation->messages()->orderByDesc('sent_at')->orderByDesc('id')->first();
        $conversation->update(['last_message_at' => $lastMessage?->sent_at]);

        return redirect()->route('chats', ['conversation_id' => $conversation->id]);
    }
}
