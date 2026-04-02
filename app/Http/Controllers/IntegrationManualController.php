<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class IntegrationManualController extends Controller
{
    public function show(): View
    {
        $staticPdf = public_path('docs/Manual-Integracion-Bitrix24-Botmaker.pdf');

        return view('docs.integration-manual-public', [
            'appBaseUrl' => $this->baseUrl(),
            'staticPdfUrl' => is_file($staticPdf) ? asset('docs/Manual-Integracion-Bitrix24-Botmaker.pdf') : null,
        ]);
    }

    public function downloadPdf(): Response
    {
        $pdf = Pdf::loadView('docs.integration-manual-pdf', [
            'appBaseUrl' => $this->baseUrl(),
            'generatedAt' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Manual-Integracion-Bitrix24-Botmaker.pdf');
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }
}
