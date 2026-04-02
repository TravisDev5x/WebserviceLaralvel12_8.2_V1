<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class GenerateInfraRequirementsPdf extends Command
{
    protected $signature = 'manual:infra-pdf';

    protected $description = 'Genera PDF breve de requisitos de infraestructura en public/docs/';

    public function handle(): int
    {
        $dir = public_path('docs');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.'/Requisitos-Infraestructura-Breve.pdf';

        $pdf = Pdf::loadView('docs.infra-requisitos-pdf', [
            'generatedAt' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
        ]);
        $pdf->setPaper('a4', 'portrait');
        $pdf->save($path);

        $this->info('PDF guardado en: '.$path);

        return self::SUCCESS;
    }
}
