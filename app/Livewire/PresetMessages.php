<?php

namespace App\Livewire;

use App\Models\BrowserExtensionConnection;
use App\Models\PresetMessage;
use App\Models\PresetMessagePreference;
use App\Models\User;
use App\Services\PresetMessageAnalyticsService;
use App\Services\PresetMessagePreferenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class PresetMessages extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $library = 'mine';

    public bool $formOpen = false;

    public ?int $editingMessageId = null;

    public string $title = '';

    public string $body = '';

    public bool $isActive = true;

    public bool $appearanceOpen = false;

    public string $messageLayout = 'grid';

    public string $messageDensity = 'comfortable';

    public string $messageAccent = 'blue';

    public string $messageBackground = 'plain';

    public string $messageTheme = 'classic';

    public ?string $messageAccentColor = null;

    public ?string $messageBackgroundColor = null;

    public ?string $messageBackgroundImagePath = null;

    public string $messageCardStyle = 'outlined';

    public string $messageRadius = 'rounded';

    public int $messageFontSize = 14;

    public string $draftTheme = 'classic';

    public string $draftLayout = 'grid';

    public string $draftDensity = 'comfortable';

    public string $draftAccent = 'blue';

    public ?string $draftAccentColor = null;

    public string $draftBackground = 'plain';

    public ?string $draftBackgroundColor = null;

    public string $draftCardStyle = 'outlined';

    public string $draftRadius = 'rounded';

    public int $draftFontSize = 14;

    public $backgroundImageUpload = null;

    public bool $removeBackgroundImage = false;

    public bool $extensionConfigured = false;

    public function mount(PresetMessagePreferenceService $preferences): void
    {
        $preference = $preferences->preferencesFor(Auth::user());
        $this->applyPreferences($preference);
        $this->syncAppearanceDraft($preference);
        $this->extensionConfigured = BrowserExtensionConnection::query()
            ->where('user_id', Auth::id())
            ->whereNotNull('connected_at')
            ->exists();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $user = Auth::user();
        if (mb_strlen(trim($this->search)) < 2 || $this->messagesQuery($user)->exists()) {
            return;
        }

        app(PresetMessageAnalyticsService::class)->recordNoResultSearch($user, $this->search, $this->library);
    }

    #[On('preset-message-used')]
    public function recordUsage(int $messageId, string $channel, PresetMessageAnalyticsService $analytics): void
    {
        abort_unless(in_array($channel, ['clipboard', 'browser'], true), 422);
        $message = PresetMessage::query()->findOrFail($messageId);
        abort_unless($message->created_by === Auth::id() || $message->is_active, 403);

        $analytics->recordUsage(Auth::user(), $message, $channel);
    }

    #[On('browser-extension-detected')]
    public function extensionDetected(string $browser = 'Chrome / Edge'): void
    {
        BrowserExtensionConnection::query()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'browser_name' => mb_substr($browser, 0, 80),
                'connected_at' => now(),
                'last_seen_at' => now(),
            ],
        );

        $this->extensionConfigured = true;
    }

    #[On('open-preset-message-form')]
    public function createMessage(): void
    {
        $this->authorize('preset_messages.create');
        $this->closeForm();
        $this->library = 'mine';
        $this->formOpen = true;
    }

    public function selectLibrary(string $library): void
    {
        abort_unless(in_array($library, ['mine', 'global', 'hidden'], true), 404);
        abort_if($library === 'global' && ! Auth::user()->can('preset_messages.view_global'), 403);

        $this->library = $library;
        $this->closeForm();
        $this->resetPage();
    }

    public function editMessage(int $messageId): void
    {
        $this->authorize('preset_messages.update');
        $message = PresetMessage::query()->findOrFail($messageId);
        abort_unless($message->created_by === Auth::id(), 403);
        $this->closeForm();
        $this->editingMessageId = $message->id;
        $this->title = $message->title;
        $this->body = $message->body;
        $this->isActive = $message->is_active;
        $this->formOpen = true;
    }

    public function saveMessage(): void
    {
        $isEditing = $this->editingMessageId !== null;
        $this->authorize($isEditing ? 'preset_messages.update' : 'preset_messages.create');

        $validated = $this->validate([
            'title' => [
                'required',
                'string',
                'max:120',
                Rule::unique('preset_messages', 'title')
                    ->where('created_by', Auth::id())
                    ->ignore($this->editingMessageId),
            ],
            'body' => ['required', 'string', 'max:5000'],
            'isActive' => ['boolean'],
        ], ['title.unique' => 'Ya existe un mensaje con este nombre.']);

        $normalizedBody = PresetMessage::normalizeVariableSyntax(trim($validated['body']));
        $attributes = [
            'title' => trim($validated['title']),
            'body' => $normalizedBody,
            'variables' => PresetMessage::extractVariables($normalizedBody),
            'is_active' => $validated['isActive'],
        ];

        if ($isEditing) {
            $message = PresetMessage::query()->findOrFail($this->editingMessageId);
            abort_unless($message->created_by === Auth::id(), 403);
            $message->update($attributes);
        } else {
            PresetMessage::query()->create([...$attributes, 'created_by' => Auth::id()]);
        }

        $this->closeForm();
        session()->flash('preset-messages-status', $isEditing ? 'Mensaje actualizado.' : 'Mensaje creado.');
    }

    public function importMessage(int $messageId): void
    {
        $this->authorize('preset_messages.create');

        $source = PresetMessage::query()
            ->whereKey($messageId)
            ->where('created_by', '!=', Auth::id())
            ->where('is_active', true)
            ->firstOrFail();

        $normalizedBody = PresetMessage::normalizeVariableSyntax($source->body);
        $imported = PresetMessage::query()->firstOrCreate(
            [
                'created_by' => Auth::id(),
                'source_message_id' => $source->id,
            ],
            [
                'title' => $this->availableImportedTitle($source->title),
                'body' => $normalizedBody,
                'variables' => PresetMessage::extractVariables($normalizedBody),
                'is_active' => true,
            ],
        );

        session()->flash(
            'preset-messages-status',
            $imported->wasRecentlyCreated
                ? 'Mensaje importado a Mis mensajes.'
                : 'Este mensaje ya está en Mis mensajes.',
        );
    }

    public function deleteMessage(int $messageId): void
    {
        $this->authorize('preset_messages.delete');
        $message = PresetMessage::query()->findOrFail($messageId);
        abort_unless($message->created_by === Auth::id(), 403);
        $message->delete();
        session()->flash('preset-messages-status', 'Mensaje eliminado.');
    }

    public function cancelForm(): void
    {
        $this->closeForm();
    }

    public function togglePin(int $messageId, PresetMessagePreferenceService $preferences): void
    {
        $preferences->togglePin(Auth::user(), PresetMessage::query()->findOrFail($messageId));
    }

    public function movePinned(int $messageId, int $targetMessageId, string $direction, PresetMessagePreferenceService $preferences): void
    {
        $preferences->movePinned(
            Auth::user(),
            PresetMessage::query()->findOrFail($messageId),
            PresetMessage::query()->findOrFail($targetMessageId),
            $direction,
        );
    }

    public function reorderPinned(array $messageIds, PresetMessagePreferenceService $preferences): void
    {
        $preferences->reorderPinned(Auth::user(), $messageIds);
    }

    public function hideMessage(int $messageId, PresetMessagePreferenceService $preferences): void
    {
        $preferences->hide(Auth::user(), PresetMessage::query()->findOrFail($messageId));
        session()->flash('preset-messages-status', 'Mensaje ocultado de tu biblioteca.');
    }

    public function restoreMessage(int $messageId, PresetMessagePreferenceService $preferences): void
    {
        $preferences->restore(Auth::user(), PresetMessage::query()->findOrFail($messageId));
        session()->flash('preset-messages-status', 'Mensaje restaurado.');
    }

    public function setMessagePreference(string $key, string $value, PresetMessagePreferenceService $preferences): void
    {
        $this->applyPreferences($preferences->updatePreference(Auth::user(), $key, $value));
    }

    public function openAppearance(PresetMessagePreferenceService $preferences): void
    {
        $this->syncAppearanceDraft($preferences->preferencesFor(Auth::user()));
        $this->reset('backgroundImageUpload', 'removeBackgroundImage');
        $this->resetValidation();
        $this->appearanceOpen = true;
    }

    public function cancelAppearance(): void
    {
        $this->appearanceOpen = false;
        $this->reset('backgroundImageUpload', 'removeBackgroundImage');
        $this->resetValidation();
    }

    public function saveAppearance(PresetMessagePreferenceService $preferences): void
    {
        $validated = $this->validate([
            'draftTheme' => ['required', Rule::in([...array_keys(PresetMessagePreference::THEMES), 'custom'])],
            'draftLayout' => ['required', Rule::in(PresetMessagePreference::LAYOUTS)],
            'draftDensity' => ['required', Rule::in(PresetMessagePreference::DENSITIES)],
            'draftAccent' => ['required', Rule::in(PresetMessagePreference::ACCENTS)],
            'draftAccentColor' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'draftBackground' => ['required', Rule::in(PresetMessagePreference::BACKGROUNDS)],
            'draftBackgroundColor' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'draftCardStyle' => ['required', Rule::in(PresetMessagePreference::CARD_STYLES)],
            'draftRadius' => ['required', Rule::in(PresetMessagePreference::RADII)],
            'draftFontSize' => ['required', 'integer', 'min:'.PresetMessagePreference::MIN_FONT_SIZE, 'max:'.PresetMessagePreference::MAX_FONT_SIZE],
            'backgroundImageUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'removeBackgroundImage' => ['boolean'],
        ], [
            'backgroundImageUpload.image' => 'Selecciona una imagen válida.',
            'backgroundImageUpload.max' => 'La imagen no puede superar 4 MB.',
        ]);

        $imagePath = $this->messageBackgroundImagePath;
        if ($this->backgroundImageUpload) {
            $imagePath = $this->backgroundImageUpload->store('preset-message-backgrounds/'.Auth::id(), 'public');
            $this->draftBackground = 'image';
            $this->draftTheme = 'custom';
            $validated['draftTheme'] = 'custom';
        } elseif ($this->removeBackgroundImage) {
            $imagePath = null;
            if ($this->draftBackground === 'image') {
                $this->draftBackground = 'plain';
            }
        }

        if ($this->draftBackground === 'image' && blank($imagePath)) {
            $this->addError('backgroundImageUpload', 'Selecciona una imagen antes de usar este fondo.');

            return;
        }

        $saved = $preferences->savePreferences(Auth::user(), [
            'theme' => $validated['draftTheme'],
            'layout' => $validated['draftLayout'],
            'density' => $validated['draftDensity'],
            'accent' => $validated['draftAccent'],
            'accent_color' => ($validated['draftAccentColor'] ?? null) ? strtolower($validated['draftAccentColor']) : null,
            'background' => $this->draftBackground,
            'background_color' => ($validated['draftBackgroundColor'] ?? null) ? strtolower($validated['draftBackgroundColor']) : null,
            'background_image_path' => $imagePath,
            'card_style' => $validated['draftCardStyle'],
            'radius' => $validated['draftRadius'],
            'font_size' => $validated['draftFontSize'],
        ]);

        if (($this->backgroundImageUpload || $this->removeBackgroundImage)
            && $this->messageBackgroundImagePath
            && $this->messageBackgroundImagePath !== $saved->background_image_path) {
            Storage::disk('public')->delete($this->messageBackgroundImagePath);
        }

        $this->applyPreferences($saved);
        $this->syncAppearanceDraft($saved);
        $this->appearanceOpen = false;
        $this->reset('backgroundImageUpload', 'removeBackgroundImage');
        session()->flash('preset-messages-status', 'Personalización guardada.');
    }

    public function render()
    {
        $user = Auth::user();
        $baseQuery = $this->messagesQuery($user)
            ->with([
                'creator:id,name,email,profile_photo_path,avatar_seed,avatar_preferences',
                'userStates' => fn ($query) => $query->whereKey($user->id),
            ]);

        $pinnedMessages = collect();
        if ($this->library === 'mine') {
            $pinnedMessages = (clone $baseQuery)
                ->whereHas('userStates', fn (Builder $query) => $query
                    ->whereKey($user->id)
                    ->whereNotNull('preset_message_user.pinned_at')
                    ->whereNull('preset_message_user.hidden_at'))
                ->orderByRaw('(select pin_order from preset_message_user where preset_message_user.preset_message_id = preset_messages.id and preset_message_user.user_id = ?) asc', [$user->id])
                ->get();

            $baseQuery->whereDoesntHave('userStates', fn (Builder $query) => $query
                ->whereKey($user->id)
                ->whereNotNull('preset_message_user.pinned_at')
                ->whereNull('preset_message_user.hidden_at'));
        }

        $messages = $baseQuery
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->paginate($this->library === 'global' ? 30 : 12);

        $importedSourceIds = $this->library === 'global'
            ? PresetMessage::query()
                ->where('created_by', $user->id)
                ->whereNotNull('source_message_id')
                ->pluck('source_message_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        return view('livewire.preset-messages', [
            'messages' => $messages,
            'pinnedMessages' => $pinnedMessages,
            'importedSourceIds' => $importedSourceIds,
            'automaticVariables' => PresetMessage::automaticVariables(),
            'calculatedVariables' => PresetMessage::calculatedVariables(),
            'presetThemes' => PresetMessagePreference::THEMES,
            'messageBackgroundImageUrl' => $this->messageBackgroundImagePath
                ? route('media.public', ['path' => $this->messageBackgroundImagePath], false)
                : null,
            'suggestedVariables' => [
                'nombre' => 'Nombre',
                'folio' => 'Folio',
                'empresa' => 'Empresa',
                'monto' => 'Monto',
                'enlace' => 'Enlace',
            ],
        ]);
    }

    private function closeForm(): void
    {
        $this->formOpen = false;
        $this->reset('editingMessageId', 'title', 'body');
        $this->isActive = true;
        $this->resetValidation();
    }

    private function messagesQuery(User $user): Builder
    {
        abort_if($this->library === 'global' && ! $user->can('preset_messages.view_global'), 403);

        $query = PresetMessage::query();

        if ($this->library === 'hidden') {
            $query
                ->where(fn (Builder $query) => $query
                    ->where('created_by', $user->id)
                    ->orWhere('is_active', true))
                ->whereHas('userStates', fn (Builder $query) => $query
                    ->whereKey($user->id)
                    ->whereNotNull('preset_message_user.hidden_at'));
        } else {
            $query
                ->when(
                    $this->library === 'mine',
                    fn (Builder $query) => $query->where('created_by', $user->id),
                    fn (Builder $query) => $query->where('created_by', '!=', $user->id)->where('is_active', true),
                )
                ->whereDoesntHave('userStates', fn (Builder $query) => $query
                    ->whereKey($user->id)
                    ->whereNotNull('preset_message_user.hidden_at'));
        }

        return $query->when(trim($this->search) !== '', function (Builder $query): void {
            $term = '%'.trim($this->search).'%';
            $query->where(fn (Builder $query) => $query->where('title', 'like', $term)->orWhere('body', 'like', $term));
        });
    }

    private function availableImportedTitle(string $sourceTitle): string
    {
        $baseTitle = trim($sourceTitle);
        $title = mb_substr($baseTitle, 0, 120);
        $copyNumber = 1;

        while (PresetMessage::query()->where('created_by', Auth::id())->where('title', $title)->exists()) {
            $suffix = $copyNumber === 1 ? ' (copia)' : " (copia {$copyNumber})";
            $title = mb_substr($baseTitle, 0, 120 - mb_strlen($suffix)).$suffix;
            $copyNumber++;
        }

        return $title;
    }

    private function applyPreferences(PresetMessagePreference $preferences): void
    {
        $this->messageTheme = $preferences->theme;
        $this->messageLayout = $preferences->layout;
        $this->messageDensity = $preferences->density;
        $this->messageAccent = $preferences->accent;
        $this->messageAccentColor = $preferences->accent_color;
        $this->messageBackground = $preferences->background;
        $this->messageBackgroundColor = $preferences->background_color;
        $this->messageBackgroundImagePath = $preferences->background_image_path;
        $this->messageCardStyle = $preferences->card_style;
        $this->messageRadius = $preferences->radius;
        $this->messageFontSize = $preferences->font_size;
    }

    private function syncAppearanceDraft(PresetMessagePreference $preferences): void
    {
        $this->draftTheme = $preferences->theme;
        $this->draftLayout = $preferences->layout;
        $this->draftDensity = $preferences->density;
        $this->draftAccent = $preferences->accent;
        $this->draftAccentColor = $preferences->accent_color;
        $this->draftBackground = $preferences->background;
        $this->draftBackgroundColor = $preferences->background_color;
        $this->draftCardStyle = $preferences->card_style;
        $this->draftRadius = $preferences->radius;
        $this->draftFontSize = $preferences->font_size;
    }
}
