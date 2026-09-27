<?php

namespace App\Livewire;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatWorkspace extends Component
{
    use WithFileUploads;

    private const EMOJIS = ['😀', '😂', '😍', '👍', '👏', '🎉', '❤️', '🔥', '✅', '🙏', '👀', '🤝'];

    #[Url(as: 'conversation_id', history: true)]
    public ?int $conversationId = null;

    #[Url(as: 'contact_id', history: true)]
    public ?int $contactId = null;

    public string $search = '';

    public string $messageBody = '';

    public $attachment = null;

    public bool $createModalOpen = false;

    public string $createTab = 'contact';

    public string $contactSearch = '';

    public string $groupName = '';

    public string $groupDescription = '';

    public $groupPhoto = null;

    public array $selectedParticipants = [];

    public bool $groupInfoOpen = false;

    public bool $directInfoOpen = false;

    public bool $editingGroup = false;

    public bool $addingParticipants = false;

    public string $groupEditName = '';

    public string $groupEditDescription = '';

    public $groupEditPhoto = null;

    public array $addParticipantIds = [];

    public ?int $editingMessageId = null;

    public string $editingBody = '';

    public bool $emojiPickerOpen = false;

    public ?string $notice = null;

    public function mount(): void
    {
        if ($this->conversationId) {
            $this->markConversationAsRead();
        }

        if ($this->contactId && $this->contactId === (int) Auth::id()) {
            $this->contactId = null;
        }
    }

    public function selectConversation(int $conversationId, ChatService $chat): void
    {
        $conversation = $chat->conversationFor(Auth::user(), $conversationId);
        $this->conversationId = $conversation->id;
        $this->contactId = null;
        $this->closeTransientPanels();
        $this->markConversationAsRead();
        $this->dispatch('chat-scroll-bottom');
    }

    public function selectContact(int $contactId, ChatService $chat): void
    {
        $contact = User::query()->whereKeyNot(Auth::id())->findOrFail($contactId);
        $existing = $this->existingDirectConversation($contact);

        if ($existing) {
            $chat->restoreFor($existing, Auth::user());
        }

        $this->conversationId = $existing?->id;
        $this->contactId = $existing ? null : $contact->id;
        $this->closeTransientPanels();
        $this->dispatch('chat-scroll-bottom');
    }

    public function closeConversation(): void
    {
        $this->conversationId = null;
        $this->contactId = null;
        $this->closeTransientPanels();
    }

    public function openCreateModal(string $tab = 'contact'): void
    {
        $this->resetValidation();
        $this->createTab = in_array($tab, ['contact', 'group'], true) ? $tab : 'contact';
        $this->createModalOpen = true;
    }

    public function closeCreateModal(): void
    {
        $this->createModalOpen = false;
        $this->resetCreateForm();
    }

    public function createGroup(ChatService $chat): void
    {
        $validated = $this->validate([
            'groupName' => ['required', 'string', 'min:2', 'max:120'],
            'groupDescription' => ['nullable', 'string', 'max:1000'],
            'groupPhoto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'selectedParticipants' => ['required', 'array', 'min:1'],
            'selectedParticipants.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', Auth::id())),
            ],
        ]);

        $group = $chat->createGroup(
            Auth::user(),
            $validated['groupName'],
            $validated['groupDescription'] ?: null,
            $validated['selectedParticipants'],
            $this->groupPhoto,
        );

        $this->conversationId = $group->id;
        $this->contactId = null;
        $this->createModalOpen = false;
        $this->resetCreateForm();
        $this->notice = 'Grupo creado correctamente.';
        $this->dispatch('chat-scroll-bottom');
    }

    public function sendMessage(ChatService $chat): void
    {
        $validated = $this->validate([
            'messageBody' => ['nullable', 'string', 'max:2000'],
            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,gif,mp3,wav,ogg,m4a,aac,webm',
                'max:20480',
            ],
        ]);

        if (blank($validated['messageBody']) && ! $this->attachment) {
            $this->addError('messageBody', 'Escribe un mensaje o adjunta un archivo.');

            return;
        }

        $conversation = $this->selectedConversation();

        if (! $conversation) {
            abort_unless($this->contactId, 422);
            $contact = User::query()->whereKeyNot(Auth::id())->findOrFail($this->contactId);
            $conversation = $chat->findOrCreateDirect(Auth::user(), $contact);
            $this->conversationId = $conversation->id;
            $this->contactId = null;
        }

        $chat->sendMessage($conversation, Auth::user(), $validated['messageBody'], $this->attachment);
        $this->reset('messageBody', 'attachment');
        $this->emojiPickerOpen = false;
        $this->notice = null;
        $this->dispatch('chat-scroll-bottom');
    }

    public function appendEmoji(string $emoji): void
    {
        abort_unless(in_array($emoji, self::EMOJIS, true), 422);
        $this->messageBody .= $emoji;
        $this->emojiPickerOpen = false;
        $this->dispatch('chat-focus-composer');
    }

    public function removeAttachment(): void
    {
        $this->reset('attachment');
        $this->resetValidation('attachment');
    }

    public function startEditingMessage(int $messageId): void
    {
        $message = $this->messageOwnedByCurrentUser($messageId);
        $this->editingMessageId = $message->id;
        $this->editingBody = $message->body;
    }

    public function cancelEditingMessage(): void
    {
        $this->reset('editingMessageId', 'editingBody');
        $this->resetValidation('editingBody');
    }

    public function updateMessage(): void
    {
        $validated = $this->validate([
            'editingBody' => ['required', 'string', 'max:2000'],
        ]);

        $message = $this->messageOwnedByCurrentUser((int) $this->editingMessageId);
        $message->update(['body' => trim($validated['editingBody'])]);
        $this->cancelEditingMessage();
        $this->dispatch('chat-scroll-bottom');
    }

    public function deleteMessage(int $messageId, ChatService $chat): void
    {
        $message = $this->messageOwnedByCurrentUser($messageId);
        $chat->deleteMessage($message, Auth::user());
        $this->notice = 'Mensaje eliminado.';
    }

    public function openGroupInfo(): void
    {
        $conversation = $this->selectedConversation();
        abort_unless($conversation?->type === 'group', 422);

        $this->groupEditName = $conversation->name ?? '';
        $this->groupEditDescription = $conversation->description ?? '';
        $this->groupInfoOpen = true;
        $this->directInfoOpen = false;
        $this->editingGroup = false;
        $this->addingParticipants = false;
    }

    public function closeGroupInfo(): void
    {
        $this->groupInfoOpen = false;
        $this->editingGroup = false;
        $this->addingParticipants = false;
        $this->reset('groupEditPhoto', 'addParticipantIds');
        $this->resetValidation();
    }

    public function openConversationInfo(): void
    {
        $conversation = $this->selectedConversationOrFail();

        if ($conversation->type === 'group') {
            $this->openGroupInfo();

            return;
        }

        $this->directInfoOpen = true;
        $this->groupInfoOpen = false;
    }

    public function closeDirectInfo(): void
    {
        $this->directInfoOpen = false;
    }

    public function updateGroup(ChatService $chat): void
    {
        $validated = $this->validate([
            'groupEditName' => ['required', 'string', 'min:2', 'max:120'],
            'groupEditDescription' => ['nullable', 'string', 'max:1000'],
            'groupEditPhoto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $conversation = $this->selectedConversationOrFail();
        $chat->updateGroup(
            $conversation,
            Auth::user(),
            $validated['groupEditName'],
            $validated['groupEditDescription'] ?: null,
            $this->groupEditPhoto,
        );

        $this->editingGroup = false;
        $this->reset('groupEditPhoto');
        $this->notice = 'Información del grupo actualizada.';
    }

    public function addParticipants(ChatService $chat): void
    {
        $validated = $this->validate([
            'addParticipantIds' => ['required', 'array', 'min:1'],
            'addParticipantIds.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $chat->addParticipants($this->selectedConversationOrFail(), Auth::user(), $validated['addParticipantIds']);
        $this->reset('addParticipantIds');
        $this->addingParticipants = false;
        $this->notice = 'Participantes agregados.';
    }

    public function removeParticipant(int $participantId, ChatService $chat): void
    {
        $participant = User::query()->findOrFail($participantId);
        $chat->removeParticipant($this->selectedConversationOrFail(), Auth::user(), $participant);
        $this->notice = "{$participant->name} fue eliminado del grupo.";
    }

    public function leaveConversation(ChatService $chat): void
    {
        $chat->leave($this->selectedConversationOrFail(), Auth::user());
        $this->conversationId = null;
        $this->contactId = null;
        $this->closeGroupInfo();
        $this->notice = 'Saliste de la conversación.';
    }

    public function hideDirectConversation(ChatService $chat): void
    {
        $conversation = $this->selectedConversationOrFail();
        $chat->hideDirectConversation($conversation, Auth::user());
        $this->conversationId = null;
        $this->contactId = null;
        $this->directInfoOpen = false;
        $this->notice = 'El chat se eliminó sólo de tu bandeja. El historial se conserva para auditoría.';
    }

    public function refreshConversation(): void
    {
        $this->markConversationAsRead();
    }

    public function render()
    {
        $user = Auth::user();
        $contacts = User::query()
            ->whereKeyNot($user->id)
            ->orderBy('name')
            ->get();

        $conversations = ChatConversation::query()
            ->with('participants')
            ->visibleTo($user)
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        $conversations->each(function (ChatConversation $conversation) use ($user): void {
            $visibleMessages = $conversation->messagesVisibleTo($user);
            $latestMessage = (clone $visibleMessages)
                ->with('user')
                ->orderByDesc('sent_at')
                ->orderByDesc('id')
                ->first();

            $conversation->setRelation('latestMessage', $latestMessage);
            $conversation->setAttribute('unread_count', (clone $visibleMessages)
                ->whereNull('read_at')
                ->where('type', '!=', 'system')
                ->where('user_id', '!=', $user->id)
                ->count());
        });

        if ($this->search !== '') {
            $term = mb_strtolower(trim($this->search));
            $conversations = $conversations->filter(function (ChatConversation $conversation) use ($term, $user): bool {
                $peer = $conversation->participants->first(fn (User $participant) => ! $participant->is($user));
                $title = $conversation->type === 'group' ? $conversation->name : $peer?->name;
                $preview = $conversation->latestMessage?->body;

                return str_contains(mb_strtolower((string) $title), $term)
                    || str_contains(mb_strtolower((string) $preview), $term);
            })->values();
        }

        $selectedConversation = $this->selectedConversation();
        $selectedContact = $this->contactId ? $contacts->firstWhere('id', $this->contactId) : null;
        if ($selectedConversation?->type === 'direct') {
            $selectedContact = $selectedConversation->participants
                ->first(fn (User $participant) => ! $participant->is($user));
        }

        $messages = $selectedConversation
            ? $selectedConversation->messagesVisibleTo($user)->with('user')->orderBy('sent_at')->orderBy('id')->get()
            : collect();

        $participants = $selectedConversation?->participants()->orderBy('name')->get() ?? collect();
        $availableParticipants = $selectedConversation?->type === 'group'
            ? $contacts->whereNotIn('id', $participants->modelKeys())->values()
            : collect();

        return view('livewire.chat-workspace', [
            'user' => $user,
            'contacts' => $contacts,
            'filteredContacts' => $contacts->filter(fn (User $contact) => $this->contactSearch === ''
                || str_contains(mb_strtolower($contact->name.' '.$contact->username.' '.$contact->email), mb_strtolower($this->contactSearch))),
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'selectedContact' => $selectedContact,
            'messages' => $messages,
            'sharedMedia' => $messages->whereIn('type', ['image', 'gif'])->values(),
            'sharedAudio' => $messages->where('type', 'audio')->values(),
            'participants' => $participants,
            'availableParticipants' => $availableParticipants,
            'isGroupOwner' => $selectedConversation?->isOwnedBy($user) ?? false,
            'emojis' => self::EMOJIS,
        ]);
    }

    private function selectedConversation(): ?ChatConversation
    {
        if (! $this->conversationId) {
            return null;
        }

        return ChatConversation::query()
            ->with(['participants', 'creator'])
            ->visibleTo((int) Auth::id())
            ->find($this->conversationId);
    }

    private function selectedConversationOrFail(): ChatConversation
    {
        return $this->selectedConversation() ?? abort(404);
    }

    private function existingDirectConversation(User $contact): ?ChatConversation
    {
        return ChatConversation::query()
            ->where('type', 'direct')
            ->has('participants', '=', 2)
            ->whereHas('participants', fn ($query) => $query->whereKey(Auth::id()))
            ->whereHas('participants', fn ($query) => $query->whereKey($contact->id))
            ->first();
    }

    private function messageOwnedByCurrentUser(int $messageId): ChatMessage
    {
        $conversation = $this->selectedConversationOrFail();

        return $conversation->messages()
            ->where('user_id', Auth::id())
            ->where('type', '!=', 'system')
            ->findOrFail($messageId);
    }

    private function markConversationAsRead(): void
    {
        $conversation = $this->selectedConversation();
        if (! $conversation) {
            return;
        }

        $conversation->messagesVisibleTo(Auth::user())
            ->where('user_id', '!=', Auth::id())
            ->where('type', '!=', 'system')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function closeTransientPanels(): void
    {
        $this->createModalOpen = false;
        $this->groupInfoOpen = false;
        $this->directInfoOpen = false;
        $this->editingGroup = false;
        $this->addingParticipants = false;
        $this->emojiPickerOpen = false;
        $this->cancelEditingMessage();
    }

    private function resetCreateForm(): void
    {
        $this->reset(
            'contactSearch',
            'groupName',
            'groupDescription',
            'groupPhoto',
            'selectedParticipants',
        );
        $this->createTab = 'contact';
        $this->resetValidation();
    }
}
