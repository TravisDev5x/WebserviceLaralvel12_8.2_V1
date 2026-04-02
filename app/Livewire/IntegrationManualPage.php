<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class IntegrationManualPage extends Component
{
    public function render(): View
    {
        return view('livewire.integration-manual-page', [
            'appBaseUrl' => rtrim((string) config('app.url'), '/'),
        ])->layout('layouts.app', [
            'title' => 'Manual de integración',
        ]);
    }
}
