<?php

use Illuminate\Support\Facades\Blade;

test('flux table component renders', function () {
    $view = Blade::render(<<<'BLADE'
<x-flux::table>
    <x-flux::table.columns>
        <x-flux::table.column>Name</x-flux::table.column>
    </x-flux::table.columns>
    <x-flux::table.rows>
        <x-flux::table.row>
            <x-flux::table.cell>John Doe</x-flux::table.cell>
        </x-flux::table.row>
    </x-flux::table.rows>
</x-flux::table>
BLADE);

    expect($view)
        ->toContain('Name')
        ->toContain('John Doe')
        ->toContain('data-flux-table');
});
