<?php

namespace App\Livewire;

use App\Services\PresetMessageAnalyticsService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UsageAnalytics extends Component
{
    public function render(PresetMessageAnalyticsService $analytics)
    {
        $metrics = $analytics->dashboardFor(Auth::user());

        return view('livewire.usage-analytics', [
            ...$metrics,
            'timeSavedLabel' => $this->formatDuration($metrics['time_saved_seconds']),
        ]);
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.' s';
        }

        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            return $minutes.' min';
        }

        return intdiv($minutes, 60).' h '.($minutes % 60).' min';
    }
}
