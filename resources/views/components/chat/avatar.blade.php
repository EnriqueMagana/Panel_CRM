@props([
    'user' => null,
    'label' => '',
    'size' => 'md',
    'group' => false,
    'imageUrl' => null,
    'seed' => null,
    'loading' => 'lazy',
    'animate' => 'hover',
])

@php
    $sizes = [
        'sm' => ['class' => 'chat-avatar--sm', 'pixels' => 36],
        'md' => ['class' => 'chat-avatar--md', 'pixels' => 44],
        'lg' => ['class' => 'chat-avatar--lg', 'pixels' => 48],
    ];
    $avatarSize = $sizes[$size] ?? $sizes['md'];
    $initials = collect(preg_split('/\s+/', trim($label)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $resolvedSeed = $seed
        ?: $user?->avatar_seed
        ?: hash('sha256', $user?->email ?: 'chat-avatar:' . $label);
    $resolvedImageUrl = $imageUrl ?: $user?->profile_photo_url;
@endphp

<span role="img" aria-label="{{ $label }}"
    {{ $attributes->class(['chat-avatar', $avatarSize['class'], 'chat-avatar--group' => $group]) }}>
    <span class="chat-avatar__fallback" aria-hidden="true">
        @if ($group)
            <x-chat.icon name="users" class="size-5" />
        @else
            {{ $initials ?: '?' }}
        @endif
    </span>

    <img data-blobatar-seed="{{ $resolvedSeed }}" data-blobatar-size="{{ $avatarSize['pixels'] }}"
        @if ($animate) data-blobatar-animate="{{ $animate }}" data-blobatar-mode="{{ $animate }}" @endif
        width="{{ $avatarSize['pixels'] }}" height="{{ $avatarSize['pixels'] }}" alt=""
        data-chat-avatar-generated class="chat-avatar__generated size-full rounded-full object-cover">

    @if ($resolvedImageUrl)
        <img src="{{ $resolvedImageUrl }}" width="{{ $avatarSize['pixels'] }}" height="{{ $avatarSize['pixels'] }}"
            alt="" loading="{{ $loading }}" decoding="async" data-chat-avatar-photo
            class="chat-avatar__photo size-full object-cover">
    @endif
</span>
