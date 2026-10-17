<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Acceso') | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        (() => {
            const preference = localStorage.getItem('snippetdesk-theme') || 'system';
            const dark = preference === 'dark' || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-muted/30 font-sans text-foreground antialiased">
    <div class="relative flex min-h-dvh flex-col">
        <header class="flex h-16 items-center justify-between px-5 sm:px-8">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5 rounded-lg font-semibold tracking-tight outline-none ring-ring transition-opacity hover:opacity-80 focus-visible:ring-2 focus-visible:ring-offset-2" aria-label="{{ config('app.name') }} - inicio">
                <span class="grid size-8 place-items-center rounded-lg bg-primary text-primary-foreground shadow-sm" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4.5" xmlns="http://www.w3.org/2000/svg"><path d="M8 8.75 4.75 12 8 15.25M16 8.75 19.25 12 16 15.25M13.5 6.5l-3 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </span>
                <span>{{ config('app.name') }}</span>
            </a>
            <div class="inline-flex items-center rounded-lg border border-border bg-background p-1 shadow-sm" aria-label="Apariencia" role="group">
                <button type="button" data-theme-value="light" aria-pressed="false" class="grid size-8 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground aria-pressed:bg-muted aria-pressed:text-foreground" aria-label="Usar modo claro" title="Modo claro">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4.5" aria-hidden="true"><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="1.7" /><path d="M12 2.5v2M12 19.5v2M4.5 12h-2M21.5 12h-2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M18.7 5.3l-1.4 1.4M6.7 17.3l-1.4 1.4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
                </button>
                <button type="button" data-theme-value="dark" aria-pressed="false" class="grid size-8 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground aria-pressed:bg-muted aria-pressed:text-foreground" aria-label="Usar modo oscuro" title="Modo oscuro">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4.5" aria-hidden="true"><path d="M20.2 15.1A8 8 0 0 1 8.9 3.8 8.1 8.1 0 1 0 20.2 15Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </button>
            </div>
        </header>
        <main class="flex flex-1 items-center justify-center px-5 py-10 sm:px-8"><div class="w-full max-w-sm">@yield('content')</div></main>
        <footer class="px-5 pb-6 text-center text-xs text-muted-foreground sm:px-8">&copy; {{ now()->year }} {{ config('app.name') }}</footer>
    </div>
</body>
</html>
