<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class RetrySettings extends Component
{
    public string $retryMaxAttempts = '5';

    public string $retryBackoffSchedule = '30,60,300,900,3600';

    public string $retryHttpTimeout = '15';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->retryMaxAttempts = (string) config_dynamic('retry.max_attempts', 5);
        $this->retryBackoffSchedule = implode(',', (array) config_dynamic('retry.backoff_schedule', [30, 60, 300, 900, 3600]));
        $this->retryHttpTimeout = (string) config_dynamic('retry.http_timeout', 15);
    }

    public function save(): void
    {
        $this->reset('successMessage', 'errorMessage');

        $validated = $this->validate([
            'retryMaxAttempts' => ['required', 'integer', 'min:1', 'max:10'],
            'retryBackoffSchedule' => ['required', 'string'],
            'retryHttpTimeout' => ['required', 'integer', 'min:5', 'max:120'],
        ], [], [
            'retryMaxAttempts' => 'Máximo de intentos',
            'retryBackoffSchedule' => 'Backoff',
            'retryHttpTimeout' => 'Timeout HTTP',
        ]);

        try {
            $backoff = array_values(array_filter(array_map(
                static fn (string $value): int => (int) trim($value),
                explode(',', (string) $validated['retryBackoffSchedule']),
            ), static fn (int $value): bool => $value > 0));
            if ($backoff === []) {
                throw new \RuntimeException('Backoff inválido: usa números separados por coma.');
            }

            Setting::set('retry.max_attempts', (int) $validated['retryMaxAttempts'], 'integer');
            Setting::set('retry.backoff_schedule', $backoff, 'json');
            Setting::set('retry.http_timeout', (int) $validated['retryHttpTimeout'], 'integer');

            $this->retryBackoffSchedule = implode(',', $backoff);
            $this->successMessage = 'Parámetros de reintentos guardados.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.retry-settings')
            ->layout('layouts.app', ['title' => 'Reintentos y rendimiento']);
    }
}
