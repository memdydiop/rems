@props([
    'heading' => null,
    'subheading' => null,
    'actions' => null,
])

<div class="flex flex-col gap-6">

    <div class="flex items-center justify-between">
        <div class="flex flex-col leading-none">
            <flux:heading level="1" size="md">{{ $heading ?? null }}</flux:heading>
            @if ($subheading)
                <flux:subheading>{{ $subheading ?? null }}</flux:subheading>
            @endif
        </div>
        @if ($actions)
            <div class="flex items-center gap-2">
                {{ $actions ?? null }}
            </div>
        @endif
    </div>

    {{ $slot }}

</div>  