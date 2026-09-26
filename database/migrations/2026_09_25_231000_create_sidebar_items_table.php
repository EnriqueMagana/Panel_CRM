<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidebar_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('sidebar_items')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('label', 100);
            $table->string('icon', 40)->nullable();
            $table->string('route_name')->nullable();
            $table->string('route_fragment')->nullable();
            $table->string('action_key', 40)->nullable();
            $table->string('permission_name')->nullable();
            $table->string('active_route')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidebar_items');
    }
};
