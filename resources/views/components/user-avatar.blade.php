@props([
    'user',
    'size' => 'size-10',
    'pixels' => 40,
    'alt' => '',
    'seed' => null,
    'animate' => null,
    'sync' => false,
])

@php($avatarClass = trim($size . ' shrink-0 object-cover ' . $attributes->get('class', '')))

@if ($user->profile_photo_path && $seed === null)
    <img src="{{ Storage::disk('public')->url($user->profile_photo_path) }}" width="{{ $pixels }}"
        height="{{ $pixels }}" alt="{{ $alt }}" class="{{ $avatarClass }}"
        @if ($animate) data-blobatar-mode="{{ $animate }}" @endif
        @if ($sync) data-user-avatar-id="{{ $user->id }}" @endif
        {{ $attributes->except('class') }}>
@else
    <img data-blobatar-seed="{{ $seed ?: $user->avatar_seed ?: hash('sha256', mb_strtolower(trim($user->email))) }}"
        data-blobatar-size="{{ $pixels }}" width="{{ $pixels }}" height="{{ $pixels }}"
        @if ($animate) data-blobatar-animate="{{ $animate }}" data-blobatar-mode="{{ $animate }}" @endif
        alt="{{ $alt }}" @if ($sync) data-user-avatar-id="{{ $user->id }}" @endif
        class="{{ $avatarClass }}" {{ $attributes->except('class') }}>
@endif
