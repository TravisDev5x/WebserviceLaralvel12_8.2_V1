<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class DiagnosticWizard extends Component
{
    public string $step = 'root';

    public string $errorDetail = '';

    public bool $resolved = false;

    public function go(string $nextStep): void
    {
        $this->step = $nextStep;
        $this->resolved = false;
    }

    public function markResolved(bool $value): void
    {
        $this->resolved = $value;
    }

    public function resetWizard(): void
    {
        $this->step = 'root';
        $this->errorDetail = '';
        $this->resolved = false;
    }

    public function render(): View
    {
        return view('livewire.diagnostic-wizard');
    }
}
