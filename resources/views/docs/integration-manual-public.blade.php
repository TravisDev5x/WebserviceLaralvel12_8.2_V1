@extends('layouts.docs-public', ['title' => 'Manual de integración Bitrix24 y Botmaker', 'staticPdfUrl' => $staticPdfUrl ?? null])

@section('content')
    <p class="muted" style="margin-top: 0;">Guía detallada para equipos técnicos y operaciones. Incluye URLs, cabeceras, cuerpos JSON mínimos y variables de entorno.</p>
    @include('docs.partials.manual-sections', ['appBaseUrl' => $appBaseUrl])
@endsection
