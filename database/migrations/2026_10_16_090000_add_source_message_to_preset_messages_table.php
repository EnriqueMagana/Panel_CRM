<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preset_messages', function (Blueprint $table): void {
            $table->foreignId('source_message_id')
                ->nullable()
                ->after('created_by')
                ->constrained('preset_messages')
                ->nullOnDelete();
            $table->unique(['created_by', 'source_message_id']);
        });
    }

    public function down(): void
    {
        Schema::table('preset_messages', function (Blueprint $table): void {
            $table->dropUnique(['created_by', 'source_message_id']);
            $table->dropConstrainedForeignId('source_message_id');
        });
    }
};
