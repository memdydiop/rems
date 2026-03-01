@props([
    'level' => 6,
    'size' => 'sm',
])

@php
    $classes = Flux::classes()
        ->add('font-semibold text-zinc-900')
        ->add('text-sm')
        ->add('leading-tight');
@endphp

<flux:heading {{ $attributes->class($classes) }} level="{{ $level }}" size="{{ $size }}">
    {{ $slot }}
</flux:heading>