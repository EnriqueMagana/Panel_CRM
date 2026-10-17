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
            ['key' => 'operations', 'parent' => null, 'type' => 'group', 'label' => 'Operación', 'icon' => 'operations', 'sort_order' => 27],
            ['key' => 'preset-messages', 'parent' => 'operations', 'type' => 'link', 'label' => 'Mensajes predeterminados', 'icon' => 'messages', 'route_name' => 'preset-messages', 'permission_name' => 'preset_messages.view', 'sort_order' => 10],
            ['key' => 'ticket-escalations', 'parent' => 'operations', 'type' => 'link', 'label' => 'Escalado de tickets', 'icon' => 'ticket', 'route_name' => 'ticket-escalations', 'permission_name' => 'ticket_escalations.view', 'sort_order' => 20],
            ['key' => 'snapshots', 'parent' => 'operations', 'type' => 'link', 'label' => 'Snapshots', 'icon' => 'camera', 'route_name' => 'snapshots', 'permission_name' => 'snapshots.view', 'sort_order' => 30],
            ['key' => 'iam', 'parent' => null, 'type' => 'group', 'label' => 'IAM', 'icon' => 'shield', 'sort_order' => 28],
            ['key' => 'technical-center', 'parent' => 'iam', 'type' => 'link', 'label' => 'Herramientas IAM', 'icon' => 'shield', 'route_name' => 'technical-center', 'permission_name' => 'technical_center.view', 'sort_order' => 10],
            ['key' => 'extensions', 'parent' => null, 'type' => 'group', 'label' => 'Extensiones', 'icon' => 'extension', 'sort_order' => 29],
            ['key' => 'browser-extension', 'parent' => 'extensions', 'type' => 'link', 'label' => 'Extensión del navegador', 'icon' => 'extension', 'route_name' => 'browser-extension', 'permission_name' => 'browser_extensions.view', 'sort_order' => 10],
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
            $record = $this->findExistingRecord($item);
            $record->fill([
                ...collect($item)->except(['key', 'parent'])->all(),
                'parent_id' => $parentId,
            ]);
            $record->is_active ??= true;
            $record->save();
            $ids[$item['key']] = $record->id;
        }
    }

    private function findExistingRecord(array $item): SidebarItem
    {
        if ($item['type'] === 'link') {
            return SidebarItem::query()->firstOrNew([
                'route_name' => $item['route_name'],
                'route_fragment' => $item['route_fragment'] ?? null,
            ]);
        }

        if ($item['type'] === 'action') {
            return SidebarItem::query()->firstOrNew(['action_key' => $item['action_key']]);
        }

        return SidebarItem::query()->firstOrNew([
            'type' => 'group',
            'label' => $item['label'],
        ]);
    }
}
