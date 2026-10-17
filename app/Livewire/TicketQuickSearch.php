<?php

namespace App\Livewire;

use App\Models\SidebarItem;
use App\Models\TicketEscalation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class TicketQuickSearch extends Component
{
    public bool $open = false;

    public string $search = '';

    public ?int $selectedTicketId = null;

    #[On('open-ticket-quick-search')]
    public function openSearch(): void
    {
        $this->authorizeAccess();
        $this->reset('search', 'selectedTicketId');
        $this->open = true;
    }

    public function updatedSearch(): void
    {
        $this->selectedTicketId = null;
    }

    public function selectTicket(int $ticketId): void
    {
        $this->authorizeAccess();
        TicketEscalation::query()->findOrFail($ticketId);
        $this->selectedTicketId = $ticketId;
    }

    public function closeSearch(): void
    {
        $this->open = false;
        $this->reset('search', 'selectedTicketId');
    }

    public function render()
    {
        if (! $this->open) {
            return view('livewire.ticket-quick-search', [
                'tickets' => collect(),
                'selectedTicket' => null,
            ]);
        }

        $this->authorizeAccess();
        $term = trim($this->search);
        $tickets = $this->searchTickets($term);
        $selectedTicket = $this->selectedTicketId
            ? TicketEscalation::query()
                ->with(['creator', 'ticketAttachments', 'comments.user', 'comments.attachments'])
                ->findOrFail($this->selectedTicketId)
            : null;

        return view('livewire.ticket-quick-search', [
            'tickets' => $tickets,
            'selectedTicket' => $selectedTicket,
        ]);
    }

    /** @return Collection<int, TicketEscalation> */
    private function searchTickets(string $term): Collection
    {
        if ($term !== '' && mb_strlen($term) < 2) {
            return collect();
        }

        return TicketEscalation::query()
            ->with('creator')
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('folio', 'like', "%{$term}%")
                        ->orWhere('title', 'like', "%{$term}%")
                        ->orWhere('escalated_area', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('escalated_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    private function authorizeAccess(): void
    {
        $this->authorize('ticket_escalations.view');
        $items = SidebarItem::query()
            ->with('parent:id,is_active')
            ->where('route_name', 'ticket-escalations')
            ->get();

        if ($items->isNotEmpty()) {
            abort_unless($items->contains(
                fn (SidebarItem $item): bool => $item->is_active
                    && ($item->parent_id === null || $item->parent?->is_active)
            ), 403, 'El módulo de escalado de tickets está desactivado.');
        }
    }
}
