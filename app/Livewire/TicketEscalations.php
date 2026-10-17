<?php

namespace App\Livewire;

use App\Models\TicketEscalation;
use App\Models\TicketEscalationComment;
use App\Services\TicketEscalationAttachmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TicketEscalations extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    /** @var array<int, string> */
    public array $commentBodies = [];

    /** @var array<int, array<int, mixed>> */
    public array $commentUploads = [];

    public function mount(): void
    {
        $this->authorize('ticket_escalations.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteEscalation(int $escalationId): void
    {
        $this->authorize('ticket_escalations.delete');
        $this->ownedEscalation($escalationId)->delete();
        session()->flash('ticket-escalations-status', 'Escalado eliminado.');
    }

    public function addComment(int $escalationId, TicketEscalationAttachmentService $attachments): void
    {
        $this->authorize('ticket_escalations.view');
        $escalation = TicketEscalation::query()->findOrFail($escalationId);
        $this->commentBodies[$escalationId] ??= '';
        $this->commentUploads[$escalationId] ??= [];

        $body = trim($this->commentBodies[$escalationId]);
        $uploads = $this->commentUploads[$escalationId];
        if ($body === '' && $uploads === []) {
            $this->addError("commentBodies.{$escalationId}", 'Escribe un comentario o adjunta un archivo.');

            return;
        }

        $this->validate([
            "commentBodies.{$escalationId}" => ['nullable', 'string', 'max:5000'],
            "commentUploads.{$escalationId}" => ['array', 'max:5'],
            "commentUploads.{$escalationId}.*" => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,txt,csv', 'max:10240'],
        ], [
            "commentUploads.{$escalationId}.max" => 'Puedes adjuntar hasta 5 archivos.',
            "commentUploads.{$escalationId}.*.max" => 'Cada archivo debe pesar menos de 10 MB.',
            "commentUploads.{$escalationId}.*.mimes" => 'El formato de uno de los archivos no está permitido.',
        ]);

        DB::transaction(function () use ($escalation, $body, $uploads, $attachments): void {
            $comment = TicketEscalationComment::query()->create([
                'ticket_escalation_id' => $escalation->id,
                'user_id' => Auth::id(),
                'body' => $body !== '' ? $body : null,
            ]);

            $attachments->storeMany($escalation, Auth::user(), $uploads, $comment);
        });

        unset($this->commentBodies[$escalationId], $this->commentUploads[$escalationId]);
        $this->resetValidation(["commentBodies.{$escalationId}", "commentUploads.{$escalationId}"]);
        session()->flash('ticket-escalations-status', 'Comentario añadido al seguimiento.');
    }

    public function removeCommentUpload(int $escalationId, int $index): void
    {
        unset($this->commentUploads[$escalationId][$index]);
        $this->commentUploads[$escalationId] = array_values($this->commentUploads[$escalationId] ?? []);
        $this->resetValidation("commentUploads.{$escalationId}");
    }

    #[On('ticket-escalation-saved')]
    public function refreshEscalations(): void
    {
        // Rendering this component is required only after a successful save.
    }

    public function render()
    {
        $term = trim($this->search);

        $escalations = TicketEscalation::query()
            ->with([
                'creator',
                'ticketAttachments',
                'comments.user',
                'comments.attachments',
            ])
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('folio', 'like', "%{$term}%")
                        ->orWhere('title', 'like', "%{$term}%")
                        ->orWhere('escalated_area', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('escalated_at')
            ->orderByDesc('id')
            ->paginate(12);

        return view('livewire.ticket-escalations', [
            'escalations' => $escalations,
            'statuses' => TicketEscalation::STATUSES,
        ]);
    }

    private function ownedEscalation(int $escalationId): TicketEscalation
    {
        $escalation = TicketEscalation::query()->findOrFail($escalationId);
        abort_unless($escalation->created_by === Auth::id(), 403);

        return $escalation;
    }
}
