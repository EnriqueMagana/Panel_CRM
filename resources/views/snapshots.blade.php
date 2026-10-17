@extends('layouts.app')

@section('title', 'Snapshots')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-muted-foreground">Operación</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">Snapshots</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground sm:text-base">
                    Pega evidencias, asígnales un nombre y arrástralas directamente al sistema de tickets.
                </p>
            </div>
        </header>

        <div class="rounded-2xl border border-border bg-card p-4 shadow-sm sm:p-6">
            <x-snapshot-workspace page />
        </div>
    </div>
@endsection
