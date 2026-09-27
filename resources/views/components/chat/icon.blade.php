@props(['name', 'class' => 'size-5'])

<svg viewBox="0 0 24 24" fill="none" class="{{ $class }}" aria-hidden="true">
    @switch($name)
        @case('plus')
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        @break

        @case('search')
            <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7" />
            <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
        @break

        @case('arrow-left')
            <path d="M15 18 9 12l6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                stroke-linejoin="round" />
        @break

        @case('more')
            <circle cx="12" cy="5" r="1.6" fill="currentColor" />
            <circle cx="12" cy="12" r="1.6" fill="currentColor" />
            <circle cx="12" cy="19" r="1.6" fill="currentColor" />
        @break

        @case('check-check')
            <path d="m3.5 12 3.5 3.5L14.5 8M10 14l2 2 8.5-9" stroke="currentColor" stroke-width="1.65"
                stroke-linecap="round" stroke-linejoin="round" />
        @break

        @case('paperclip')
            <path d="m9.5 12.5 5.9-5.9a3.2 3.2 0 0 1 4.5 4.5l-8.1 8.1a5 5 0 0 1-7.1-7.1l7.6-7.6"
                stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
        @break

        @case('smile')
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" />
            <path d="M8.5 14.2c.9 1.3 2 2 3.5 2s2.6-.7 3.5-2M9 9.5h.01M15 9.5h.01" stroke="currentColor"
                stroke-width="1.8" stroke-linecap="round" />
        @break

        @case('send')
            <path d="M3.5 11.5 20.5 4l-4 16-4.8-6.2-8.2-2.3Z" stroke="currentColor" stroke-width="1.6"
                stroke-linejoin="round" />
            <path d="m11.7 13.8 3.9-4.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        @break

        @case('users')
            <path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20M9.5 10.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM17 4.2a3.4 3.4 0 0 1 0 6.6M21 20v-1.5a4 4 0 0 0-3-3.8"
                stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        @break

        @case('message')
            <path d="M6.5 18.5 3 21v-5.2A8.5 8.5 0 1 1 6.5 18.5Z" stroke="currentColor" stroke-width="1.6"
                stroke-linejoin="round" />
            <path d="M8 10.5h8M8 14h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        @break

        @case('x')
            <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        @break

        @case('trash')
            <path d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5" stroke="currentColor"
                stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
        @break

        @case('pencil')
            <path d="m4 20 4.2-1 10.6-10.6a2 2 0 0 0-2.8-2.8L5.4 16.2 4 20Z" stroke="currentColor"
                stroke-width="1.6" stroke-linejoin="round" />
        @break

        @case('check')
            <path d="m5 12 4.2 4.2L19 6.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                stroke-linejoin="round" />
        @break

        @case('camera')
            <path d="M4 8.5h3l1.4-2h7.2l1.4 2h3v10H4v-10Z" stroke="currentColor" stroke-width="1.6"
                stroke-linejoin="round" />
            <circle cx="12" cy="13.5" r="3.2" stroke="currentColor" stroke-width="1.6" />
        @break

        @case('image')
            <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.6" />
            <circle cx="8.5" cy="9" r="1.6" stroke="currentColor" stroke-width="1.5" />
            <path d="m4.5 17 4.2-4.2 3.2 3.1 2.2-2.2 5.4 5.3" stroke="currentColor" stroke-width="1.6"
                stroke-linecap="round" stroke-linejoin="round" />
        @break

        @case('mic')
            <rect x="9" y="3" width="6" height="12" rx="3" stroke="currentColor" stroke-width="1.6" />
            <path d="M6.5 11.5a5.5 5.5 0 0 0 11 0M12 17v4M9 21h6" stroke="currentColor" stroke-width="1.6"
                stroke-linecap="round" />
        @break

        @case('user-plus')
            <circle cx="9" cy="8" r="4" stroke="currentColor" stroke-width="1.6" />
            <path d="M2.5 20a6.5 6.5 0 0 1 13 0M19 8v6M16 11h6" stroke="currentColor" stroke-width="1.6"
                stroke-linecap="round" />
        @break

        @case('user-minus')
            <circle cx="9" cy="8" r="4" stroke="currentColor" stroke-width="1.6" />
            <path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 11h6" stroke="currentColor" stroke-width="1.6"
                stroke-linecap="round" />
        @break

        @case('log-out')
            <path d="M10 5H5v14h5M14 8l4 4-4 4M18 12H9" stroke="currentColor" stroke-width="1.6"
                stroke-linecap="round" stroke-linejoin="round" />
        @break
    @endswitch
</svg>
