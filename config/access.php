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
                'suspend' => 'Suspender o reactivar',
                'force_password_reset' => 'Forzar cambio de contraseña',
            ],
        ],
        'roles' => [
            'label' => 'Roles',
            'permissions' => [
                'view' => 'Ver',
                'create' => 'Crear',
                'update' => 'Editar',
                'delete' => 'Eliminar',
                'manage_permissions' => 'Asignar permisos',
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
                'manage_iam' => 'Administrar políticas IAM',
                'view_sessions' => 'Consultar sesiones activas',
                'revoke_sessions' => 'Cerrar sesiones remotas',
                'view_audit' => 'Consultar bitácora IAM',
                'export_audit' => 'Exportar bitácora IAM',
            ],
        ],
        'preset_messages' => [
            'label' => 'Mensajes predeterminados',
            'permissions' => [
                'view' => 'Ver',
                'view_global' => 'Ver biblioteca global',
                'create' => 'Crear',
                'update' => 'Editar',
                'delete' => 'Eliminar',
                'export' => 'Exportar',
            ],
        ],
        'ticket_escalations' => [
            'label' => 'Escalado de tickets',
            'permissions' => [
                'view' => 'Ver',
                'create' => 'Crear',
                'update' => 'Editar propios',
                'delete' => 'Eliminar propios',
                'download_evidence' => 'Descargar evidencias',
                'export' => 'Exportar',
            ],
        ],
        'snapshots' => [
            'label' => 'Snapshots',
            'permissions' => [
                'view' => 'Ver',
                'download' => 'Descargar',
            ],
        ],
        'browser_extensions' => [
            'label' => 'Extensión del navegador',
            'permissions' => [
                'view' => 'Ver',
                'manage' => 'Configurar',
                'download' => 'Descargar',
            ],
        ],
    ],
];
