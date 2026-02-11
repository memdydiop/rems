@blaze

@php
$classes = Flux::classes()
    ->add('p-4')
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-card-body>
    {{ $slot }}
</div>
