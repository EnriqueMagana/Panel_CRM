<?php

namespace Database\Seeders;

use App\Models\SidebarItem;
use Illuminate\Database\Seeder;

class SidebarItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['key' => 'dashboard', 'parent' => null, 'type' => 'link', 'label' => 'Inicio', 'icon' => 'home', 'route_name' => 'home', 'permission_name' => 'dashboard.view', 'sort_order' => 10],
            ['key' => 'administration', 'parent' => null, 'type' => 'group', 'label' => 'Administración', 'icon' => 'settings', 'sort_order' => 20],
            ['key' => 'users', 'parent' => 'administration', 'type' => 'link', 'label' => 'Usuarios', 'icon' => 'users', 'route_name' => 'users', 'permission_name' => 'users.view', 'sort_order' => 10],
            ['key' => 'roles', 'parent' => 'administration', 'type' => 'link', 'label' => 'Roles', 'icon' => 'shield', 'route_name' => 'roles', 'permission_name' => 'roles.view', 'sort_order' => 20],
            ['key' => 'navigation', 'parent' => 'administration', 'type' => 'link', 'label' => 'Navegación', 'icon' => 'menu', 'route_name' => 'navigation', 'permission_name' => 'navigation.view', 'sort_order' => 30],
            ['key' => 'chat', 'parent' => null, 'type' => 'link', 'label' => 'Chats', 'icon' => 'chat', 'route_name' => 'chats', 'sort_order' => 25],
            ['key' => 'account', 'parent' => null, 'type' => 'group', 'label' => 'Cuenta', 'icon' => 'user', 'sort_order' => 30],
            ['key' => 'profile-details', 'parent' => 'account', 'type' => 'link', 'label' => 'Perfil', 'icon' => 'user', 'route_name' => 'profile', 'route_fragment' => 'profile-details', 'active_route' => 'profile', 'sort_order' => 10],
            ['key' => 'password', 'parent' => 'account', 'type' => 'link', 'label' => 'Contraseña', 'icon' => 'key', 'route_name' => 'profile', 'route_fragment' => 'password-heading', 'active_route' => 'profile', 'sort_order' => 20],
            ['key' => 'two-factor', 'parent' => 'account', 'type' => 'link', 'label' => 'Verificación en dos pasos', 'icon' => 'lock', 'route_name' => 'profile', 'route_fragment' => 'two-factor-heading', 'active_route' => 'profile', 'sort_order' => 30],
            ['key' => 'preferences', 'parent' => null, 'type' => 'group', 'label' => 'Preferencias', 'icon' => 'settings', 'sort_order' => 40],
            ['key' => 'search', 'parent' => 'preferences', 'type' => 'action', 'label' => 'Buscar', 'icon' => 'search', 'action_key' => 'search', 'sort_order' => 10],
        ];

        $ids = [];
        foreach ($items as $item) {
            $parentId = $item['parent'] ? $ids[$item['parent']] : null;
            $record = SidebarItem::query()->updateOrCreate(
                ['label' => $item['label'], 'parent_id' => $parentId],
                [
                    ...collect($item)->except(['key', 'parent'])->all(),
                    'parent_id' => $parentId,
                    'is_active' => true,
                ],
            );
            $ids[$item['key']] = $record->id;
        }
    }
}
