@blaze

@props([
    'bg' => 'bg-white',
    'padding' => null,
])
@php
    $classes = Flux::classes()
        ->add($bg)
        ->add($padding)
        ->add('rounded-lg shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] relative overflow-hidden')
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-card>
    {{ $slot }}
</div>