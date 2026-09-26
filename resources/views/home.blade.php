@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
    @php($user = auth()->user())
    <div class="space-y-8" data-module-type="dashboard">
        <header class="flex flex-col gap-4 border-b border-border pb-7 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-muted-foreground">Espacio de trabajo</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Hola, {{ $user->name }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">Tu cuenta de Snippetdesk está lista.
                    Administra tus preferencias y mantén tu acceso protegido.</p>
            </div>
            <a href="{{ route('profile') }}"
                class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 sm:self-auto">
                Configurar perfil
                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                    <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </a>
        </header>

        <section class="border-l-2 border-primary pl-5" aria-labelledby="workspace-heading">
            <h2 id="workspace-heading" class="text-base font-semibold">Tu cuenta, en un solo lugar</h2>
            <p class="mt-1 text-sm leading-6 text-muted-foreground">Desde Perfil y seguridad puedes actualizar tus datos,
                cambiar tu contraseña y proteger el acceso con verificación en dos pasos.</p>
            <a href="{{ route('profile') }}"
                class="mt-3 inline-flex min-h-11 items-center text-sm font-medium underline-offset-4 hover:underline">
                Abrir Perfil y seguridad
            </a>
        </section>
    </div>
@endsection
