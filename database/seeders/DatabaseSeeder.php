<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
        ]);

        $this->call(AccessControlSeeder::class);
        $this->call(SidebarItemSeeder::class);
        $this->call(ChatSeeder::class);

        if (app()->isLocal()) {
            $user->assignRole('Super Admin');
        }
    }
}
