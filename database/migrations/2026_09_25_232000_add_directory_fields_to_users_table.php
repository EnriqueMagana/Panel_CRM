<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('phone_number', 32)->nullable()->after('email');
            $table->string('status', 16)->default('active')->index()->after('password');
        });

        DB::table('users')->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                $baseUsername = Str::slug(Str::before($user->email, '@'), '.') ?: 'user';
                $username = $baseUsername;
                $suffix = 1;

                while (DB::table('users')->where('username', $username)->exists()) {
                    $username = $baseUsername.'.'.$suffix++;
                }

                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropIndex(['status']);
            $table->dropColumn(['username', 'phone_number', 'status']);
        });
    }
};
