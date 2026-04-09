<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class IntegrationManual extends Component
{
    public string $search = '';

    /** @var array<int, array{id:string,title:string,keywords:string}> */
    private array $sectionMeta = [
        ['id' => 'sec-1', 'title' => 'Arquitectura general', 'keywords' => 'flujo botmaker bitrix canal abierto imconnector oauth bidireccional'],
        ['id' => 'sec-2', 'title' => 'Flujo A: Cliente → Agente', 'keywords' => 'whatsapp botmaker webhook canal abierto imconnector send messages'],
        ['id' => 'sec-3', 'title' => 'Flujo B: Agente → Cliente', 'keywords' => 'bitrix agente respuesta botmaker sendmessage delivery'],
        ['id' => 'sec-4', 'title' => 'Requisitos previos', 'keywords' => 'ssl https dominio php mysql queue worker'],
        ['id' => 'sec-5', 'title' => 'Configuración desde el panel', 'keywords' => 'panel admin settings oauth token api botmaker bitrix24 connector'],
        ['id' => 'sec-6', 'title' => 'Configuración en Bitrix24', 'keywords' => 'app local developer oauth install handler canal abierto contact center'],
        ['id' => 'sec-7', 'title' => 'Configuración en Botmaker', 'keywords' => 'webhook url auth-bm-token salida api'],
        ['id' => 'sec-8', 'title' => 'Validación diaria', 'keywords' => 'monitor logs recibidos exitosos fallidos health'],
        ['id' => 'sec-9', 'title' => 'Errores comunes y solución', 'keywords' => '401 422 500 queue bitrix token oauth expired'],
        ['id' => 'sec-10', 'title' => 'Comandos Artisan', 'keywords' => 'artisan queue work setup connector migrate seed'],
        ['id' => 'sec-11', 'title' => 'Responsables por área', 'keywords' => 'telecom operaciones infraestructura desarrollo'],
    ];

    /** @var list<array{situation:string,owner:string,action:string}> */
    public array $responsibilities = [
        ['situation' => 'No llegan mensajes de WhatsApp al Canal Abierto', 'owner' => 'Telecomunicaciones', 'action' => 'Verificar webhook de salida en Botmaker y token auth-bm-token'],
        ['situation' => 'Agente responde pero cliente no recibe en WhatsApp', 'owner' => 'Telecomunicaciones', 'action' => 'Verificar API Token de Botmaker en panel > Conexión Botmaker'],
        ['situation' => 'Error 401 en webhook entrante', 'owner' => 'Telecomunicaciones', 'action' => 'Sincronizar token entre Botmaker y panel > Webhooks autorizados'],
        ['situation' => 'Token OAuth expirado / error de refresh', 'owner' => 'Desarrollo', 'action' => 'Reinstalar App Local en Bitrix24 o verificar client_id/secret'],
        ['situation' => 'Conector no aparece en Contact Center', 'owner' => 'Desarrollo', 'action' => 'Ejecutar "Registrar/Activar conector" desde panel > Bitrix24'],
        ['situation' => 'Error 500 o cola detenida', 'owner' => 'Infraestructura', 'action' => 'Revisar queue worker, logs del servidor y conectividad a APIs externas'],
        ['situation' => 'Sin actividad en 24h', 'owner' => 'Telecomunicaciones', 'action' => 'Confirmar que el webhook en Botmaker siga activo y que hay tráfico real'],
        ['situation' => 'Cambio de dominio Bitrix24', 'owner' => 'Desarrollo', 'action' => 'Actualizar dominio en panel > Bitrix24, reinstalar App Local y re-registrar conector'],
    ];

    public function getFilteredSectionsProperty(): array
    {
        $term = mb_strtolower(trim($this->search));
        if ($term === '') {
            return $this->sectionMeta;
        }

        return array_values(array_filter($this->sectionMeta, function (array $section) use ($term): bool {
            $haystack = mb_strtolower($section['title'] . ' ' . $section['keywords']);

            return str_contains($haystack, $term);
        }));
    }

    public function render(): View
    {
        return view('livewire.integration-manual', [
            'filteredSections' => $this->filteredSections,
            'responsibilities' => $this->responsibilities,
        ])->layout('layouts.app', [
            'title' => 'Manual de integración',
            'breadcrumbs' => [
                ['label' => 'Manual'],
            ],
        ]);
    }
}
