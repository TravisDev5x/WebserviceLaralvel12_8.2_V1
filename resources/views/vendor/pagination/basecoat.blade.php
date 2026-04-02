@if ($paginator->hasPages())
    <nav role="navigation" aria-label="pagination" class="mx-auto flex w-full justify-center" style="margin-top:.5rem;">
        <ul class="flex flex-row items-center gap-1" style="display:flex; flex-wrap:wrap; gap:.35rem; list-style:none; padding:0; margin:0;">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="btn btn-ghost" style="opacity:.6; pointer-events:none;">Anterior</span>
                @else
                    <button class="btn btn-ghost" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="prev" type="button">Anterior</button>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="btn btn-ghost" style="opacity:.7; pointer-events:none;">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="btn btn-outline" aria-current="page">{{ $page }}</span>
                            @else
                                <button class="btn btn-ghost" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" type="button">{{ $page }}</button>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

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
