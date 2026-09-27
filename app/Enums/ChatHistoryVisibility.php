<?php

namespace App\Enums;

enum ChatHistoryVisibility: string
{
    case All = 'all';
    case SinceAdded = 'since_added';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Todo el historial',
            self::SinceAdded => 'Solo desde que fue agregado',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::All => 'La persona podrá consultar los mensajes y archivos enviados antes de entrar al grupo.',
            self::SinceAdded => 'La persona verá únicamente los mensajes y archivos publicados después de ser agregada.',
        };
    }
}
