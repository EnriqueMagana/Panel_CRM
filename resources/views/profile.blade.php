@extends('layouts.app')

@section('title', 'Perfil')

@section('content')
    <div class="space-y-8" data-module-type="profile">
        <header class="border-b border-border pb-7">
            <p class="text-sm font-medium text-muted-foreground">Cuenta</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Perfil</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">Actualiza tus datos personales, revisa tu rol y
                administra la seguridad de tu cuenta.</p>
        </header>

        <livewire:profile-security />
    </div>
@endsection
