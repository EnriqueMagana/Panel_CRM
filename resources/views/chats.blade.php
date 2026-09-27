@extends('layouts.app')

@section('title', 'Mensajes')

@section('content')
    <div data-module-type="chat">
        <livewire:chat-workspace />
    </div>
@endsection
