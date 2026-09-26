<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('initials', 4)->nullable();
            $table->string('accent')->nullable();
            $table->string('status')->default('online');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['direct', 'group'])->default('direct');
            $table->string('name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_admin')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['chat_conversation_id', 'user_id']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('direction', ['incoming', 'outgoing'])->default('incoming');
            $table->text('body');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversation_user');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('chat_contacts');
    }
};
