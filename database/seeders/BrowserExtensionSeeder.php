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

        if (is_file($source) && ! Storage::disk('public')->exists($target)) {
            Storage::disk('public')->put($target, file_get_contents($source));
        }

        if (! Storage::disk('public')->exists($target)) {
            return;
        }

        $extension = BrowserExtension::query()->firstOrNew(['slug' => 'snippetdesk-browser']);
        $extension->fill([
            'name' => 'Snippetdesk para navegador',
            'version' => '1.0.0',
            'description' => 'Abre Snippetdesk en el panel lateral del navegador y pega mensajes directamente en el campo activo del sistema de tickets.',
            'features' => [
                'Panel lateral sin cambiar de pestaña',
                'Pegado directo en inputs, textareas y editores compatibles',
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
