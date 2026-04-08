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
        ['id' => 'sec-1', 'title' => 'Flujo del sistema', 'keywords' => 'flujo botmaker webhook bitrix lead'],
        ['id' => 'sec-2', 'title' => 'Configuración mínima', 'keywords' => 'app_url webhook auth-bm-token https'],
        ['id' => 'sec-3', 'title' => 'Validación diaria', 'keywords' => 'monitor logs recibidos exitosos fallidos'],
        ['id' => 'sec-4', 'title' => 'Errores comunes y solución', 'keywords' => '401 422 500 queue bitrix token'],
        ['id' => 'sec-5', 'title' => 'Responsables por área', 'keywords' => 'telecom operaciones infraestructura'],
    ];

    /** @var array<int, array{situation:string,owner:string,action:string,section:string}> */
    public array $responsibilities = [
        ['situation' => 'No llegan webhooks al sistema', 'owner' => 'Telecomunicaciones', 'action' => 'Revisar URL de salida y token auth-bm-token en Botmaker', 'section' => '2'],
        ['situation' => 'Llega webhook pero no se crea lead', 'owner' => 'Operaciones', 'action' => 'Validar webhook de Bitrix24 y revisar errores en Monitor', 'section' => '4'],
        ['situation' => 'Respuesta 401', 'owner' => 'Telecomunicaciones', 'action' => 'Sincronizar token activo entre Botmaker y authorized_tokens', 'section' => '4'],
        ['situation' => 'Respuesta 500 o cola detenida', 'owner' => 'Infraestructura', 'action' => 'Revisar workers/servicios y logs del servidor', 'section' => '4'],
        ['situation' => 'Sin actividad en 24h', 'owner' => 'Telecomunicaciones', 'action' => 'Confirmar que el webhook en Botmaker siga activo', 'section' => '3'],
    ];

    public function getFilteredSectionsProperty(): array
    {
        $term = mb_strtolower(trim($this->search));
        if ($term === '') {
            return $this->sectionMeta;
        }

        return array_values(array_filter($this->sectionMeta, function (array $section) use ($term): bool {
            $haystack = mb_strtolower($section['title'].' '.$section['keywords']);

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
