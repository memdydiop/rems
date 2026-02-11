@props([
    'padding' => 'px-4 py-2',
    'bg' => 'bg-zinc-50/50',
    'border' => 'border-b border-zinc-200',
    'search' => false,
    'selectable' => null,
    'linesPerPage' => false,
])
@php
    $classes = Flux::classes()
        ->add($padding)
        ->add($bg)
        ->add($border)
        ->add('flex flex-col md:flex-row gap-4 justify-between items-center')
    ;
@endphp

<div {{ $attributes->class($classes) }}>
        @if ($search || $selectable)
            <div class="flex items-center gap-2 w-full md:w-auto">
                @if ($search)
                    <flux:input 
                        wire:model.live.debounce.300ms="search" 
                        icon="magnifying-glass" 
                        size="sm"
                        placeholder="Rechercher..." 
                        class="w-full md:w-64" />
                @endif
                
                @if ($selectable)
                    {{ $selectable }}
                @endif
            </div>
        @endif

        @if ($linesPerPage)
            <div class="flex items-center gap-2">
                <span class="text-xs text-zinc-500 hidden md:block">Afficher par</span>
                <flux:select wire:model.live="perPage" size="sm" class="w-20">
                    <flux:select.option value="5">5</flux:select.option>
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                </flux:select>
            </div>
        @endif
</div>
    