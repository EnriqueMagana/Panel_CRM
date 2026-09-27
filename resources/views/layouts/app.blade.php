<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title', 'Panel') | {{ config('app.name', 'Snippetdesk') }}</title>
    <script>
        (() => {
            try {
                const defaults = {
                    theme: 'system',
                    layout: 'default',
                    sidebar_variant: 'sidebar',
                    direction: 'ltr',
                };
                const userPreferences = {
                    theme: @json(optional(auth()->user())->theme ?? 'system'),
                    layout: @json(optional(auth()->user())->layout ?? 'default'),
                    sidebar_variant: @json(optional(auth()->user())->sidebar_variant ?? 'sidebar'),
                    direction: @json(optional(auth()->user())->direction ?? 'ltr'),
                };
                const theme = localStorage.getItem('snippetdesk-theme') || userPreferences.theme || defaults.theme;
                const layout = localStorage.getItem('snippetdesk-layout') || userPreferences.layout || defaults.layout;
                const sidebarVariant = localStorage.getItem('snippetdesk-sidebar-variant') || userPreferences
                    .sidebar_variant || defaults.sidebar_variant;
                const direction = localStorage.getItem('snippetdesk-direction') || userPreferences.direction || defaults
                    .direction;
                const dark = theme === 'dark' || (theme === 'system' && window.matchMedia(
                    '(prefers-color-scheme: dark)').matches);
                const root = document.documentElement;
                root.classList.toggle('dark', dark);
                root.style.colorScheme = dark ? 'dark' : 'light';
                root.classList.toggle('sidebar-collapsed', layout === 'compact');
                root.dataset.layout = layout;
                root.dataset.sidebarVariant = sidebarVariant;
                root.setAttribute('dir', direction);
                document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#020817' : '#ffffff');
            } catch {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-background text-foreground antialiased">
    <div id="global-confirm-modal" aria-hidden="true"
        class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="pointer-events-auto w-full max-w-md rounded-xl border border-border bg-background p-5 text-foreground shadow-2xl"
            role="dialog" aria-modal="true" aria-labelledby="global-confirm-title">
            <h2 id="global-confirm-title" class="text-xl font-semibold">Confirmación requerida</h2>
            <p data-confirm-message class="mt-3 text-sm text-muted-foreground">¿Estás seguro?</p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-confirm-cancel
                    class="min-h-11 rounded-md border border-input px-4 text-sm font-medium hover:bg-accent">Cancelar</button>
                <button type="button" data-confirm-accept
                    class="min-h-11 rounded-md bg-destructive px-4 text-sm font-medium text-destructive-foreground hover:bg-destructive/90">Eliminar</button>
            </div>
        </div>
    </div>
    @php
        $layoutUser = auth()->user();
        $layoutRoles = $layoutUser->getRoleNames();
    @endphp

    <div id="app-shell" class="min-h-dvh lg:grid lg:grid-cols-[256px_minmax(0,1fr)]">
        <aside id="app-sidebar"
            class="sticky top-0 hidden h-dvh border-r border-sidebar-border bg-sidebar text-sidebar-foreground lg:flex lg:flex-col">
            <a href="{{ route('home') }}" wire:navigate.hover
                class="flex h-16 items-center gap-3 border-b border-sidebar-border px-6 font-semibold">
                <span class="grid size-9 shrink-0 place-items-center rounded-md bg-primary text-primary-foreground"
                    aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5">
                        <path
                            d="M5 5.75A.75.75 0 0 1 5.75 5h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5A.75.75 0 0 1 5 10.25v-4.5Zm8 0a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5a.75.75 0 0 1-.75-.75v-4.5Zm-8 8a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5Zm8 0a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5Z"
                            fill="currentColor" />
                    </svg>
                </span>
                <span class="sidebar-brand-text truncate">{{ config('app.name', 'Snippetdesk') }}</span>
            </a>

            <div class="min-h-0 flex-1 overflow-y-auto">
                @include('layouts.partials.navigation')
            </div>

            <div class="border-t border-sidebar-border p-4">
                <div class="flex items-center gap-3 px-2">
                    <div
                        class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-lg bg-accent text-sm font-semibold text-accent-foreground">
                        <x-user-avatar :user="$layoutUser" size="size-full" :pixels="40" alt=""
                            animate="hover" :sync="true" class="rounded-lg" />
                    </div>
                    <div class="sidebar-user-copy min-w-0">
                        <p class="truncate text-sm font-semibold">{{ $layoutUser->name }}</p>
                        <p class="truncate text-xs text-muted-foreground">{{ $layoutUser->email }}</p>
                    </div>
                </div>
                <p class="sidebar-user-copy truncate px-2 pt-2 text-xs text-muted-foreground">
                    {{ $layoutRoles->isNotEmpty() ? $layoutRoles->join(', ') : 'Sin rol asignado' }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" title="Cerrar sesión"
                        class="sidebar-link flex min-h-11 w-full items-center gap-3 rounded-md px-3 text-sm text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                        <svg viewBox="0 0 24 24" fill="none" class="size-4 shrink-0" aria-hidden="true">
                            <path d="M10 17l5-5-5-5m5 5H3m9-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" stroke="currentColor"
                                stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span class="sidebar-label">Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0">
            <header
                class="sticky top-0 z-30 flex h-16 items-center gap-2 border-b border-border bg-background px-3 sm:gap-3 sm:px-6">
                <details class="mobile-sidebar-root relative lg:hidden">
                    <summary aria-label="Abrir navegación"
                        class="grid size-11 list-none cursor-pointer place-items-center rounded-md border border-input text-foreground hover:bg-accent">
                        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                            <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.7"
                                stroke-linecap="round" />
                        </svg>
                    </summary>
                    <button type="button" data-close-mobile-sidebar
                        class="mobile-sidebar-backdrop fixed inset-0 z-40 bg-black/40"
                        aria-label="Cerrar navegación"></button>
                    <aside
                        class="mobile-sidebar-panel fixed inset-y-0 left-0 z-50 flex w-[min(19rem,calc(100vw-3rem))] flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground shadow-xl">
                        <div class="flex h-16 items-center justify-between border-b border-sidebar-border px-4">
                            <a href="{{ route('home') }}" wire:navigate.hover class="flex min-h-11 items-center gap-3 font-semibold">
                                <span
                                    class="grid size-9 place-items-center rounded-md bg-primary text-primary-foreground"
                                    aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" class="size-5">
                                        <path
                                            d="M5 5.75A.75.75 0 0 1 5.75 5h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5A.75.75 0 0 1 5 10.25v-4.5Zm8 0a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5a.75.75 0 0 1-.75-.75v-4.5Zm-8 8a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5Zm8 0a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                                {{ config('app.name', 'Snippetdesk') }}
                            </a>
                            <button type="button" data-close-mobile-sidebar aria-label="Cerrar navegación"
                                class="grid size-11 place-items-center rounded-md text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                                <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                                    <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7"
                                        stroke-linecap="round" />
                                </svg>
                            </button>
                        </div>
                        <div class="min-h-0 flex-1 overflow-y-auto">
                            @include('layouts.partials.navigation')
                        </div>
                        <div class="flex items-center gap-3 border-t border-sidebar-border p-4">
                            <div
                                class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-lg bg-accent text-sm font-semibold text-accent-foreground">
                                <x-user-avatar :user="$layoutUser" size="size-full" :pixels="40" alt=""
                                    animate="hover" :sync="true" class="rounded-lg" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ $layoutUser->name }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ $layoutUser->email }}</p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ $layoutRoles->isNotEmpty() ? $layoutRoles->join(', ') : 'Sin rol asignado' }}
                                </p>
                            </div>
                        </div>
                    </aside>
                </details>

                <button type="button" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="true"
                    aria-label="Contraer barra lateral" title="Contraer barra lateral"
                    class="hidden size-10 shrink-0 place-items-center rounded-md border border-input text-foreground hover:bg-accent lg:grid">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor"
                            stroke-width="1.6" />
                        <path d="M9 4v16" stroke="currentColor" stroke-width="1.6" />
                    </svg>
                </button>
                <span class="hidden h-6 w-px bg-border lg:block" aria-hidden="true"></span>

                <button type="button" data-search-trigger aria-keyshortcuts="Control+K Meta+K"
                    class="group relative flex h-10 min-w-0 flex-1 items-center gap-2 rounded-md border border-input bg-muted/25 px-3 text-left text-sm text-muted-foreground shadow-none transition-colors hover:bg-accent sm:max-w-sm">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4 shrink-0" aria-hidden="true">
                        <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.6" />
                        <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                    <span class="truncate">Buscar</span>
                    <kbd
                        class="ml-auto hidden rounded border border-border bg-background px-1.5 py-0.5 font-mono text-[10px] text-muted-foreground sm:inline-flex">Ctrl
                        K</kbd>
                </button>

                <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
                    <details class="relative">
                        <summary aria-label="Cambiar tema" title="Cambiar tema"
                            class="grid size-10 list-none cursor-pointer place-items-center rounded-full text-foreground hover:bg-accent">
                            <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                                <circle cx="12" cy="12" r="3.5" stroke="currentColor"
                                    stroke-width="1.6" />
                                <path
                                    d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"
                                    stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                            </svg>
                        </summary>
                        <div
                            class="absolute right-0 top-full z-40 mt-2 grid min-w-36 gap-1 rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-lg">
                            <button type="button" data-theme-value="light" aria-pressed="false"
                                class="flex min-h-10 items-center justify-between rounded-sm px-3 text-left text-sm hover:bg-accent">Claro
                                <svg data-theme-check="light" viewBox="0 0 24 24" fill="none"
                                    class="hidden size-4">
                                    <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.7"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg></button>
                            <button type="button" data-theme-value="dark" aria-pressed="false"
                                class="flex min-h-10 items-center justify-between rounded-sm px-3 text-left text-sm hover:bg-accent">Oscuro
                                <svg data-theme-check="dark" viewBox="0 0 24 24" fill="none"
                                    class="hidden size-4">
                                    <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.7"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg></button>
                            <button type="button" data-theme-value="system" aria-pressed="false"
                                class="flex min-h-10 items-center justify-between rounded-sm px-3 text-left text-sm hover:bg-accent">Sistema
                                <svg data-theme-check="system" viewBox="0 0 24 24" fill="none"
                                    class="hidden size-4">
                                    <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.7"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg></button>
                        </div>
                    </details>
                    <button type="button" data-open-theme-settings aria-label="Abrir ajustes del tema"
                        title="Ajustes del tema"
                        class="grid size-10 place-items-center rounded-full text-foreground hover:bg-accent">
                        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                            <path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z" stroke="currentColor"
                                stroke-width="1.6" />
                            <path
                                d="m19.4 15 .1.1 1.1 1.9-1.7 2.9-2.1-.1a7.8 7.8 0 0 1-1.7 1l-.8 1.9h-3.4l-.8-1.9a7.8 7.8 0 0 1-1.7-1l-2.1.1-1.7-2.9L5.7 15a7.8 7.8 0 0 1 0-2l-1.1-1.9 1.7-2.9 2.1.1a7.8 7.8 0 0 1 1.7-1l.8-1.9h3.4l.8 1.9a7.8 7.8 0 0 1 1.7 1l2.1-.1 1.7 2.9-1.1 1.9a7.8 7.8 0 0 1-.1 2Z"
                                stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <details class="relative">
                        <summary aria-label="Menú de usuario" title="Menú de usuario"
                            class="grid size-10 list-none cursor-pointer place-items-center overflow-hidden rounded-full bg-accent text-xs font-semibold text-accent-foreground hover:ring-2 hover:ring-ring/40">
                            <x-user-avatar :user="$layoutUser" size="size-full" :pixels="40" alt=""
                                animate="hover" :sync="true" class="rounded-full" />
                        </summary>
                        <div
                            class="absolute right-0 top-full z-40 mt-2 w-64 rounded-md border border-border bg-popover p-2 text-popover-foreground shadow-lg">
                            <div class="border-b border-border px-3 py-2">
                                <p class="truncate text-sm font-semibold">{{ $layoutUser->name }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ $layoutUser->email }}</p>
                                <p class="truncate pt-1 text-xs text-muted-foreground">
                                    {{ $layoutRoles->isNotEmpty() ? $layoutRoles->join(', ') : 'Sin rol asignado' }}
                                </p>
                            </div>
                            <a href="{{ route('profile') }}" wire:navigate.hover data-profile-link
                                class="flex min-h-10 items-center justify-between rounded-sm px-3 text-sm hover:bg-accent"><span>Perfil</span><kbd
                                    class="text-[10px] text-muted-foreground">Ctrl+Shift+P</kbd></a>
                            <a href="{{ route('profile') }}#profile-details" wire:navigate
                                class="flex min-h-10 items-center rounded-sm px-3 text-sm hover:bg-accent">Perfil</a>
                            <button type="button" data-open-theme-settings
                                class="flex min-h-10 w-full items-center justify-between rounded-sm px-3 text-left text-sm hover:bg-accent"><span>Configuración</span><kbd
                                    class="text-[10px] text-muted-foreground">Ctrl+Shift+S</kbd></button>
                            <form method="POST" action="{{ route('logout') }}" data-logout-form
                                class="border-t border-border pt-1">
                                @csrf
                                <button type="submit"
                                    class="flex min-h-10 w-full items-center justify-between rounded-sm px-3 text-left text-sm text-destructive hover:bg-accent"><span>Cerrar
                                        sesión</span><kbd
                                        class="text-[10px] text-destructive">Ctrl+Shift+Q</kbd></button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1600px] px-4 py-8 sm:px-8 sm:py-10">
                <div data-module-stage class="relative min-h-[18rem]">
                    <div data-module-content class="min-h-[18rem]" aria-live="polite" wire:transition.navigate>
                        @yield('content')
                    </div>
                    <x-module-skeleton type="dashboard" />
                </div>
            </main>
        </div>

        <x-theme-settings />
        <livewire:user-preferences />

        <dialog data-search-dialog aria-label="Buscar en el panel"
            class="m-auto w-[calc(100%-2rem)] max-w-xl rounded-lg border border-border bg-popover p-0 text-popover-foreground shadow-2xl backdrop:bg-black/40">
            <div class="border-b border-border p-4">
                <div class="flex items-center gap-3">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5 shrink-0 text-muted-foreground"
                        aria-hidden="true">
                        <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.6" />
                        <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                    </svg>
                    <input type="search" data-search-input aria-label="Buscar páginas y secciones"
                        placeholder="Buscar páginas y secciones..." autocomplete="off"
                        class="h-10 min-w-0 flex-1 bg-transparent text-base outline-none placeholder:text-muted-foreground">
                    <button type="button" data-close-search aria-label="Cerrar búsqueda"
                        class="grid size-9 place-items-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"><svg
                            viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                            <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7"
                                stroke-linecap="round" />
                        </svg></button>
                </div>
            </div>
            <div class="max-h-[min(24rem,60vh)] overflow-y-auto p-2" role="listbox"
                aria-label="Resultados de búsqueda">
                <p class="px-3 py-2 text-xs font-medium text-muted-foreground">Navegación</p>
                @can('dashboard.view')
                    <a role="option" wire:navigate.hover data-search-item data-search-value="inicio home dashboard"
                        href="{{ route('home') }}"
                        class="flex min-h-11 items-center justify-between rounded-md px-3 text-sm hover:bg-accent"><span>Inicio</span><span
                            class="text-xs text-muted-foreground">General</span></a>
                @endcan
                @can('users.view')
                    <a role="option" wire:navigate.hover data-search-item data-search-value="usuarios users cuentas"
                        href="{{ route('users') }}"
                        class="flex min-h-11 items-center justify-between rounded-md px-3 text-sm hover:bg-accent"><span>Usuarios</span><span
                            class="text-xs text-muted-foreground">Administración</span></a>
                @endcan
                @can('roles.view')
                    <a role="option" wire:navigate.hover data-search-item data-search-value="roles permisos permisos por modulo"
                        href="{{ route('roles') }}"
                        class="flex min-h-11 items-center justify-between rounded-md px-3 text-sm hover:bg-accent"><span>Roles</span><span
                            class="text-xs text-muted-foreground">Administración</span></a>
                @endcan
                @can('navigation.view')
                    <a role="option" wire:navigate.hover data-search-item data-search-value="navegación menú sidebar"
                        href="{{ route('navigation') }}"
                        class="flex min-h-11 items-center justify-between rounded-md px-3 text-sm hover:bg-accent"><span>Navegación</span><span
                            class="text-xs text-muted-foreground">Administración</span></a>
                @endcan
                @can('technical_center.view')
                    <a role="option" wire:navigate.hover data-search-item
                        data-search-value="centro técnico configuraciones sistema chats historial privacidad"
                        href="{{ route('technical-center') }}"
                        class="flex min-h-11 items-center justify-between rounded-md px-3 text-sm hover:bg-accent"><span>Centro técnico</span><span
                            class="text-xs text-muted-foreground">Administración</span></a>
                @endcan
                <a role="option" wire:navigate.hover data-search-item data-search-value="chats mensajes inbox conversación"
                    href="{{ route('chats') }}"
                    class="flex min-h-11 items-center justify-between rounded-md px-3 text-sm hover:bg-accent"><span>Chats</span><span
                        class="text-xs text-muted-foreground">General</span></a>
                <a role="option" wire:navigate data-search-item data-search-value="perfil nombre imagen foto cuenta"
                    href="{{ route('profile') }}#profile-details"
                    class="flex min-h-11 items-center justify-between rounded-md px-3 text-sm hover:bg-accent"><span>Perfil</span><span
                        class="text-xs text-muted-foreground">Cuenta</span></a>
                <a role="option" wire:navigate data-search-item data-search-value="contraseña password clave seguridad"
                    href="{{ route('profile') }}#password-heading"
                    class="flex min-h-11 items-center justify-between rounded-md px-3 text-sm hover:bg-accent"><span>Contraseña</span><span
                        class="text-xs text-muted-foreground">Cuenta</span></a>
                <a role="option" wire:navigate data-search-item
                    data-search-value="dos pasos 2fa autenticador verificación seguridad"
                    href="{{ route('profile') }}#two-factor-heading"
                    class="flex min-h-11 items-center justify-between rounded-md px-3 text-sm hover:bg-accent"><span>Verificación
                        en dos pasos</span><span class="text-xs text-muted-foreground">Cuenta</span></a>
                <p data-search-empty class="hidden px-3 py-8 text-center text-sm text-muted-foreground">No se
                    encontraron resultados.</p>
            </div>
            <div
                class="flex items-center justify-between border-t border-border px-4 py-3 text-xs text-muted-foreground">
                <span>Busca una página o sección de la cuenta</span><kbd
                    class="rounded border border-border bg-muted px-1.5 py-0.5 font-mono">Esc</kbd>
            </div>
        </dialog>
    </div>

    @livewireScripts
</body>

</html>
