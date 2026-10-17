<?php

namespace Database\Seeders;

use App\Models\BrowserExtension;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BrowserExtensionSeeder extends Seeder
{
    public function run(): void
    {
        $source = public_path('downloads/snippetdesk-browser-extension.zip');
        $target = 'browser-extensions/snippetdesk-browser-extension.zip';

        if (is_file($source)) {
            Storage::disk('public')->put($target, file_get_contents($source));
        }

        if (! Storage::disk('public')->exists($target)) {
            return;
        }

        $extension = BrowserExtension::query()->firstOrNew(['slug' => 'snippetdesk-browser']);
        $extension->fill([
            'name' => 'Snippetdesk para navegador',
            'version' => '1.3.0',
            'description' => 'Configura un campo una sola vez y pega mensajes en la última pestaña compatible, incluso al gestionar varios tickets simultáneamente.',
            'features' => [
                'Panel lateral sin cambiar de pestaña',
                'Selector visual global para inputs, textareas y editores',
                'Seguimiento de la última pestaña de ticket visitada',
                'Reutilización automática del selector entre tickets del mismo sistema',
                'Destino persistente aunque el campo pierda el foco',
                'Verificación y resaltado del campo configurado',
                'Detección automática de conexión con Snippetdesk',
            ],
            'supported_browsers' => 'Chrome, Edge',
            'zip_path' => $target,
            'zip_size' => Storage::disk('public')->size($target),
        ]);
        $extension->is_active ??= true;
        $extension->save();
    }
}
