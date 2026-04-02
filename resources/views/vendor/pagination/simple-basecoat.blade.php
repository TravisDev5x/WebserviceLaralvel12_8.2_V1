@if ($paginator->hasPages())
    <nav role="navigation" aria-label="pagination" class="mx-auto flex w-full justify-center" style="margin-top:.5rem;">
        <ul class="flex flex-row items-center gap-1" style="display:flex; gap:.35rem; list-style:none; padding:0; margin:0;">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="btn btn-ghost" style="opacity:.6; pointer-events:none;">Anterior</span>
                @else
                    <button class="btn btn-ghost" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="prev" type="button">Anterior</button>
                @endif
            </li>
            <li>
                @if ($paginator->hasMorePages())
                    <button class="btn btn-ghost" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="next" type="button">Siguiente</button>
                @else
                    <span class="btn btn-ghost" style="opacity:.6; pointer-events:none;">Siguiente</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
