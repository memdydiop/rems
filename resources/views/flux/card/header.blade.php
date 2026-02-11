@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'cardActions' => null,
])

@php
$classes = Flux::classes()
    ->add('px-4 pt-4 pb-2.5 flex justify-between items-center')
    ->add('bg-zinc-50/50 border-b border-zinc-200');
@endphp

<div {{ $attributes->class($classes) }} data-flux-card-header>
    @if ($icon || $title || $subtitle)
        <div class="flex items-center gap-3">
            @if ($icon)
                <x-flux::card.icon :name="$icon" variant="mini" />
            @endif

            @if ($title || $subtitle)
                <div>
                    @if ($title)
                        <x-flux::card.title>{{ $title }}</x-flux::card.title>
                    @endif
                    @if ($subtitle)
                        <x-flux::card.subtitle>{{ $subtitle }}</x-flux::card.subtitle>
                    @endif
                </div>
            @endif
        </div>
    @endif
    
    {{ $cardActions }}

</div>
