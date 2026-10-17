<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone_number',
        'password',
        'must_change_password',
        'password_changed_at',
        'avatar_seed',
        'avatar_preferences',
        'profile_photo_variants',
        'status',
        'theme',
        'sidebar_variant',
        'layout',
        'direction',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'profile_photo_variants' => 'array',
            'avatar_preferences' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (blank($user->username)) {
                $baseUsername = Str::slug(Str::before($user->email, '@'), '.');
                $baseUsername = $baseUsername ?: 'user';
                $username = $baseUsername;
                $suffix = 1;

                while (static::where('username', $username)->exists()) {
                    $username = $baseUsername.'.'.$suffix++;
                }

                $user->username = $username;
            }

            $user->status ??= 'active';

            if (blank($user->profile_photo_path) && blank($user->avatar_seed)) {
                $user->avatar_seed = (string) Str::uuid();
            }
        });
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profilePhotoUrl('small');
    }

    public function presetMessagePreference(): HasOne
    {
        return $this->hasOne(PresetMessagePreference::class);
    }

    public function browserExtensionConnection(): HasOne
    {
        return $this->hasOne(BrowserExtensionConnection::class);
    }

    public function ticketEscalations(): HasMany
    {
        return $this->hasMany(TicketEscalation::class, 'created_by');
    }

    public function profilePhotoUrl(string $size = 'small'): ?string
    {
        $path = $this->profile_photo_variants[$size]
            ?? $this->profile_photo_variants['medium']
            ?? $this->profile_photo_path;

        if (blank($path)) {
            return null;
        }

        return route('media.public', [
            'path' => $path,
            'v' => $this->updated_at?->getTimestamp(),
        ], false);
    }
}
