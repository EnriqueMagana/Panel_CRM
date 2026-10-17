<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preset_message_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('layout', 20)->default('grid');
            $table->string('density', 20)->default('comfortable');
            $table->string('accent', 20)->default('blue');
            $table->string('background', 20)->default('plain');
            $table->timestamps();
        });

        Schema::create('preset_message_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preset_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('pinned_at')->nullable();
            $table->unsignedInteger('pin_order')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->unique(['preset_message_id', 'user_id']);
            $table->index(['user_id', 'pinned_at', 'pin_order'], 'preset_message_user_pin_index');
            $table->index(['user_id', 'hidden_at'], 'preset_message_user_hidden_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preset_message_user');
        Schema::dropIfExists('preset_message_preferences');
    }
};
