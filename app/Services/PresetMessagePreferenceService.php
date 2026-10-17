<?php

namespace App\Services;

use App\Models\PresetMessage;
use App\Models\PresetMessagePreference;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PresetMessagePreferenceService
{
    public function preferencesFor(User $user): PresetMessagePreference
    {
        return PresetMessagePreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'theme' => 'classic',
                'layout' => 'grid',
                'density' => 'comfortable',
                'accent' => 'blue',
                'background' => 'plain',
                'card_style' => 'outlined',
                'radius' => 'rounded',
                'font_size' => 14,
            ],
        );
    }

    /** @param array<string, mixed> $values */
    public function savePreferences(User $user, array $values): PresetMessagePreference
    {
        $theme = $values['theme'] ?? 'custom';
        abort_unless($theme === 'custom' || isset(PresetMessagePreference::THEMES[$theme]), 422);

        if ($theme !== 'custom') {
            $values = [...$values, ...collect(PresetMessagePreference::THEMES[$theme])->except('label')->all()];
        }

        $allowed = [
            'layout' => PresetMessagePreference::LAYOUTS,
            'density' => PresetMessagePreference::DENSITIES,
            'accent' => PresetMessagePreference::ACCENTS,
            'background' => PresetMessagePreference::BACKGROUNDS,
            'card_style' => PresetMessagePreference::CARD_STYLES,
            'radius' => PresetMessagePreference::RADII,
        ];

        foreach ($allowed as $key => $options) {
            abort_unless(isset($values[$key]) && in_array($values[$key], $options, true), 422);
        }

        abort_unless(
            isset($values['font_size'])
            && is_numeric($values['font_size'])
            && (int) $values['font_size'] >= PresetMessagePreference::MIN_FONT_SIZE
            && (int) $values['font_size'] <= PresetMessagePreference::MAX_FONT_SIZE,
            422,
        );
        $values['font_size'] = (int) $values['font_size'];

        foreach (['accent_color', 'background_color'] as $colorKey) {
            abort_unless(blank($values[$colorKey] ?? null) || preg_match('/^#[0-9A-Fa-f]{6}$/', $values[$colorKey]) === 1, 422);
        }

        $preferences = $this->preferencesFor($user);
        $preferences->update(collect($values)->only($preferences->getFillable())->all());

        return $preferences->refresh();
    }

    public function updatePreference(User $user, string $key, string $value): PresetMessagePreference
    {
        $allowed = [
            'layout' => PresetMessagePreference::LAYOUTS,
            'density' => PresetMessagePreference::DENSITIES,
            'accent' => PresetMessagePreference::ACCENTS,
            'background' => PresetMessagePreference::BACKGROUNDS,
        ];

        abort_unless(isset($allowed[$key]) && in_array($value, $allowed[$key], true), 422);

        $preferences = $this->preferencesFor($user);
        $preferences->update([$key => $value]);

        return $preferences->refresh();
    }

    public function togglePin(User $user, PresetMessage $message): void
    {
        $this->ensureVisibleTo($user, $message);

        DB::transaction(function () use ($user, $message): void {
            $state = DB::table('preset_message_user')
                ->where('user_id', $user->id)
                ->where('preset_message_id', $message->id)
                ->lockForUpdate()
                ->first();

            if ($state?->pinned_at) {
                DB::table('preset_message_user')->where('id', $state->id)->update([
                    'pinned_at' => null,
                    'pin_order' => null,
                    'updated_at' => now(),
                ]);
                $this->normalizePinOrder($user);

                return;
            }

            $nextOrder = ((int) DB::table('preset_message_user')
                ->where('user_id', $user->id)
                ->whereNotNull('pinned_at')
                ->max('pin_order')) + 1;

            DB::table('preset_message_user')->updateOrInsert(
                ['user_id' => $user->id, 'preset_message_id' => $message->id],
                [
                    'pinned_at' => now(),
                    'pin_order' => $nextOrder,
                    'hidden_at' => null,
                    'created_at' => $state?->created_at ?? now(),
                    'updated_at' => now(),
                ],
            );
        });
    }

    public function movePinned(User $user, PresetMessage $message, PresetMessage $target, string $direction): void
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 422);

        DB::transaction(function () use ($user, $message, $target): void {
            $states = DB::table('preset_message_user')
                ->where('user_id', $user->id)
                ->whereIn('preset_message_id', [$message->id, $target->id])
                ->whereNotNull('pinned_at')
                ->whereNull('hidden_at')
                ->lockForUpdate()
                ->get();
            abort_unless($states->count() === 2, 422);

            $currentState = $states->firstWhere('preset_message_id', $message->id);
            $targetState = $states->firstWhere('preset_message_id', $target->id);
            DB::table('preset_message_user')->where('id', $currentState->id)->update(['pin_order' => $targetState->pin_order, 'updated_at' => now()]);
            DB::table('preset_message_user')->where('id', $targetState->id)->update(['pin_order' => $currentState->pin_order, 'updated_at' => now()]);
        });
    }

    /** @param list<int> $messageIds */
    public function reorderPinned(User $user, array $messageIds): void
    {
        $messageIds = array_values(array_unique(array_map('intval', $messageIds)));

        DB::transaction(function () use ($user, $messageIds): void {
            $states = DB::table('preset_message_user')
                ->where('user_id', $user->id)
                ->whereIn('preset_message_id', $messageIds)
                ->whereNotNull('pinned_at')
                ->whereNull('hidden_at')
                ->orderBy('pin_order')
                ->lockForUpdate()
                ->get();
            abort_unless($states->count() === count($messageIds), 422);

            $orders = $states->pluck('pin_order')->sort()->values();

            foreach ($messageIds as $index => $messageId) {
                DB::table('preset_message_user')
                    ->where('user_id', $user->id)
                    ->where('preset_message_id', $messageId)
                    ->update(['pin_order' => $orders[$index], 'updated_at' => now()]);
            }
        });
    }

    public function hide(User $user, PresetMessage $message): void
    {
        $this->ensureVisibleTo($user, $message);

        DB::table('preset_message_user')->updateOrInsert(
            ['user_id' => $user->id, 'preset_message_id' => $message->id],
            [
                'pinned_at' => null,
                'pin_order' => null,
                'hidden_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        $this->normalizePinOrder($user);
    }

    public function restore(User $user, PresetMessage $message): void
    {
        DB::table('preset_message_user')
            ->where('user_id', $user->id)
            ->where('preset_message_id', $message->id)
            ->update(['hidden_at' => null, 'updated_at' => now()]);
    }

    private function normalizePinOrder(User $user): void
    {
        DB::table('preset_message_user')
            ->where('user_id', $user->id)
            ->whereNotNull('pinned_at')
            ->whereNull('hidden_at')
            ->orderBy('pin_order')
            ->pluck('preset_message_id')
            ->each(function ($messageId, $index) use ($user): void {
                DB::table('preset_message_user')
                    ->where('user_id', $user->id)
                    ->where('preset_message_id', $messageId)
                    ->update(['pin_order' => $index + 1]);
            });
    }

    private function ensureVisibleTo(User $user, PresetMessage $message): void
    {
        abort_unless($message->created_by === $user->id || $message->is_active, 404);
    }
}
