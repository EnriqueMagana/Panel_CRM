<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table): void {
            $table->uuid('avatar_seed')->nullable()->after('image_path');
        });

        DB::table('chat_conversations')
            ->where('type', 'group')
            ->whereNull('avatar_seed')
            ->orderBy('id')
            ->eachById(function ($conversation): void {
                DB::table('chat_conversations')
                    ->where('id', $conversation->id)
                    ->update(['avatar_seed' => (string) Str::uuid()]);
            });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table): void {
            $table->dropColumn('avatar_seed');
        });
    }
};
