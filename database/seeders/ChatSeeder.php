<?php

namespace Database\Seeders;

use App\Models\ChatContact;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->orderBy('name')->get();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            ChatContact::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $user->name,
                    'role' => 'Team member',
                    'initials' => strtoupper(substr($user->name, 0, 2)),
                    'accent' => 'from-slate-500 to-slate-700',
                    'status' => 'online',
                    'is_active' => true,
                    'sort_order' => 0,
                ],
            );
        }

        // Intentionally empty: conversations are created only when a user starts a chat or group
        // and they are stored in the database from the UI flow.
    }
}
