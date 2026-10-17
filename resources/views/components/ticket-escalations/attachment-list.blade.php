@props(['attachments'])

@if ($attachments->isNotEmpty())
    <div {{ $attributes->class('grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4') }}>
        @foreach ($attachments as $attachment)
            <a href="{{ $attachment->url }}" target="_blank" rel="noreferrer"
                class="group/file flex min-w-0 items-center gap-2 rounded-lg border border-border bg-background p-2 text-sm transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                aria-label="Abrir {{ $attachment->original_name }}">
                @if ($attachment->isImage())
                    <img src="{{ $attachment->url }}" alt="{{ $attachment->original_name }}" loading="lazy"
                        width="48" height="48" class="size-12 shrink-0 rounded-md object-cover ring-1 ring-border">
                @else
                    <span class="grid size-12 shrink-0 place-items-center rounded-md bg-muted text-muted-foreground">
                        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
                            <path d="M7 3h7l4 4v14H7V3Zm7 0v5h4M10 13h5M10 17h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                @endif
                <span class="min-w-0">
                    <span class="block truncate text-xs font-medium">{{ $attachment->original_name }}</span>
                    <span class="mt-0.5 block text-xs text-muted-foreground">{{ number_format($attachment->size / 1024, 0) }} KB</span>
                </span>
            </a>
        @endforeach
    </div>
@endif
