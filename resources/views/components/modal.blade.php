@props(['id', 'title', 'description' => null, 'closeAction' => 'cancelForm', 'size' => 'lg'])

@php
    $sizeClass = match ($size) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-3xl',
        '2xl' => 'max-w-4xl',
        default => 'max-w-xl',
    };
@endphp

<dialog data-livewire-modal aria-labelledby="{{ $id }}"
    @if ($description) aria-describedby="{{ $id }}-description" @endif
    class="fixed inset-0 m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] {{ $sizeClass }} overflow-y-auto rounded-md border border-border bg-background p-0 text-foreground shadow-2xl backdrop:bg-black/50">
    <header
        class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-border bg-background px-5 py-4 sm:px-6">
        <div>
            <h2 id="{{ $id }}" class="text-lg font-semibold">{{ $title }}</h2>
            @if ($description)
                <p id="{{ $id }}-description" class="mt-1 text-sm text-muted-foreground">{{ $description }}</p>
            @endif
        </div>
        <button type="button" data-modal-close-action wire:click="{{ $closeAction }}" aria-label="Cerrar diálogo"
            class="grid size-11 shrink-0 place-items-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground">
            <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            </svg>
        </button>
    </header>
    <div class="p-5 sm:p-6">{{ $slot }}</div>
</dialog>
