@props([
    'user',
    'size' => 'size-10',
    'pixels' => 40,
    'alt' => '',
    'seed' => null,
    'animate' => null,
    'sync' => false,
    'preferences' => null,
])

@php
    $avatarClass = trim($size . ' shrink-0 object-cover ' . $attributes->get('class', ''));
    $avatarPreferences = is_array($preferences) ? $preferences : ($user->avatar_preferences ?? []);
    $avatarColorMode = $avatarPreferences['color_mode'] ?? 'auto';
    $avatarBackground = $avatarPreferences['background'] ?? 'circle';
    $avatarExpression = $avatarPreferences['expression'] ?? 'idle';
@endphp

@if ($user->profile_photo_path && $seed === null)
    <img src="{{ $user->profile_photo_url }}" width="{{ $pixels }}"
        height="{{ $pixels }}" alt="{{ $alt }}" class="{{ $avatarClass }}"
        @if ($animate) data-blobatar-mode="{{ $animate }}" @endif
        @if ($sync) data-user-avatar-id="{{ $user->id }}" @endif
        {{ $attributes->except('class') }}>
@else
    <img data-blobatar-seed="{{ $seed ?: $user->avatar_seed ?: hash('sha256', mb_strtolower(trim($user->email))) }}"
        data-blobatar-size="{{ $pixels }}" width="{{ $pixels }}" height="{{ $pixels }}"
        data-blobatar-background="{{ $avatarBackground }}"
        data-blobatar-expression="{{ $avatarExpression }}"
        @if ($avatarColorMode === 'custom')
            data-blobatar-hue="{{ $avatarPreferences['hue'] ?? 35 }}"
            data-blobatar-tone="{{ $avatarPreferences['tone'] ?? 0.55 }}"
        @endif
        @if (!empty($avatarPreferences['background_color']))
            data-blobatar-background-color="{{ $avatarPreferences['background_color'] }}"
        @endif
        @if ($animate) data-blobatar-animate="{{ $animate }}" data-blobatar-mode="{{ $animate }}" @endif
        alt="{{ $alt }}" @if ($sync) data-user-avatar-id="{{ $user->id }}" @endif
        class="{{ $avatarClass }}" {{ $attributes->except('class') }}>
@endif
