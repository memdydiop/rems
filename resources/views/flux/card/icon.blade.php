@props(['name', 'variant' => 'outline'])

@php
    $classes = Flux::classes('bg-indigo-50 p-2 rounded-lg text-indigo-600');
@endphp

<div {{ $attributes->class($classes) }}>
    <flux:icon :name="$name" :variant="$variant" class="w-5 h-5" />
</div>