<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preset_message_preferences', function (Blueprint $table) {
            $table->unsignedTinyInteger('font_size')->default(14)->after('font_scale');
        });

        DB::table('preset_message_preferences')->where('font_scale', 'small')->update(['font_size' => 12]);
        DB::table('preset_message_preferences')->where('font_scale', 'normal')->update(['font_size' => 14]);
        DB::table('preset_message_preferences')->where('font_scale', 'large')->update(['font_size' => 16]);

        Schema::table('preset_message_preferences', function (Blueprint $table) {
            $table->dropColumn('font_scale');
        });
    }

    public function down(): void
    {
        Schema::table('preset_message_preferences', function (Blueprint $table) {
            $table->string('font_scale', 20)->default('normal')->after('radius');
        });

        DB::table('preset_message_preferences')->where('font_size', '<=', 12)->update(['font_scale' => 'small']);
        DB::table('preset_message_preferences')->whereBetween('font_size', [13, 15])->update(['font_scale' => 'normal']);
        DB::table('preset_message_preferences')->where('font_size', '>=', 16)->update(['font_scale' => 'large']);

        Schema::table('preset_message_preferences', function (Blueprint $table) {
            $table->dropColumn('font_size');
        });
    }
};
