@blaze

@php
$classes = Flux::classes([
    'flex items-center px-4 text-sm whitespace-nowrap',
    'text-zinc-800 
    'bg-zinc-800/5 
    'border-zinc-200 
    'rounded-e-lg',
    'border-e border-t border-b shadow-xs',
]);
@endphp

<div {{ $attributes->class($classes) }} data-flux-input-group-suffix>
    {{ $slot }}
</div>
