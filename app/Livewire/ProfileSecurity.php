<?php

namespace App\Livewire;

use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use App\Traits\ProcessesResponsiveImages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileSecurity extends Component
{
    use ProcessesResponsiveImages, WithFileUploads;

    public string $name = '';

    public $photo = null;

    public string $profileMode = 'avatar';

    public array $avatarChoices = [];

    public string $selectedAvatarSeed = '';

    public string $avatarColorMode = 'auto';

    public int $avatarHue = 35;

    public float $avatarTone = 0.55;

    public string $avatarBackground = 'circle';

    public string $avatarBackgroundColor = '#f6c76b';

    public string $avatarExpression = 'idle';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public string $twoFactorPassword = '';

    public string $code = '';

    public array $recoveryCodes = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->profileMode = $user->profile_photo_path ? 'photo' : 'avatar';
        $this->loadAvatarPreferences($user);
        $this->makeAvatarChoices($user->avatar_seed);
    }

    public function updatedPhoto(mixed $value = null): void
    {
        if ($this->photo) {
            $this->profileMode = 'photo';
        }
    }

    public function selectProfilePhoto(): void
    {
        $this->profileMode = 'photo';
    }

    public function selectProfileAvatar(): void
    {
        $this->profileMode = 'avatar';
        $this->reset('photo');
        $this->resetValidation('photo');
    }

    public function generateAvatarChoices(): void
    {
        $this->selectProfileAvatar();
        $this->makeAvatarChoices();
    }

    public function updateProfile(): void
    {
        $user = Auth::user();
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'profileMode' => ['required', Rule::in(['photo', 'avatar'])],
            'photo' => [
                $this->profileMode === 'photo' && blank($user->profile_photo_path) ? 'required' : 'nullable',
                'image',
                'max:2048',
            ],
            'selectedAvatarSeed' => [
                $this->profileMode === 'avatar' ? 'required' : 'nullable',
                'uuid',
            ],
            'avatarColorMode' => ['required', Rule::in(['auto', 'custom'])],
            'avatarHue' => ['required', 'integer', 'between:0,359'],
            'avatarTone' => ['required', 'numeric', 'between:0.15,0.85'],
            'avatarBackground' => ['required', Rule::in(['circle', 'square', 'squircle', 'transparent'])],
            'avatarBackgroundColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'avatarExpression' => ['required', Rule::in($this->avatarExpressions())],
        ]);

        $updates = [
            'name' => $validated['name'],
            'avatar_preferences' => $this->avatarPreferences(),
        ];
        $previousPhoto = $user->profile_photo_path;
        $previousVariants = $user->profile_photo_variants;

        if ($validated['profileMode'] === 'avatar') {
            $updates['profile_photo_path'] = null;
            $updates['profile_photo_variants'] = null;
            $updates['avatar_seed'] = $validated['selectedAvatarSeed'];
        } elseif ($this->photo) {
            $variants = $this->storeResponsiveImage($this->photo, 'profile-photos', square: true);
            $photoPath = $variants['medium'];

            if (! $photoPath) {
                $this->addError('photo', 'No se pudo guardar la imagen. Inténtalo de nuevo.');

                return;
            }

            $updates['profile_photo_path'] = $photoPath;
            $updates['profile_photo_variants'] = $variants;
        }

        $user->forceFill($updates)->save();

        if ($previousPhoto && $previousPhoto !== $user->profile_photo_path) {
            $this->deleteResponsiveImages($previousVariants, $previousPhoto);
        }

        $this->reset('photo');
        $this->profileMode = $user->profile_photo_path ? 'photo' : 'avatar';
        $this->makeAvatarChoices($user->avatar_seed);
        session()->flash('profile-status', 'El perfil se actualizó correctamente.');
        $this->dispatchProfileAvatarUpdated($user);
    }

    public function removeProfilePhoto(): void
    {
        $user = Auth::user();

        if ($user->profile_photo_path) {
            $this->deleteResponsiveImages($user->profile_photo_variants, $user->profile_photo_path);
            $user->forceFill([
                'profile_photo_path' => null,
                'profile_photo_variants' => null,
                'avatar_seed' => $user->avatar_seed ?: (string) Str::uuid(),
            ])->save();
        }

        $this->reset('photo');
        $this->profileMode = 'avatar';
        $this->makeAvatarChoices($user->avatar_seed);
        session()->flash('profile-status', 'Se eliminó la imagen de perfil.');
        $this->dispatchProfileAvatarUpdated($user);
    }

    public function updatePassword(UpdateUserPassword $updater): void
    {
        $validated = $this->validate([
            'currentPassword' => ['required', 'current_password:web'],
            'newPassword' => ['required', 'string', Password::default(), 'same:newPasswordConfirmation'],
            'newPasswordConfirmation' => ['required', 'string'],
        ]);

        $updater->update(Auth::user(), [
            'current_password' => $validated['currentPassword'],
            'password' => $validated['newPassword'],
            'password_confirmation' => $validated['newPasswordConfirmation'],
        ]);

        $this->reset('currentPassword', 'newPassword', 'newPasswordConfirmation');
        session()->flash('profile-status', 'La contraseña se actualizó correctamente.');
    }

    public function enableTwoFactor(EnableTwoFactorAuthentication $enable): void
    {
        $this->validate([
            'twoFactorPassword' => ['required', 'current_password:web'],
        ]);

        $user = Auth::user();
        $enable($user);
        $this->recoveryCodes = $user->refresh()->recoveryCodes();
        $this->reset('twoFactorPassword');
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirm): void
    {
        $this->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $confirm(Auth::user(), $this->code);
        $this->reset('code');
        session()->flash('profile-status', 'La verificación en dos pasos quedó activada.');
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate): void
    {
        $this->validate([
            'twoFactorPassword' => ['required', 'current_password:web'],
        ]);

        $user = Auth::user();
        abort_unless($user->hasEnabledTwoFactorAuthentication(), 403);

        $generate($user);
        $this->recoveryCodes = $user->refresh()->recoveryCodes();
        $this->reset('twoFactorPassword');
        session()->flash('profile-status', 'Se generaron nuevos códigos de recuperación.');
    }

    public function disableTwoFactor(DisableTwoFactorAuthentication $disable): void
    {
        $this->validate([
            'twoFactorPassword' => ['required', 'current_password:web'],
        ]);

        $user = Auth::user();
        abort_unless($user->hasEnabledTwoFactorAuthentication(), 403);

        $disable($user);
        $this->recoveryCodes = [];
        $this->reset('twoFactorPassword');
        session()->flash('profile-status', 'La verificación en dos pasos quedó desactivada.');
    }

    public function render()
    {
        return view('livewire.profile-security', [
            'roles' => Auth::user()->getRoleNames(),
        ]);
    }

    private function makeAvatarChoices(?string $selectedSeed = null): void
    {
        $this->avatarChoices = collect(range(1, 8))
            ->map(fn () => (string) Str::uuid())
            ->all();

        if ($selectedSeed) {
            $this->avatarChoices[0] = $selectedSeed;
        }

        $this->selectedAvatarSeed = $this->avatarChoices[0];
    }

    public function avatarPreferences(): array
    {
        return [
            'color_mode' => $this->avatarColorMode,
            'hue' => $this->avatarHue,
            'tone' => round($this->avatarTone, 2),
            'background' => $this->avatarBackground,
            'background_color' => strtolower($this->avatarBackgroundColor),
            'expression' => $this->avatarExpression,
        ];
    }

    private function loadAvatarPreferences(User $user): void
    {
        $preferences = $user->avatar_preferences ?? [];
        $this->avatarColorMode = $preferences['color_mode'] ?? 'auto';
        $this->avatarHue = (int) ($preferences['hue'] ?? 35);
        $this->avatarTone = (float) ($preferences['tone'] ?? 0.55);
        $this->avatarBackground = $preferences['background'] ?? 'circle';
        $this->avatarBackgroundColor = $preferences['background_color'] ?? '#f6c76b';
        $this->avatarExpression = $preferences['expression'] ?? 'idle';
    }

    private function avatarExpressions(): array
    {
        return ['idle', 'happy', 'wink', 'surprised', 'thinking', 'sleepy', 'love', 'shy', 'smug', 'unsure'];
    }

    private function dispatchProfileAvatarUpdated(User $user): void
    {
        $this->dispatch(
            'profile-avatar-updated',
            userId: $user->id,
            profilePhotoUrl: $user->profile_photo_url,
            avatarSeed: $user->avatar_seed ?: hash('sha256', mb_strtolower(trim($user->email))),
            avatarPreferences: $user->avatar_preferences ?? [],
        );
    }
}
