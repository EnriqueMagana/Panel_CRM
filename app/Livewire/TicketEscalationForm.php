<?php

namespace App\Livewire;

use App\Models\TicketEscalation;
use App\Services\TicketEscalationAttachmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class TicketEscalationForm extends Component
{
    use WithFileUploads;

    public bool $formOpen = false;

    public ?int $editingEscalationId = null;

    public string $folio = '';

    public string $title = '';

    public string $escalatedArea = '';

    public string $description = '';

    public string $status = 'pending';

    public string $escalatedAt = '';

    /** @var array<int, mixed> */
    public array $ticketUploads = [];

    public function mount(): void
    {
        $this->escalatedAt = now()->toDateString();
    }

    #[On('open-ticket-escalation-form')]
    public function open(?int $escalationId = null): void
    {
        $this->resetForm();

        if ($escalationId === null) {
            $this->authorize('ticket_escalations.create');
            $this->formOpen = true;

            return;
        }

        $this->authorize('ticket_escalations.update');
        $escalation = $this->ownedEscalation($escalationId);
        $this->editingEscalationId = $escalation->id;
        $this->folio = $escalation->folio;
        $this->title = $escalation->title;
        $this->escalatedArea = $escalation->escalated_area;
        $this->description = $escalation->description;
        $this->status = $escalation->status;
        $this->escalatedAt = $escalation->escalated_at->toDateString();
        $this->formOpen = true;
    }

    public function saveEscalation(TicketEscalationAttachmentService $attachments): void
    {
        $editing = $this->editingEscalationId !== null;
        $this->authorize($editing ? 'ticket_escalations.update' : 'ticket_escalations.create');

        $validated = $this->validate([
            'folio' => [
                'required',
                'string',
                'max:80',
                Rule::unique('ticket_escalations', 'folio')->ignore($this->editingEscalationId),
            ],
            'title' => ['required', 'string', 'max:160'],
            'escalatedArea' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:10000'],
            'status' => ['required', Rule::in(array_keys(TicketEscalation::STATUSES))],
            'escalatedAt' => ['required', 'date'],
            'ticketUploads' => ['array', 'max:5'],
            'ticketUploads.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,txt,csv', 'max:10240'],
        ], [
            'folio.unique' => 'Ya existe un escalado con este folio.',
            'escalatedAt.required' => 'La fecha es obligatoria.',
            'ticketUploads.max' => 'Puedes adjuntar hasta 5 archivos por actualización.',
            'ticketUploads.*.max' => 'Cada archivo debe pesar menos de 10 MB.',
            'ticketUploads.*.mimes' => 'El formato de uno de los archivos no está permitido.',
        ]);

        $attributes = [
            'folio' => mb_strtoupper(trim($validated['folio'])),
            'title' => trim($validated['title']),
            'escalated_area' => trim($validated['escalatedArea']),
            'description' => trim($validated['description']),
            'status' => $validated['status'],
            'escalated_at' => $validated['escalatedAt'],
        ];

        DB::transaction(function () use ($editing, $attributes, $attachments): void {
            if ($editing) {
                $escalation = $this->ownedEscalation($this->editingEscalationId);
                $escalation->update($attributes);
            } else {
                $escalation = TicketEscalation::query()->create([...$attributes, 'created_by' => Auth::id()]);
            }

            $attachments->storeMany($escalation, Auth::user(), $this->ticketUploads);
        });

        $this->resetForm();
        session()->flash('ticket-escalations-status', $editing ? 'Escalado actualizado.' : 'Escalado registrado.');
        $this->dispatch('ticket-escalation-saved');
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function removeTicketUpload(int $index): void
    {
        unset($this->ticketUploads[$index]);
        $this->ticketUploads = array_values($this->ticketUploads);
        $this->resetValidation('ticketUploads');
    }

    public function render()
    {
        return view('livewire.ticket-escalation-form', [
            'statuses' => TicketEscalation::STATUSES,
        ]);
    }

    private function ownedEscalation(int $escalationId): TicketEscalation
    {
        $escalation = TicketEscalation::query()->findOrFail($escalationId);
        abort_unless($escalation->created_by === Auth::id(), 403);

        return $escalation;
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->formOpen = false;
        $this->editingEscalationId = null;
        $this->folio = '';
        $this->title = '';
        $this->escalatedArea = '';
        $this->description = '';
        $this->status = 'pending';
        $this->escalatedAt = now()->toDateString();
        $this->ticketUploads = [];
    }
}
