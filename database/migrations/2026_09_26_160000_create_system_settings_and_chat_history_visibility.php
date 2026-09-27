<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group')->default('general')->index();
            $table->text('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('chat_conversation_user', function (Blueprint $table) {
            $table->unsignedBigInteger('history_visible_after_message_id')->nullable()->after('joined_at');
        });

        DB::table('system_settings')->insert([
            'key' => 'chat.new_participant_history',
            'group' => 'chat',
            'value' => 'all',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('chat_conversation_user', function (Blueprint $table) {
            $table->dropColumn('history_visible_after_message_id');
        });

        Schema::dropIfExists('system_settings');
    }
};
