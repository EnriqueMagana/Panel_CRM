<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme')->nullable()->after('status');
            $table->string('sidebar_variant')->nullable()->after('theme');
            $table->string('layout')->nullable()->after('sidebar_variant');
            $table->string('direction')->nullable()->after('layout');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme', 'sidebar_variant', 'layout', 'direction']);
        });
    }
};
