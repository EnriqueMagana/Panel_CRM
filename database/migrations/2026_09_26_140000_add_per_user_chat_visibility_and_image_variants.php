<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversation_user', function (Blueprint $table) {
            $table->timestamp('hidden_at')->nullable()->after('joined_at');
            $table->index(['user_id', 'hidden_at'], 'chat_participant_visibility_index');
        });

        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->json('image_variants')->nullable()->after('image_path');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->json('attachment_variants')->nullable()->after('attachment_path');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('profile_photo_variants')->nullable()->after('profile_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_photo_variants');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn('attachment_variants');
        });

        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropColumn('image_variants');
        });

        Schema::table('chat_conversation_user', function (Blueprint $table) {
            $table->dropIndex('chat_participant_visibility_index');
            $table->dropColumn('hidden_at');
        });
    }
};
