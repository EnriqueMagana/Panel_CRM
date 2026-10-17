<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preset_message_usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('preset_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_title', 120)->nullable();
            $table->string('event_type', 40);
            $table->string('search_query')->nullable();
            $table->json('context')->nullable();
            $table->unsignedInteger('saved_seconds')->default(0);
            $table->date('occurred_on');
            $table->timestamps();

            $table->index(['user_id', 'event_type', 'occurred_on'], 'preset_usage_user_type_date');
            $table->index(['preset_message_id', 'event_type'], 'preset_usage_message_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preset_message_usage_events');
    }
};
