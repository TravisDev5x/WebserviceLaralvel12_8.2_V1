<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['Contacto recibido', 'notification', 'Hola {nombre}, recibimos tu consulta y un agente te contactará pronto.'],
            ['En proceso', 'confirmation', '{nombre}, tu solicitud está siendo atendida. Te mantendremos informado.'],
            ['Caso cerrado', 'follow_up', '{nombre}, tu caso ha sido resuelto. Gracias por contactarnos.'],
            ['Mensaje libre', 'custom', '{mensaje}'],
        ];

        foreach ($templates as [$name, $category, $body]) {
            MessageTemplate::query()->updateOrCreate(
                ['slug' => Str::slug((string) $name)],
                [
                    'name' => $name,
                    'category' => $category,
                    'body' => $body,
                    'variables_available' => ['nombre', 'apellido', 'telefono', 'estatus', 'lead_id', 'agente', 'fecha', 'mensaje'],
                    'is_active' => true,
                ],
            );
        }
    }
}
