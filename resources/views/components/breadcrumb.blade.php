@props([
    'items' => [],
])
@if(count($items) > 0)
    <nav class="app-breadcrumb" aria-label="Migas de pan">
        <ol class="app-breadcrumb-list">
            @foreach($items as $index => $item)
                @if($index > 0)
                    <li class="app-breadcrumb-sep" aria-hidden="true">
                        <x-svg-lucide name="chevron-right" class="app-breadcrumb-chevron" />
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
