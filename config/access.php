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
    ],
];
