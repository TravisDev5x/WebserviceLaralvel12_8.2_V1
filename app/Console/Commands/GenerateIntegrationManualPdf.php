<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class GenerateIntegrationManualPdf extends Command
{
    protected $signature = 'manual:pdf';

    protected $description = 'Genera el PDF del manual en public/docs/Manual-Integracion-Bitrix24-Botmaker.pdf';

    public function handle(): int
    {
        $dir = public_path('docs');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.'/Manual-Integracion-Bitrix24-Botmaker.pdf';

        $pdf = Pdf::loadView('docs.integration-manual-pdf', [
            'appBaseUrl' => rtrim((string) config('app.url'), '/'),
            'generatedAt' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
        ]);
        $pdf->setPaper('a4', 'portrait');
        $pdf->save($path);

        $this->info('PDF guardado en: '.$path);

        return self::SUCCESS;
    }
}
