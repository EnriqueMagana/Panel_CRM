<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_escalations', function (Blueprint $table): void {
            $table->string('escalated_area', 120)->default('Sin especificar')->after('title')->index();
        });

        Schema::create('ticket_escalation_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_escalation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->timestamps();
            $table->index(['ticket_escalation_id', 'created_at']);
        });

        Schema::create('ticket_escalation_attachments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ticket_escalation_id');
            $table->unsignedBigInteger('ticket_escalation_comment_id')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->foreign('ticket_escalation_id', 'tea_ticket_fk')->references('id')->on('ticket_escalations')->cascadeOnDelete();
            $table->foreign('ticket_escalation_comment_id', 'tea_comment_fk')->references('id')->on('ticket_escalation_comments')->cascadeOnDelete();
            $table->foreign('uploaded_by', 'tea_uploader_fk')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_escalation_attachments');
        Schema::dropIfExists('ticket_escalation_comments');

        Schema::table('ticket_escalations', function (Blueprint $table): void {
            $table->dropColumn('escalated_area');
        });
    }
};
