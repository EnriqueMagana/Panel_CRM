<div>
    @if ($formOpen)
        <x-ticket-escalations.form-modal :editing="$editingEscalationId !== null" :statuses="$statuses" :uploads="$ticketUploads" />
    @endif
</div>
