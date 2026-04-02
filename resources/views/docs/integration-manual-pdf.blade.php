<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; line-height: 1.45; }
        h1 { font-size: 16pt; margin: 0 0 12pt; border-bottom: 1px solid #ccc; padding-bottom: 6pt; }
        .meta { font-size: 9pt; color: #555; margin-bottom: 14pt; }
        .manual-block { margin-bottom: 14pt; page-break-inside: avoid; }
        h2 { font-size: 12pt; margin: 0 0 6pt; color: #1a1a1a; }
        h3 { font-size: 10pt; margin: 10pt 0 4pt; color: #444; }
        p, li { margin: 0 0 5pt; }
        ul, ol { margin: 4pt 0 6pt; padding-left: 16pt; }
        code { font-family: DejaVu Sans Mono, monospace; font-size: 8.5pt; background: #f0f0f0; padding: 1pt 3pt; }
        table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin: 6pt 0; }
        th, td { border: 1px solid #bbb; padding: 4pt 5pt; text-align: left; vertical-align: top; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h1>Manual de integración — Bitrix24 y Botmaker</h1>
    <p class="meta">Middleware Laravel · Generado: {{ $generatedAt }} · URL base de referencia: {{ $appBaseUrl }}</p>
    @include('docs.partials.manual-sections', ['appBaseUrl' => $appBaseUrl])
</body>
</html>
