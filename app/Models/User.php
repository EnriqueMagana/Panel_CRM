<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'avatar_seed',
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
}
