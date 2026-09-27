<?php

namespace App\Services;

use App\Enums\ChatHistoryVisibility;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Traits\ProcessesResponsiveImages;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatService
{
    use ProcessesResponsiveImages;

    public function __construct(private readonly TechnicalSettings $settings) {}

    public function conversationFor(User $user, int $conversationId): ChatConversation
    {
        return ChatConversation::query()
            ->visibleTo($user)
            ->findOrFail($conversationId);
    }

    public function findOrCreateDirect(User $user, User $contact): ChatConversation
    {
        abort_if($user->is($contact), 422, 'No puedes iniciar un chat contigo mismo.');

        $conversation = ChatConversation::query()
            ->where('type', 'direct')
            ->has('participants', '=', 2)
            ->whereHas('participants', fn ($query) => $query->whereKey($user->id))
            ->whereHas('participants', fn ($query) => $query->whereKey($contact->id))
            ->first();

        if ($conversation) {
            $this->restoreFor($conversation, $user);

            return $conversation;
        }

        return DB::transaction(function () use ($user, $contact): ChatConversation {
            $conversation = ChatConversation::query()->create([
                'type' => 'direct',
                'name' => $contact->name,
                'created_by' => $user->id,
            ]);

            $conversation->participants()->attach([
                $user->id => ['is_admin' => true, 'joined_at' => now()],
                $contact->id => ['is_admin' => false, 'joined_at' => now()],
            ]);

            return $conversation;
        });
    }

    public function createGroup(
        User $creator,
        string $name,
        ?string $description,
        array $participantIds,
        ?UploadedFile $image = null,
    ): ChatConversation {
        $participantIds = collect($participantIds)
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === (int) $creator->id)
            ->unique()
            ->values();

        $variants = $image
            ? $this->storeResponsiveImage($image, 'chat/group-images', square: true)
            : null;

        try {
            return DB::transaction(function () use ($creator, $name, $description, $participantIds, $variants): ChatConversation {
                $group = ChatConversation::query()->create([
                    'type' => 'group',
                    'name' => trim($name),
                    'description' => filled($description) ? trim($description) : null,
                    'image_path' => $variants['medium'] ?? null,
                    'image_variants' => $variants,
                    'created_by' => $creator->id,
                ]);

                $members = $participantIds
                    ->mapWithKeys(fn (int $id) => [$id => ['is_admin' => false, 'joined_at' => now()]])
                    ->put($creator->id, ['is_admin' => true, 'joined_at' => now()])
                    ->all();

                $group->participants()->attach($members);
                $this->recordGroupEvent(
                    $group,
                    $creator,
                    'group_created',
                    "{$creator->name} creó el grupo",
                );

                return $group;
            });
        } catch (\Throwable $exception) {
            $this->deleteResponsiveImages($variants);
            throw $exception;
        }
    }

    public function updateGroup(
        ChatConversation $group,
        User $actor,
        string $name,
        ?string $description,
        ?UploadedFile $image = null,
    ): void {
        $this->ensureOwner($group, $actor);
        abort_unless($group->type === 'group', 422);

        $updates = [
            'name' => trim($name),
            'description' => filled($description) ? trim($description) : null,
        ];

        if ($image) {
            $previousImage = $group->image_path;
            $previousVariants = $group->image_variants;
            $variants = $this->storeResponsiveImage($image, 'chat/group-images', square: true);
            $updates['image_path'] = $variants['medium'];
            $updates['image_variants'] = $variants;

            try {
                $group->update($updates);
            } catch (\Throwable $exception) {
                $this->deleteResponsiveImages($variants);
                throw $exception;
            }

            $this->deleteResponsiveImages($previousVariants, $previousImage);

            return;
        }

        $group->update($updates);
    }

    public function addParticipants(ChatConversation $group, User $actor, array $participantIds): void
    {
        $this->ensureOwner($group, $actor);
        abort_unless($group->type === 'group', 422);

        $participantIds = collect($participantIds)
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === (int) $actor->id)
            ->unique()
            ->diff($group->participants()->pluck('users.id'))
            ->values();

        $newParticipants = User::query()
            ->whereIn('id', $participantIds)
            ->orderBy('name')
            ->get();

        if ($newParticipants->isEmpty()) {
            return;
        }

        $hidePreviousHistory = $this->settings->chatHistoryVisibility() === ChatHistoryVisibility::SinceAdded;

        DB::transaction(function () use ($group, $actor, $newParticipants, $hidePreviousHistory): void {
            $historyCutoff = $hidePreviousHistory
                ? (int) ($group->messages()->lockForUpdate()->orderByDesc('id')->value('id') ?? 0)
                : null;

            $participants = $newParticipants
                ->mapWithKeys(fn (User $participant) => [$participant->id => [
                    'is_admin' => false,
                    'joined_at' => now(),
                    'history_visible_after_message_id' => $historyCutoff,
                ]])
                ->all();

            $group->participants()->syncWithoutDetaching($participants);

            foreach ($newParticipants as $participant) {
                $this->recordGroupEvent(
                    $group,
                    $actor,
                    'participant_added',
                    "{$actor->name} agregó a {$participant->name}",
                    $participant,
                );
            }
        });
    }

    public function removeParticipant(ChatConversation $group, User $actor, User $participant): void
    {
        $this->ensureOwner($group, $actor);
        abort_unless($group->type === 'group', 422);
        abort_if($participant->is($actor), 422, 'Usa la opción Salir del grupo.');
        abort_unless($group->participants()->whereKey($participant->id)->exists(), 404);

        DB::transaction(function () use ($group, $actor, $participant): void {
            $this->recordGroupEvent(
                $group,
                $actor,
                'participant_removed',
                "{$actor->name} eliminó a {$participant->name}",
                $participant,
            );
            $group->participants()->detach($participant->id);
        });
    }

    public function leave(ChatConversation $conversation, User $actor): void
    {
        abort_unless($conversation->participants()->whereKey($actor->id)->exists(), 403);

        DB::transaction(function () use ($conversation, $actor): void {
            if ($conversation->type === 'group') {
                $this->recordGroupEvent(
                    $conversation,
                    $actor,
                    'participant_left',
                    "{$actor->name} salió del grupo",
                    $actor,
                );
            }

            if ($conversation->type === 'group' && $conversation->isOwnedBy($actor)) {
                $nextOwner = $conversation->participants()
                    ->where('users.id', '!=', $actor->id)
                    ->orderByPivot('joined_at')
                    ->first();

                if ($nextOwner) {
                    $conversation->update(['created_by' => $nextOwner->id]);
                    $conversation->participants()->updateExistingPivot($nextOwner->id, ['is_admin' => true]);
                }
            }

            $conversation->participants()->detach($actor->id);

            if (! $conversation->participants()->exists()) {
                $this->deleteConversationFiles($conversation);
                $conversation->delete();
            }
        });
    }

    public function hideDirectConversation(ChatConversation $conversation, User $actor): void
    {
        abort_unless($conversation->type === 'direct', 422);
        abort_unless($conversation->participants()->whereKey($actor->id)->exists(), 403);

        $conversation->participants()->updateExistingPivot($actor->id, [
            'hidden_at' => now(),
        ]);
    }

    public function restoreFor(ChatConversation $conversation, User $actor): void
    {
        abort_unless($conversation->participants()->whereKey($actor->id)->exists(), 403);

        $conversation->participants()->updateExistingPivot($actor->id, [
            'hidden_at' => null,
        ]);
    }

    public function sendMessage(
        ChatConversation $conversation,
        User $sender,
        ?string $body,
        ?UploadedFile $attachment = null,
    ): ChatMessage {
        abort_unless($conversation->participants()->whereKey($sender->id)->exists(), 403);

        $mime = $attachment?->getMimeType();
        $type = 'text';
        $variants = null;
        $path = null;

        if ($attachment) {
            $type = $mime === 'image/gif'
                ? 'gif'
                : (Str::startsWith((string) $mime, 'image/') ? 'image' : 'audio');

            if (in_array($type, ['image', 'gif'], true)) {
                $variants = $this->storeResponsiveImage(
                    $attachment,
                    'chat/attachments',
                    preserveAnimatedGif: $type === 'gif',
                );
                $path = $variants[$type === 'gif' ? 'original' : 'medium'];
            } else {
                $path = $attachment->store('chat/attachments', 'public');
            }
        }

        try {
            return DB::transaction(function () use ($conversation, $sender, $body, $attachment, $path, $variants, $mime, $type): ChatMessage {
                DB::table('chat_conversation_user')
                    ->where('chat_conversation_id', $conversation->id)
                    ->update(['hidden_at' => null, 'updated_at' => now()]);

                $message = ChatMessage::query()->create([
                    'chat_conversation_id' => $conversation->id,
                    'user_id' => $sender->id,
                    'direction' => 'outgoing',
                    'type' => $type,
                    'body' => trim((string) $body),
                    'attachment_path' => $path,
                    'attachment_variants' => $variants,
                    'attachment_name' => $attachment?->getClientOriginalName(),
                    'attachment_mime' => $mime,
                    'attachment_size' => $attachment?->getSize(),
                    'sent_at' => now(),
                ]);

                $conversation->update(['last_message_at' => $message->sent_at]);

                return $message;
            });
        } catch (\Throwable $exception) {
            if ($variants) {
                $this->deleteResponsiveImages($variants, $path);
            } elseif ($path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }
    }

    public function deleteMessage(ChatMessage $message, User $actor): void
    {
        abort_unless((int) $message->user_id === (int) $actor->id, 403);
        abort_if($message->type === 'system', 403, 'Los eventos del sistema forman parte del historial de auditoría.');

        if ($message->attachment_path) {
            $this->deleteResponsiveImages($message->attachment_variants, $message->attachment_path);
        }

        $conversation = $message->conversation;
        $message->delete();
        $conversation->update(['last_message_at' => $conversation->messages()->max('sent_at')]);
    }

    private function ensureOwner(ChatConversation $conversation, User $actor): void
    {
        abort_unless($conversation->isOwnedBy($actor), 403, 'Sólo el creador puede administrar el grupo.');
    }

    private function recordGroupEvent(
        ChatConversation $group,
        User $actor,
        string $event,
        string $body,
        ?User $subject = null,
    ): ChatMessage {
        $message = ChatMessage::query()->create([
            'chat_conversation_id' => $group->id,
            'user_id' => $actor->id,
            'direction' => 'incoming',
            'type' => 'system',
            'event_data' => [
                'event' => $event,
                'actor_id' => $actor->id,
                'actor_name' => $actor->name,
                'subject_id' => $subject?->id,
                'subject_name' => $subject?->name,
            ],
            'body' => $body,
            'sent_at' => now(),
        ]);

        $group->update(['last_message_at' => $message->sent_at]);

        return $message;
    }

    private function deleteConversationFiles(ChatConversation $conversation): void
    {
        $paths = $conversation->messages()
            ->get(['attachment_path', 'attachment_variants'])
            ->flatMap(fn (ChatMessage $message) => collect($message->attachment_variants ?? [])->push($message->attachment_path))
            ->merge($conversation->image_variants ?? [])
            ->push($conversation->image_path)
            ->filter()
            ->unique()
            ->all();

        Storage::disk('public')->delete($paths);
    }
}
