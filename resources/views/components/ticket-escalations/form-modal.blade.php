@props(['editing' => false, 'statuses' => [], 'uploads' => []])

<x-modal id="ticket-escalation-form-heading" :title="$editing ? 'Editar escalado' : 'Registrar escalado'"
    description="Documenta el ticket para que toda la mesa de ayuda pueda darle seguimiento." close-action="cancelForm"
    size="xl" :dismissible="false">
    <form wire:submit="saveEscalation" class="grid gap-5">
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="grid gap-2">
                <label for="ticket-folio" class="text-sm font-medium">Folio</label>
                <input id="ticket-folio" wire:model="folio" required maxlength="80" autocomplete="off"
                    placeholder="Ej. INC-10482"
                    class="h-11 rounded-md border border-input bg-background px-3 text-base uppercase outline-none focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                @error('folio') <p role="alert" class="text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-2">
                <label for="ticket-date" class="text-sm font-medium">Fecha de escalado</label>
                <input id="ticket-date" type="date" wire:model="escalatedAt" required
                    class="h-11 rounded-md border border-input bg-background px-3 text-base outline-none focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                @error('escalatedAt') <p role="alert" class="text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-2">
            <label for="ticket-title" class="text-sm font-medium">Título</label>
            <input id="ticket-title" wire:model="title" required maxlength="160" autocomplete="off"
                placeholder="Resume el problema o solicitud"
                class="h-11 rounded-md border border-input bg-background px-3 text-base outline-none focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
            @error('title') <p role="alert" class="text-sm text-destructive">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="grid gap-2">
                <label for="ticket-area" class="text-sm font-medium">Área a la que se escaló</label>
                <input id="ticket-area" wire:model="escalatedArea" required maxlength="120" autocomplete="organization"
                    placeholder="Ej. Infraestructura o Seguridad"
                    class="h-11 rounded-md border border-input bg-background px-3 text-base outline-none focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                @error('escalatedArea') <p role="alert" class="text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-2">
                <label for="ticket-status" class="text-sm font-medium">Estatus</label>
                <select id="ticket-status" wire:model="status"
                    class="h-11 rounded-md border border-input bg-background px-3 text-base outline-none focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <p role="alert" class="text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-2">
            <div class="flex items-center justify-between gap-3">
                <label for="ticket-description" class="text-sm font-medium">Descripción</label>
                <span class="text-xs text-muted-foreground">Máximo 10,000 caracteres</span>
            </div>
            <textarea id="ticket-description" data-ticket-rich-textarea data-attachment-input="ticket-form-attachments"
                wire:model="description" required maxlength="10000" rows="7"
                placeholder="Describe el incidente, las validaciones realizadas y el motivo del escalado."
                class="resize-y rounded-md border border-input bg-background px-3 py-2.5 text-base leading-6 outline-none focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm"></textarea>
            <p class="text-xs leading-5 text-muted-foreground">Puedes pegar una captura directamente en este campo; se agregará como adjunto sin insertar código en el texto.</p>
            @error('description') <p role="alert" class="text-sm text-destructive">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-3">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-medium">Archivos y capturas</span>
                <span class="text-xs text-muted-foreground">Hasta 5 archivos, 10 MB cada uno</span>
            </div>
            <label for="ticket-form-attachments"
                data-ticket-dropzone data-attachment-input="ticket-form-attachments"
                class="flex min-h-24 cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-input bg-muted/20 px-4 py-4 text-center transition-colors hover:bg-muted/40 focus-within:ring-2 focus-within:ring-ring">
                <svg viewBox="0 0 24 24" fill="none" class="size-5 text-muted-foreground" aria-hidden="true">
                    <path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span class="mt-2 text-sm font-medium">Selecciona o arrastra archivos</span>
                <span class="mt-1 text-xs text-muted-foreground">Imágenes, PDF, Office, TXT o CSV</span>
                <input id="ticket-form-attachments" type="file" wire:model="ticketUploads" multiple
                    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv" class="sr-only">
            </label>
            <div wire:loading wire:target="ticketUploads" class="text-sm text-muted-foreground">Procesando archivos...</div>
            @if ($uploads)
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($uploads as $index => $upload)
                        <div wire:key="ticket-upload-{{ $index }}" class="flex min-w-0 items-center gap-3 rounded-md border border-border bg-muted/20 p-2">
                            @if (str_starts_with((string) $upload->getMimeType(), 'image/'))
                                <img src="{{ $upload->temporaryUrl() }}" alt="Vista previa de {{ $upload->getClientOriginalName() }}" class="size-11 rounded object-cover">
                            @else
                                <span class="grid size-11 shrink-0 place-items-center rounded bg-muted text-muted-foreground">
                                    <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true"><path d="M7 3h7l4 4v14H7V3Zm7 0v5h4" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" /></svg>
                                </span>
                            @endif
                            <span class="min-w-0 flex-1 truncate text-xs">{{ $upload->getClientOriginalName() }}</span>
                            <button type="button" wire:click="removeTicketUpload({{ $index }})" aria-label="Quitar {{ $upload->getClientOriginalName() }}" class="grid size-11 shrink-0 place-items-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground">&times;</button>
                        </div>
                    @endforeach
                </div>
            @endif
            @error('ticketUploads') <p role="alert" class="text-sm text-destructive">{{ $message }}</p> @enderror
            @error('ticketUploads.*') <p role="alert" class="text-sm text-destructive">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-border pt-5 sm:flex-row sm:justify-end">
            <button type="button" wire:click="cancelForm"
                class="min-h-11 rounded-md border border-input px-4 text-sm font-medium hover:bg-accent">Cancelar</button>
            <button type="submit" wire:loading.attr="disabled" wire:target="saveEscalation"
                class="inline-flex min-h-11 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60">
                <span wire:loading.remove wire:target="saveEscalation">{{ $editing ? 'Guardar cambios' : 'Registrar escalado' }}</span>
                <span wire:loading wire:target="saveEscalation">Guardando...</span>
            </button>
        </div>
    </form>
</x-modal>
