<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <main class="relative grid min-h-dvh bg-background text-foreground lg:grid-cols-2">
        <section class="flex min-h-dvh flex-col px-6 py-8 sm:px-10 lg:px-12 lg:py-10">
            <a href="{{ url('/') }}" class="mx-auto flex items-center gap-2.5 text-lg font-medium lg:mx-0"
                aria-label="{{ config('app.name') }} - inicio">
                <span class="grid size-8 place-items-center rounded-md bg-primary text-primary-foreground"
                    aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M5 5.75A.75.75 0 0 1 5.75 5h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5A.75.75 0 0 1 5 10.25v-4.5Zm8 0a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5a.75.75 0 0 1-.75-.75v-4.5Zm-8 8a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5A.75.75 0 0 1 5 18.25v-4.5Zm8 0a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5a.75.75 0 0 1-.75-.75v-4.5Z"
                            fill="currentColor" />
                    </svg>
                </span>
                <span>{{ config('app.name') }}</span>
            </a>

            <div class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-12">
                <div class="mb-7 space-y-2">
                    <h1 class="text-2xl font-semibold tracking-tight">Iniciar sesión</h1>
                    <p class="text-sm leading-6 text-muted-foreground">Ingresa tu correo y contraseña para acceder a tu
                        cuenta.</p>
                </div>

                @if (session('status'))
                    <p role="status"
                        class="mb-5 rounded-md border border-border bg-muted px-4 py-3 text-sm text-foreground">
                        {{ session('status') }}</p>
                @endif

                @if ($errors->any())
                    <div role="alert"
                        class="mb-5 rounded-md border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                        <p class="font-medium">No se pudo iniciar sesión.</p>
                        <p class="mt-1">Revisa tu correo y contraseña e inténtalo de nuevo.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="grid gap-5">
                    @csrf
                    <div class="grid gap-2">
                        <label for="email" class="text-sm font-medium">Correo electrónico</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                            placeholder="nombre@ejemplo.com" autocomplete="username" required autofocus
                            aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                            class="h-11 w-full rounded-md border border-input bg-transparent px-3 text-base shadow-sm outline-none transition-[box-shadow,border-color] placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                        @error('email')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-4">
                            <label for="password" class="text-sm font-medium">Contraseña</label>
                            @if (Route::has('password.request') && view()->exists('auth.forgot-password'))
                                <a href="{{ route('password.request') }}"
                                    class="text-sm font-medium text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">¿Olvidaste
                                    tu contraseña?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <input id="password" name="password" type="password" autocomplete="current-password"
                                required aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                class="h-11 w-full rounded-md border border-input bg-transparent px-3 pr-12 text-base shadow-sm outline-none transition-[box-shadow,border-color] placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                            <button type="button" data-password-toggle aria-controls="password"
                                aria-label="Mostrar contraseña" aria-pressed="false"
                                class="absolute inset-y-0 right-0 grid w-11 place-items-center rounded-r-md text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                                <svg data-password-icon="show" viewBox="0 0 24 24" fill="none" class="size-4"
                                    aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6-9.75-6-9.75-6Z"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <circle cx="12" cy="12" r="2.5" stroke="currentColor"
                                        stroke-width="1.5" />
                                </svg>
                                <svg data-password-icon="hide" viewBox="0 0 24 24" fill="none" class="hidden size-4"
                                    aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="m3 3 18 18M10.6 6.2A10.8 10.8 0 0 1 12 6c6.25 0 9.75 6 9.75 6a17 17 0 0 1-3.2 3.7M6.2 6.8C3.65 8.5 2.25 12 2.25 12s3.5 6 9.75 6c1 0 1.95-.15 2.8-.4M9.9 9.9a3 3 0 0 0 4.2 4.2"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <label for="remember" class="flex min-h-6 items-center gap-2.5 text-sm text-muted-foreground">
                        <input id="remember" name="remember" type="checkbox" value="1"
                            @checked(old('remember'))
                            class="size-4 rounded border-input accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                        Recordarme
                    </label>

                    <button type="submit"
                        class="mt-1 inline-flex h-11 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background">
                        <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M12 15V3m0 0L7.5 7.5M12 3l4.5 4.5M5 13v5.5A2.5 2.5 0 0 0 7.5 21h9a2.5 2.5 0 0 0 2.5-2.5V13"
                                stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        Entrar
                    </button>
                </form>

                @if (Route::has('register') && view()->exists('auth.register'))
                    <p class="mt-6 text-center text-sm text-muted-foreground">
                        ¿No tienes una cuenta?
                        <a href="{{ route('register') }}"
                            class="font-medium text-foreground underline underline-offset-4 hover:text-primary">Crear
                            cuenta</a>
                    </p>
                @endif
            </div>

            <p class="text-center text-xs text-muted-foreground lg:text-left">&copy; {{ date('Y') }}
                {{ config('app.name') }}. Todos los derechos reservados.</p>
        </section>

        <aside class="relative hidden overflow-hidden bg-muted lg:block"
            aria-label="Vista previa del panel de administración">
            <img src="{{ asset('images/shadcn-admin-light.png') }}" alt="Vista del panel de administración"
                width="1024" height="1151"
                class="absolute left-20 top-[15%] h-full w-full select-none object-cover object-left-top dark:hidden">
            <img src="{{ asset('images/shadcn-admin-dark.png') }}" alt="Vista oscura del panel de administración"
                width="1024" height="1138"
                class="absolute left-20 top-[15%] hidden h-full w-full select-none object-cover object-left-top dark:block">
        </aside>
    </main>
</body>

</html>
