<?php

namespace App\Livewire;

use App\Enums\ChatHistoryVisibility;
use App\Services\TechnicalSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class TechnicalCenter extends Component
{
    public string $chatHistoryVisibility = ChatHistoryVisibility::All->value;

    public bool $saved = false;

    public function mount(TechnicalSettings $settings): void
    {
        $this->chatHistoryVisibility = $settings->chatHistoryVisibility()->value;
    }

    public function updatedChatHistoryVisibility(): void
    {
        $this->saved = false;
    }

    public function saveChatSettings(TechnicalSettings $settings): void
    {
        $this->authorize('technical_center.manage');

        $validated = $this->validate([
            'chatHistoryVisibility' => ['required', Rule::enum(ChatHistoryVisibility::class)],
        ]);

        $settings->updateChatHistoryVisibility(
            ChatHistoryVisibility::from($validated['chatHistoryVisibility']),
            Auth::user(),
        );

        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.technical-center', [
            'historyOptions' => ChatHistoryVisibility::cases(),
        ]);
    }
}
