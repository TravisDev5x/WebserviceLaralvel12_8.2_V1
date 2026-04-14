@php
    $themeColorOptions = [
        ['id' => 'default', 'label' => 'Default', 'a' => '#262626', 'b' => '#d4d4d8', 'swatch' => 'rounded-full'],
        ['id' => 'claude', 'label' => 'Claude', 'a' => '#9a5b3f', 'b' => '#e8dcc8', 'swatch' => 'rounded-full'],
        ['id' => 'doom64', 'label' => 'Doom 64', 'a' => '#171717', 'b' => '#38bdf8', 'swatch' => 'rounded-none'],
        ['id' => 'supabase', 'label' => 'Supabase', 'a' => '#3ecf8e', 'b' => '#0d3d2e', 'swatch' => 'rounded-full'],
        ['id' => 'blue', 'label' => 'Blue', 'a' => '#2563eb', 'b' => '#dbeafe', 'swatch' => 'rounded-full'],
        ['id' => 'violet', 'label' => 'Violet', 'a' => '#7c3aed', 'b' => '#ede9fe', 'swatch' => 'rounded-full'],
    ];
@endphp
<div class="dropdown-menu" id="theme-color-dropdown">
    <button
        type="button"
        id="theme-color-trigger"
        aria-haspopup="menu"
        aria-controls="theme-color-menu"
        aria-expanded="false"
        class="btn-icon-ghost size-8"
        aria-label="Tema de color"
        data-tooltip="Tema de color"
        data-side="bottom"
    >
        <x-svg-lucide name="palette" class="size-5 shrink-0" aria-hidden="true" />
    </button>
    <div id="theme-color-popover" data-popover aria-hidden="true" class="min-w-52" data-side="bottom" data-align="end">
        <div role="menu" id="theme-color-menu" aria-labelledby="theme-color-trigger" class="flex flex-col gap-0.5 p-1">
            <div class="text-muted-foreground px-2 py-1 text-xs font-medium" role="presentation">Tema (color + forma)</div>
            @foreach ($themeColorOptions as $opt)
                @php($shape = $opt['swatch'] ?? 'rounded-full')
                <button
                    type="button"
                    role="menuitemradio"
                    class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent"
                    data-color-theme-id="{{ $opt['id'] }}"
                    aria-checked="false"
                >
                    <span class="inline-flex shrink-0 gap-0.5" aria-hidden="true">
                        <span class="size-3 border border-border {{ $shape }}" style="background: {{ $opt['a'] }}"></span>
                        <span class="size-3 border border-border {{ $shape }}" style="background: {{ $opt['b'] }}"></span>
                    </span>
                    <span>{{ $opt['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>
