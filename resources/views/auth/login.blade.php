{{-- Legacy login layout retained temporarily for an easy rollback.
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
--}}

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
                    <svg viewBox="0 0 24 24" fill="none" class="size-4.5"><path d="M8 8.75 4.75 12 8 15.25M16 8.75 19.25 12 16 15.25M13.5 6.5l-3 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
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
        <main class="flex flex-1 items-center justify-center px-5 py-10 sm:px-8">
            <div class="w-full max-w-sm">
    <section class="rounded-2xl border border-border bg-card p-6 text-card-foreground shadow-sm sm:p-8"
        aria-labelledby="login-title">
        <div class="mb-7 text-center">
            <h1 id="login-title" class="text-2xl font-semibold tracking-tight">Bienvenido de nuevo</h1>
            <p class="mt-2 text-sm leading-6 text-muted-foreground">Ingresa tus datos para acceder a tu cuenta.</p>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-lg border border-emerald-500/25 bg-emerald-500/10 px-3.5 py-3 text-sm text-emerald-700 dark:text-emerald-300"
                role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-5 flex gap-3 rounded-lg border border-destructive/25 bg-destructive/10 px-3.5 py-3 text-sm text-destructive"
                role="alert">
                <svg viewBox="0 0 24 24" fill="none" class="mt-0.5 size-4 shrink-0" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" />
                    <path d="M12 7.5v5M12 16.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                <p>Revisa tus datos e inténtalo de nuevo.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf

            <div class="space-y-2">
                <label for="email" class="text-sm font-medium">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                    inputmode="email" required autofocus
                    class="flex h-10 w-full rounded-lg border border-input bg-background px-3 text-sm shadow-xs outline-none transition-[border-color,box-shadow] placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/20"
                    placeholder="nombre@empresa.com" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    @error('email') aria-describedby="email-error" @enderror>
                @error('email')
                    <p id="email-error" class="text-xs text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between gap-4">
                    <label for="password" class="text-sm font-medium">Contraseña</label>
                    @if (Route::has('password.request') && view()->exists('auth.forgot-password'))
                        <a href="{{ route('password.request') }}"
                            class="text-xs font-medium text-primary underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">¿La olvidaste?</a>
                    @endif
                </div>
                <div class="relative">
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        class="flex h-10 w-full rounded-lg border border-input bg-background px-3 pr-10 text-sm shadow-xs outline-none transition-[border-color,box-shadow] placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/20"
                        placeholder="Tu contraseña" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                        @error('password') aria-describedby="password-error" @enderror>
                    <button type="button" data-password-toggle aria-controls="password" aria-pressed="false"
                        class="absolute inset-y-0 right-0 grid w-10 place-items-center rounded-r-lg text-muted-foreground outline-none transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring"
                        aria-label="Mostrar contraseña">
                        <svg data-password-icon="show" viewBox="0 0 24 24" fill="none" class="size-4.5" aria-hidden="true">
                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7" />
                            <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7" />
                        </svg>
                        <svg data-password-icon="hide" viewBox="0 0 24 24" fill="none" class="hidden size-4.5" aria-hidden="true">
                            <path d="m4 4 16 16M10.6 6.1A10.7 10.7 0 0 1 12 6c6 0 9.5 6 9.5 6a16 16 0 0 1-2.2 2.9M6.7 6.7C4 8.5 2.5 12 2.5 12s3.5 6 9.5 6c1.1 0 2.1-.2 3-.5M9.9 9.9a3 3 0 0 0 4.2 4.2"
                                stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p id="password-error" class="text-xs text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <label for="remember" class="flex w-fit cursor-pointer items-center gap-2.5 text-sm text-muted-foreground">
                <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))
                    class="size-4 rounded border-input accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                <span>Recordar sesión</span>
            </label>

            <button type="submit"
                class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-[background-color,transform] hover:bg-primary/90 active:translate-y-px focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                Iniciar sesión
            </button>
        </form>

        @if (Route::has('register') && view()->exists('auth.register'))
            <p class="mt-6 text-center text-sm text-muted-foreground">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}"
                    class="font-medium text-foreground underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">Crear cuenta</a>
            </p>
        @endif
    </section>
            </div>
        </main>
        <footer class="px-5 pb-6 text-center text-xs text-muted-foreground sm:px-8">&copy; {{ now()->year }} {{ config('app.name') }}</footer>
    </div>
</body>
</html>
