<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(config('access.modules'))
            ->flatMap(fn (array $module, string $key) => collect($module['permissions'])
                ->keys()
                ->map(fn (string $action) => Permission::findOrCreate("{$key}.{$action}", 'web')));

        Role::findOrCreate('Super Admin', 'web')->syncPermissions($permissions);
    }
}
