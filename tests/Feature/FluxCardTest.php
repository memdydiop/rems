<?php

use Illuminate\Support\Facades\Blade;

test('flux card component renders with sub-components', function () {
    $view = Blade::render(<<<'BLADE'
<x-flux::card>
    <x-flux::card.header>
        <x-flux::card.title>Header Title</x-flux::card.title>
    </x-flux::card.header>
    <x-flux::card.body>
        Body Content
    </x-flux::card.body>
</x-flux::card>
BLADE);

    expect($view)
        ->toContain('Header Title')
        ->toContain('Body Content')
        ->toContain('data-flux-card')
        ->toContain('data-flux-card-header')
        ->toContain('data-flux-card-body')
        ->toContain('data-flux-heading');
});
