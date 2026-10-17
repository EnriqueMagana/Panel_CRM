@props(['name'])

<svg viewBox="0 0 24 24" fill="none" class="size-4 shrink-0" aria-hidden="true">
    @switch($name)
        @case('home')
            <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z" stroke="currentColor" stroke-width="1.6"
                stroke-linecap="round" stroke-linejoin="round" />
        @break

        @case('users')
            <circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="1.6" />
            <path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 5.5a3.5 3.5 0 0 1 0 6.8M18 15a5 5 0 0 1 3.5 4.8" stroke="currentColor"
                stroke-width="1.6" stroke-linecap="round" />
        @break

        @case('shield')
            <path d="M12 3 4.5 6v5.5c0 4.3 3 7.8 7.5 9.5 4.5-1.7 7.5-5.2 7.5-9.5V6L12 3Z" stroke="currentColor"
                stroke-width="1.6" stroke-linejoin="round" />
            <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
        @break

        @case('user')
            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.6" />
            <path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        @break

        @case('search')
            <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.6" />
            <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        @break

        @case('key')
            <circle cx="8" cy="15" r="5" stroke="currentColor" stroke-width="1.6" />
            <path d="m11.5 11.5 8-8L22 6l-2.5 2.5L22 11l-2.5 2.5-2.5-2.5-2.5 2.5" stroke="currentColor" stroke-width="1.6"
                stroke-linejoin="round" />
        @break

        @case('lock')
            <rect x="4" y="10" width="16" height="11" rx="2" stroke="currentColor" stroke-width="1.6" />
            <path d="M8 10V7a4 4 0 1 1 8 0v3m-4 4v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        @break

        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
        @break

        @case('chat')
            <path d="M7 18.5 3.5 21V6.5A2.5 2.5 0 0 1 6 4h12a2.5 2.5 0 0 1 2.5 2.5v9A2.5 2.5 0 0 1 18 18H7Z"
                stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
            <path d="M8 9.5h8M8 13h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        @break

        @case('messages')
            <path d="M5 4.5h14A2.5 2.5 0 0 1 21.5 7v8A2.5 2.5 0 0 1 19 17.5H9L4.5 21v-4A2.5 2.5 0 0 1 2.5 15V7A2.5 2.5 0 0 1 5 4.5Z"
                stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
            <path d="M7.5 9h9M7.5 13h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        @break

        @case('extension')
            <path d="M8.5 4.5h7v4h4v7h-4v4h-7v-4h-4v-7h4v-4Z" stroke="currentColor" stroke-width="1.6"
                stroke-linejoin="round" />
        @break

        @case('ticket')
            <path d="M4.5 7.5h15A1.5 1.5 0 0 1 21 9v2a2.5 2.5 0 0 0 0 5v2a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18v-2a2.5 2.5 0 0 0 0-5V9a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
            <path d="M9 7.5v12M13 11h4M13 15h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        @break

        @case('camera')
            <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6H9l1.2-1.5h3.6L15 6h2.5A2.5 2.5 0 0 1 20 8.5v8A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
            <circle cx="12" cy="12.5" r="3" stroke="currentColor" stroke-width="1.6" />
        @break

        @case('operations')
            <rect x="3.5" y="4" width="17" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6" />
            <rect x="3.5" y="14" width="17" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6" />
            <path d="M7 7h.01M7 17h.01M10 7h7M10 17h7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        @break

        @case('settings')
            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6" />
            <path
                d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.7 2.9-.2-.1a1.7 1.7 0 0 0-1.8.2l-.2.1h-3.4l-.1-.2a1.7 1.7 0 0 0-1.5-1h-.3l-2.9-1.7.1-.2a1.7 1.7 0 0 0-.2-1.8l-.1-.2v-3.4l.2-.1a1.7 1.7 0 0 0 1-1.5v-.3l1.7-2.9.2.1a1.7 1.7 0 0 0 1.8-.2l.2-.1h3.4l.1.2a1.7 1.7 0 0 0 1.5 1h.3l2.9 1.7-.1.2a1.7 1.7 0 0 0 .2 1.8l.1.2v3.4l-.2.1"
                stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
        @break

        @case('layout')
            <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.6" />
            <path d="M9 4v16" stroke="currentColor" stroke-width="1.6" />
        @break

        @default
            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6" />
    @endswitch
</svg>
