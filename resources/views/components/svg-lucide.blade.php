@props([
    'name',
])

@php
    $slug = strtolower((string) preg_replace('/[^a-z0-9-]/', '', (string) $name));
    $path = resource_path('svg-lucide/'.$slug.'.svg');
    $contents = is_file($path) ? file_get_contents($path) : null;
@endphp

@if ($contents !== null && $contents !== '')
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center text-current [&>svg]:block [&>svg]:h-full [&>svg]:w-full']) }}>
        {!! $contents !!}
    </span>
@endif
