@blaze

@props([
    'size' => 'base',
])

@php
$classes = Flux::classes()
    ->add(match ($size) {
        'xl' => 'text-xl',
        'lg' => 'text-lg',
        'md' => 'text-md',
        'sm' => 'text-sm',
        default => 'text-base',
        'xs' => 'text-xs',
    })
    ->add('[:where(&)]:text-muted')
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-subheading>
    {{ $slot }}
</div>
