<?php

namespace App\Services;

use App\Models\PresetMessage;
use App\Models\PresetMessageUsageEvent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PresetMessageAnalyticsService
{
    public function recordUsage(User $user, PresetMessage $message, string $channel = 'clipboard'): PresetMessageUsageEvent
    {
        $type = $channel === 'browser'
            ? PresetMessageUsageEvent::TYPE_PASTED_BROWSER
            : PresetMessageUsageEvent::TYPE_COPIED;

        return PresetMessageUsageEvent::query()->create([
            'user_id' => $user->id,
            'preset_message_id' => $message->id,
            'message_title' => $message->title,
            'event_type' => $type,
            'saved_seconds' => $this->estimateSavedSeconds($message->body),
            'occurred_on' => today(),
            'context' => ['channel' => $channel],
        ]);
    }

    public function recordNoResultSearch(User $user, string $query, string $library): ?PresetMessageUsageEvent
    {
        $query = Str::limit(trim($query), 255, '');
        if (mb_strlen($query) < 2) {
            return null;
        }

        return PresetMessageUsageEvent::query()->firstOrCreate([
            'user_id' => $user->id,
            'event_type' => PresetMessageUsageEvent::TYPE_SEARCH_NO_RESULTS,
            'search_query' => mb_strtolower($query),
            'occurred_on' => today(),
        ], [
            'saved_seconds' => 0,
            'context' => ['library' => $library],
        ]);
    }

    /** @return array{total_copies: int, time_saved_seconds: int, abandoned_templates: int, searches_without_results: int, top_messages: Collection, top_searches: Collection} */
    public function dashboardFor(User $user): array
    {
        $usageTypes = [PresetMessageUsageEvent::TYPE_COPIED, PresetMessageUsageEvent::TYPE_PASTED_BROWSER];
        $events = PresetMessageUsageEvent::query()->where('user_id', $user->id);

        $summary = (clone $events)
            ->whereIn('event_type', $usageTypes)
            ->selectRaw('COUNT(*) as total_copies, COALESCE(SUM(saved_seconds), 0) as time_saved_seconds')
            ->first();

        $topMessages = (clone $events)
            ->whereIn('event_type', $usageTypes)
            ->selectRaw('preset_message_id, MAX(message_title) as title, COUNT(*) as copies')
            ->groupBy('preset_message_id')
            ->orderByDesc('copies')
            ->limit(5)
            ->get();

        $topSearches = (clone $events)
            ->where('event_type', PresetMessageUsageEvent::TYPE_SEARCH_NO_RESULTS)
            ->selectRaw('search_query, COUNT(*) as searches')
            ->groupBy('search_query')
            ->orderByDesc('searches')
            ->limit(5)
            ->get();

        $abandonedTemplates = PresetMessage::query()
            ->where('created_by', $user->id)
            ->where('is_active', true)
            ->whereDoesntHave('usageEvents', fn ($query) => $query->whereIn('event_type', $usageTypes))
            ->count();

        return [
            'total_copies' => (int) ($summary?->total_copies ?? 0),
            'time_saved_seconds' => (int) ($summary?->time_saved_seconds ?? 0),
            'abandoned_templates' => $abandonedTemplates,
            'searches_without_results' => (clone $events)->where('event_type', PresetMessageUsageEvent::TYPE_SEARCH_NO_RESULTS)->count(),
            'top_messages' => $topMessages,
            'top_searches' => $topSearches,
        ];
    }

    private function estimateSavedSeconds(string $message): int
    {
        $words = max(1, str_word_count(strip_tags($message)));

        return max(15, (int) ceil(($words / 35) * 60) - 5);
    }
}
