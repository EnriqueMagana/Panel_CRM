<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preset_message_preferences', function (Blueprint $table) {
            $table->string('theme', 30)->default('custom')->after('user_id');
            $table->string('accent_color', 7)->nullable()->after('accent');
            $table->string('background_color', 7)->nullable()->after('background');
            $table->string('background_image_path')->nullable()->after('background_color');
            $table->string('card_style', 20)->default('outlined')->after('background_image_path');
            $table->string('radius', 20)->default('rounded')->after('card_style');
            $table->string('font_scale', 20)->default('normal')->after('radius');
        });
    }

    public function down(): void
    {
        Schema::table('preset_message_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'theme',
                'accent_color',
                'background_color',
                'background_image_path',
                'card_style',
                'radius',
                'font_scale',
            ]);
        });
    }
};
