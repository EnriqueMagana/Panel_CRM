<?php

namespace Database\Seeders;

use App\Models\PresetMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class PresetMessageSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::query()->oldest('id')->value('id');
        $messages = [
            ['title' => 'Saludo inicial', 'body' => 'Hola, gracias por comunicarte con nosotros. ¿En qué podemos ayudarte?'],
            ['title' => 'Seguimiento personalizado', 'body' => 'Hola {{nombre}}, damos seguimiento a tu solicitud {{folio}}. En breve te compartiremos una actualización.'],
            ['title' => 'Despedida', 'body' => 'Gracias por tu tiempo. Si necesitas algo más, estamos para ayudarte.'],
        ];

        foreach ($messages as $message) {
            PresetMessage::query()->updateOrCreate(['title' => $message['title']], [
                ...$message,
                'created_by' => $creatorId,
                'variables' => PresetMessage::extractVariables($message['body']),
                'is_active' => true,
            ]);
        }
    }
}
