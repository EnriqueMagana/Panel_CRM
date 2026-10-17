<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Confirmar identidad | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-dvh place-items-center bg-muted/30 px-5 text-foreground antialiased">
    <main class="w-full max-w-sm rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8">
        <span class="grid size-11 place-items-center rounded-xl bg-primary text-primary-foreground" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" class="size-5"><path d="M12 3 4.5 6v5.5c0 4.3 3 7.8 7.5 9.5 4.5-1.7 7.5-5.2 7.5-9.5V6L12 3Z" stroke="currentColor" stroke-width="1.7" /><path d="M9 12h6M12 9v6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>
        </span>
        <h1 class="mt-5 text-2xl font-semibold">Confirma tu identidad</h1>
        <p class="mt-2 text-sm leading-6 text-muted-foreground">Esta operación modifica seguridad o accesos. Ingresa nuevamente tu contraseña para continuar.</p>

        <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-6 grid gap-4">
            @csrf
            <div class="grid gap-2">
                <label for="password" class="text-sm font-medium">Contraseña actual</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required autofocus
                    class="h-11 rounded-md border border-input bg-background px-3 outline-none focus-visible:ring-2 focus-visible:ring-ring">
                @error('password')<p class="text-sm text-destructive">{{ $message }}</p>@enderror
            </div>
            <button class="min-h-11 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Confirmar y continuar</button>
            <a href="{{ url()->previous() }}" class="text-center text-sm text-muted-foreground hover:text-foreground">Cancelar</a>
        </form>
    </main>
</body>
</html>
