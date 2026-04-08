<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AuthorizedToken;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class IntegrationManual extends Component
{
    public string $search = '';

    public string $responsibilityFilter = 'todos';

    /** @var array<int, array{id:string,title:string,keywords:string}> */
    private array $sectionMeta = [
        ['id' => 'sec-1', 'title' => '¿Qué es este sistema y cómo funciona?', 'keywords' => 'sistema flujo whatsapp botmaker bitrix crm puente automatico diagrama'],
        ['id' => 'sec-2', 'title' => 'Guía rápida por rol', 'keywords' => 'roles telecomunicaciones operaciones infraestructura guia rapida'],
        ['id' => 'sec-3', 'title' => 'Tablero — Cómo leerlo en 10 segundos', 'keywords' => 'tablero dashboard contadores semaforo verificacion diaria'],
        ['id' => 'sec-4', 'title' => 'Diagnóstico — Algo no funciona', 'keywords' => 'diagnostico wizard arbol decisiones errores fallas'],
        ['id' => 'sec-5', 'title' => 'Guía completa para Telecomunicaciones', 'keywords' => 'telecom botmaker token webhook api jwt auth-bm-token'],
        ['id' => 'sec-6', 'title' => 'Guía completa para Operaciones (Bitrix24)', 'keywords' => 'operaciones bitrix24 crm webhook mapeo campos leads'],
        ['id' => 'sec-7', 'title' => 'Guía completa para Infraestructura', 'keywords' => 'infraestructura servidor ssl https nginx mysql supervisor'],
        ['id' => 'sec-8', 'title' => 'Glosario visual', 'keywords' => 'glosario webhook lead token api ssl cola flujo'],
        ['id' => 'sec-9', 'title' => 'Tabla de responsabilidades', 'keywords' => 'responsabilidades quien resuelve situaciones telecom operaciones infraestructura'],
        ['id' => 'sec-10', 'title' => 'Historial de cambios', 'keywords' => 'historial cambios configuracion activity log settings authorized tokens'],
    ];

    /** @var array<int, array{situation:string,owner:string,action:string,section:string}> */
    public array $responsibilities = [
        ['situation' => 'No llegan mensajes de WhatsApp al sistema', 'owner' => 'Telecomunicaciones', 'action' => 'Verificar webhook de salida en Botmaker', 'section' => '5.2'],
        ['situation' => 'Los leads no se crean en Bitrix24', 'owner' => 'Operaciones', 'action' => 'Verificar webhook entrante en Bitrix24', 'section' => '6.2'],
        ['situation' => 'Error 401 al enviar mensajes', 'owner' => 'Telecomunicaciones', 'action' => 'Verificar permisos del token API', 'section' => '5.3'],
        ['situation' => 'Token de Bitrix24 expiró', 'owner' => 'Operaciones', 'action' => 'Regenerar webhook y actualizar en el sistema', 'section' => '6.2'],
        ['situation' => 'El servidor no responde', 'owner' => 'Infraestructura', 'action' => 'Verificar servicios y reiniciar', 'section' => '7.4'],
        ['situation' => 'Falta HTTPS/SSL', 'owner' => 'Infraestructura', 'action' => 'Configurar dominio y certificado', 'section' => '7.2'],
        ['situation' => 'Necesito agregar un campo nuevo al lead', 'owner' => 'Operaciones', 'action' => 'Configurar en Mapeo de campos', 'section' => '6.4'],
        ['situation' => 'Quiero ajustar qué datos se envían al lead', 'owner' => 'Operaciones', 'action' => 'Configurar en Mapeo de campos', 'section' => '6.3'],
        ['situation' => 'Quiero agregar un nuevo número de WhatsApp', 'owner' => 'Telecomunicaciones', 'action' => 'Agregar en Configuración > Números WhatsApp', 'section' => '5'],
        ['situation' => 'Un usuario necesita acceso al panel', 'owner' => 'Admin del sistema', 'action' => 'Ir a Usuarios y activar o cambiar rol', 'section' => 'Sistema'],
        ['situation' => 'El procesamiento está lento', 'owner' => 'Infraestructura', 'action' => 'Verificar workers con supervisorctl', 'section' => '7.4'],
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

    public function getFilteredResponsibilitiesProperty(): array
    {
        if ($this->responsibilityFilter === 'todos') {
            return $this->responsibilities;
        }

        return array_values(array_filter($this->responsibilities, function (array $row): bool {
            return mb_strtolower($row['owner']) === mb_strtolower($this->responsibilityFilter);
        }));
    }

    public function getChangeHistoryProperty(): Collection
    {
        $settingChanges = Setting::query()
            ->select(['id', 'group', 'key', 'updated_at'])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->map(fn (Setting $item): array => [
                'who' => 'Sistema',
                'what' => 'Ajuste '.$item->group.'.'.$item->key,
                'when' => $item->updated_at,
            ]);

        $tokenChanges = AuthorizedToken::query()
            ->select(['id', 'platform', 'label', 'updated_at'])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->map(fn (AuthorizedToken $item): array => [
                'who' => 'Sistema',
                'what' => 'Token '.$item->platform.' ('.$item->label.')',
                'when' => $item->updated_at,
            ]);

        return $settingChanges
            ->concat($tokenChanges)
            ->sortByDesc('when')
            ->take(20)
            ->values();
    }

    public function render(): View
    {
        return view('livewire.integration-manual', [
            'filteredSections' => $this->filteredSections,
            'filteredResponsibilities' => $this->filteredResponsibilities,
            'changeHistory' => $this->changeHistory,
        ])->layout('layouts.app', [
            'title' => 'Manual de integración',
            'breadcrumbs' => [
                ['label' => 'Manual'],
            ],
        ]);
    }
}
