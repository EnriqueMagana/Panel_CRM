@extends('layouts.app')

@section('title', 'Escalado de tickets')

@section('content')
    <div data-module-type="list">
        <livewire:ticket-escalations />
        <livewire:ticket-escalation-form />
    </div>
@endsection
