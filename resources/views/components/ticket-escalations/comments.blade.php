@props(['escalation', 'commentUploads' => []])

<section class="mt-6 border-t border-border pt-6" aria-labelledby="ticket-comments-{{ $escalation->id }}">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 id="ticket-comments-{{ $escalation->id }}" class="font-semibold">Seguimiento colaborativo</h3>
            <p class="mt-1 text-xs text-muted-foreground">Los comentarios forman parte del historial y no pueden editarse.</p>
        </div>
        <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
            {{ $escalation->comments->count() }} {{ $escalation->comments->count() === 1 ? 'comentario' : 'comentarios' }}
        </span>
    </div>

    @if ($escalation->comments->isNotEmpty())
        <ol class="mt-5 grid gap-4">
            @foreach ($escalation->comments as $comment)
                <li class="relative flex gap-3" wire:key="ticket-comment-{{ $comment->id }}">
                    <x-user-avatar wire:ignore :user="$comment->user" size="size-9" :pixels="36" :alt="$comment->user->name"
                        :sync="true"
                        class="rounded-full ring-1 ring-border" />
                    <div class="min-w-0 flex-1 rounded-lg border border-border bg-background p-3 sm:p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold">{{ $comment->user->name }}</p>
                            <time datetime="{{ $comment->created_at->toIso8601String() }}" class="text-xs text-muted-foreground">
                                {{ $comment->created_at->format('d/m/Y H:i') }}
                            </time>
                        </div>
                        @if (filled($comment->body))
                            <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-6">{{ $comment->body }}</p>
                        @endif
                        <x-ticket-escalations.attachment-list :attachments="$comment->attachments" class="mt-3" />
                    </div>
                </li>
            @endforeach
        </ol>
    @else
        <p class="mt-4 rounded-lg border border-dashed border-border bg-background px-4 py-5 text-center text-sm text-muted-foreground">
            Aún no hay comentarios. Agrega el primer seguimiento del caso.
        </p>
    @endif

    <form wire:submit="addComment({{ $escalation->id }})" class="mt-5 grid gap-3 rounded-lg border border-border bg-background p-3 sm:p-4">
        <div class="flex items-center gap-2">
            <x-user-avatar wire:ignore :user="auth()->user()" size="size-8" :pixels="32" :alt="auth()->user()->name"
                :sync="true" class="rounded-full ring-1 ring-border" />
            <label for="ticket-comment-body-{{ $escalation->id }}" class="text-sm font-medium">Añadir seguimiento</label>
        </div>
        <textarea id="ticket-comment-body-{{ $escalation->id }}" data-ticket-rich-textarea
            data-attachment-input="ticket-comment-attachments-{{ $escalation->id }}"
            wire:model="commentBodies.{{ $escalation->id }}" maxlength="5000" rows="4"
            placeholder="Escribe una actualización, validación o respuesta del área responsable..."
            class="resize-y rounded-md border border-input bg-background px-3 py-2.5 text-base leading-6 outline-none focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm"></textarea>
        <p class="text-xs text-muted-foreground">Puedes pegar capturas en el textarea o adjuntar hasta 5 archivos.</p>
        @error('commentBodies.' . $escalation->id) <p role="alert" class="text-sm text-destructive">{{ $message }}</p> @enderror

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <label for="ticket-comment-attachments-{{ $escalation->id }}"
                data-ticket-dropzone data-attachment-input="ticket-comment-attachments-{{ $escalation->id }}"
                class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-md border border-input px-3 text-sm font-medium hover:bg-accent focus-within:ring-2 focus-within:ring-ring">
                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                    <path d="m8.5 12.5 5.7-5.7a3 3 0 0 1 4.2 4.2l-7.1 7.1a5 5 0 0 1-7.1-7.1l7.1-7.1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                </svg>
                Adjuntar archivos
                <input id="ticket-comment-attachments-{{ $escalation->id }}" type="file"
                    wire:model="commentUploads.{{ $escalation->id }}" multiple
                    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv" class="sr-only">
            </label>
            <button type="submit" wire:loading.attr="disabled" wire:target="addComment({{ $escalation->id }})"
                class="inline-flex min-h-11 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60">
                <span wire:loading.remove wire:target="addComment({{ $escalation->id }})">Publicar comentario</span>
                <span wire:loading wire:target="addComment({{ $escalation->id }})">Publicando...</span>
            </button>
        </div>

        <div wire:loading wire:target="commentUploads.{{ $escalation->id }}" class="text-sm text-muted-foreground">Procesando archivos...</div>
        @if ($uploads = ($commentUploads[$escalation->id] ?? []))
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($uploads as $index => $upload)
                    <div wire:key="comment-upload-{{ $escalation->id }}-{{ $index }}" class="flex min-w-0 items-center gap-2 rounded-md border border-border bg-muted/20 p-2">
                        @if (str_starts_with((string) $upload->getMimeType(), 'image/'))
                            <img src="{{ $upload->temporaryUrl() }}" alt="Vista previa de {{ $upload->getClientOriginalName() }}" class="size-10 rounded object-cover">
                        @else
                            <span class="grid size-10 shrink-0 place-items-center rounded bg-muted text-muted-foreground"><svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true"><path d="M7 3h7l4 4v14H7V3Zm7 0v5h4" stroke="currentColor" stroke-width="1.6" /></svg></span>
                        @endif
                        <span class="min-w-0 flex-1 truncate text-xs">{{ $upload->getClientOriginalName() }}</span>
                        <button type="button" wire:click="removeCommentUpload({{ $escalation->id }}, {{ $index }})" aria-label="Quitar {{ $upload->getClientOriginalName() }}" class="grid size-11 shrink-0 place-items-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground">&times;</button>
                    </div>
                @endforeach
            </div>
        @endif
        @error('commentUploads.' . $escalation->id) <p role="alert" class="text-sm text-destructive">{{ $message }}</p> @enderror
        @error('commentUploads.' . $escalation->id . '.*') <p role="alert" class="text-sm text-destructive">{{ $message }}</p> @enderror
    </form>
</section>
