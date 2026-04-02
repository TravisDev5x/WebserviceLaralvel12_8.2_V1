<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Manual de integración</h2>
            <p class="page-subtitle">Bitrix24, Botmaker y uso del panel (misma guía que la vista pública).</p>
        </div>
        <a class="btn" href="{{ route('manual.pdf') }}" target="_blank" rel="noopener">Descargar PDF</a>
    </div>
    <div class="card card-pad manual-doc">
        <p class="muted" style="margin-top: 0;">Vista pública sin sesión: <a href="{{ route('manual.public') }}" target="_blank" rel="noopener">{{ route('manual.public') }}</a></p>
        @include('docs.partials.manual-sections', ['appBaseUrl' => $appBaseUrl])
    </div>
</div>
