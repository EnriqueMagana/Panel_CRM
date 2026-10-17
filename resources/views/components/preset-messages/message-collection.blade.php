@props([
    'messages',
    'library',
    'pinned' => false,
    'hidden' => false,
    'title' => null,
    'extensionConfigured' => false,
    'importedSourceIds' => [],
])

@if ($messages->isNotEmpty())
    <section class="grid gap-3" @if ($pinned) aria-labelledby="pinned-preset-messages-heading" @endif>
        @if ($title)
            <div class="flex min-h-8 items-center justify-between gap-3">
                <h2 @if ($pinned) id="pinned-preset-messages-heading" @endif
                    class="text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground">{{ $title }}</h2>
                <span class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">{{ $messages->count() }}</span>
            </div>
        @endif

        <div class="preset-message-collection" @if ($pinned) data-pinned-preset-list @endif>
            @foreach ($messages as $index => $presetMessage)
                <x-preset-messages.message-card :message="$presetMessage" :library="$library" :pinned="$pinned"
                    :extension-configured="$extensionConfigured"
                    :imported="in_array($presetMessage->id, $importedSourceIds, true)"
                    :hidden="$hidden" :can-move-up="$pinned && $index > 0"
                    :can-move-down="$pinned && $index < $messages->count() - 1"
                    :move-up-id="$pinned && $index > 0 ? $messages[$index - 1]->id : null"
                    :move-down-id="$pinned && $index < $messages->count() - 1 ? $messages[$index + 1]->id : null" />
            @endforeach
        </div>
    </section>
@endif
