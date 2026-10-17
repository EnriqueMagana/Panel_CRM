<?php

return [
    'modules' => [
        'dashboard' => [
            'label' => 'Inicio',
            'permissions' => [
                'view' => 'Ver',
            ],
        ],
        'users' => [
            'label' => 'Usuarios',
            'permissions' => [
                'view' => 'Ver',
                'create' => 'Crear',
                'update' => 'Editar',
                'delete' => 'Eliminar',
                'assign_roles' => 'Asignar roles',
            ],
        ],
        'roles' => [
            'label' => 'Roles',
            'permissions' => [
                'view' => 'Ver',
                'create' => 'Crear',
                'update' => 'Editar',
                'delete' => 'Eliminar',
            ],
        ],
        'navigation' => [
            'label' => 'Navegación',
            'permissions' => [
                'view' => 'Ver',
                'manage' => 'Administrar',
            ],
        ],
        'technical_center' => [
            'label' => 'Centro técnico',
            'permissions' => [
                'view' => 'Ver',
                'manage' => 'Administrar',
            ],
        ],
        'preset_messages' => [
            'label' => 'Mensajes predeterminados',
            'permissions' => [
                'view' => 'Ver',
                'create' => 'Crear',
                'update' => 'Editar',
                'delete' => 'Eliminar',
            ],
        ],
        'ticket_escalations' => [
            'label' => 'Escalado de tickets',
            'permissions' => [
                'view' => 'Ver',
                'create' => 'Crear',
                'update' => 'Editar propios',
                'delete' => 'Eliminar propios',
            ],
        ],
        'browser_extensions' => [
            'label' => 'Extensión del navegador',
            'permissions' => [
                'view' => 'Ver',
                'manage' => 'Configurar',
            ],
        ],
    ],
];
