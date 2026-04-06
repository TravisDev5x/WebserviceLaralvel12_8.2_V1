@props([
    'items' => [],
])
@if(count($items) > 0)
    <nav class="app-breadcrumb" aria-label="Migas de pan">
        <ol class="app-breadcrumb-list">
            @foreach($items as $index => $item)
                @if($index > 0)
                    <li class="app-breadcrumb-sep" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="app-breadcrumb-chevron"><path d="m9 18 6-6-6-6"/></svg>
                    </li>
                @endif
                <li class="app-breadcrumb-item">
                    @if(!empty($item['url']))
                        <a href="{{ $item['url'] }}" class="app-breadcrumb-link">{{ $item['label'] }}</a>
                    @else
                        <span class="app-breadcrumb-current">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
