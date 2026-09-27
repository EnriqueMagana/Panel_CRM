<?php

namespace App\Services;

use App\Enums\ChatHistoryVisibility;
use App\Models\SystemSetting;
use App\Models\User;

class TechnicalSettings
{
    public const CHAT_NEW_PARTICIPANT_HISTORY = 'chat.new_participant_history';

    public function chatHistoryVisibility(): ChatHistoryVisibility
    {
        $value = SystemSetting::query()
            ->where('key', self::CHAT_NEW_PARTICIPANT_HISTORY)
            ->value('value');

        return ChatHistoryVisibility::tryFrom((string) $value) ?? ChatHistoryVisibility::All;
    }

    public function updateChatHistoryVisibility(ChatHistoryVisibility $visibility, User $actor): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => self::CHAT_NEW_PARTICIPANT_HISTORY],
            [
                'group' => 'chat',
                'value' => $visibility->value,
                'updated_by' => $actor->id,
            ],
        );
    }
}
