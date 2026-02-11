@props([
    'title' => '',
    'icon' => null,
    'actions' => null,
])

<x-flux::card {{ $attributes }}>
    @if($title || $icon || $actions)
        <x-flux::card.header>
            <div class="flex items-center gap-2">
                @if($icon)
                    <flux:icon :name="$icon" class="size-5 text-zinc-400" />
                @endif
                <x-flux::card.title>{{ $title }}</x-flux::card.title>
            </div>
            
            @if($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </x-flux::card.header>
    @endif
    
    <x-flux::card.body>
        {{ $slot }}
    </x-flux::card.body>
</x-flux::card>
