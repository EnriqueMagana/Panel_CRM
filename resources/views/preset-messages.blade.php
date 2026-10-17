@extends('layouts.app')

@section('title', 'Mensajes predeterminados')

@section('content')
    <div data-module-type="list" class="pb-24">
        <livewire:preset-messages />
        @can('ticket_escalations.view')
            <livewire:ticket-quick-search />
        @endcan
        <x-productivity-fab />
    </div>
@endsection
