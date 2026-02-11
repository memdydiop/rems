@blaze

@php
$classes = Flux::classes([
    'flex items-center px-4 text-sm whitespace-nowrap',
    'text-zinc-800 
    'bg-zinc-800/5 
    'border-zinc-200 
    'border border-x-zinc-100 shadow-xs',
]);
@endphp

<div {{ $attributes->class($classes) }} data-flux-input-group-label>
    {{ $slot }}
</div>
