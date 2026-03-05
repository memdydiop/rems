<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Lease;

new
    #[Layout('layouts.app', ['title' => 'Rapport Taux d\'Occupation'])]
    class extends Component {

    #[Url]
    public string $propertyId = '';

    #[Computed]
    public function properties()
    {
        return Property::orderBy('name')->get();
    }

    #[Computed]
    public function occupancyData()
    {
        $query = Unit::query()->with(['property', 'leases']);

        if ($this->propertyId) {
            $query->where('property_id', $this->propertyId);
        }

        $units = $query->get();

        $totalUnits = $units->count();
        $occupiedUnits = $units->filter(
            fn($unit) =>
            $unit->leases->where('status', 'active')->isNotEmpty()
        )->count();
        $vacantUnits = $totalUnits - $occupiedUnits;

        $occupancyRate = $totalUnits > 0
            ? round(($occupiedUnits / $totalUnits) * 100, 1)
            : 0;

        return [
            'totalUnits' => $totalUnits,
            'occupiedUnits' => $occupiedUnits,
            'vacantUnits' => $vacantUnits,
            'occupancyRate' => $occupancyRate,
        ];
    }

    #[Computed]
    public function propertyOccupancy()
    {
        return Property::withCount([
            'units',
            'units as occupied_units_count' => function ($query) {
                $query->whereHas('leases', fn($q) => $q->where('status', 'active'));
            }
        ])->get()->map(fn($property) => [
                'id' => $property->id,
                'name' => $property->name,
                'total' => $property->units_count,
                'occupied' => $property->occupied_units_count,
                'vacant' => $property->units_count - $property->occupied_units_count,
                'rate' => $property->units_count > 0
                    ? round(($property->occupied_units_count / $property->units_count) * 100, 1)
                    : 0,
            ]);
    }

    #[Computed]
    public function vacantUnits()
    {
        return Unit::with('property')
            ->whereDoesntHave('leases', fn($q) => $q->where('status', 'active'))
            ->get();
    }
};
?>

<div>
    <x-layouts::content heading="Rapport Taux d'Occupation" subheading="Analyse de l'occupation des unités.">

        <x-slot name="actions">
            <flux:select wire:model.live="propertyId" placeholder="Toutes les propriétés" class="w-48">
                <flux:select.option value="">Toutes</flux:select.option>
                @foreach($this->properties as $property)
                    <flux:select.option value="{{ $property->id }}">{{ $property->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button icon="printer" variant="ghost" onclick="window.print()">
                Imprimer
            </flux:button>
        </x-slot>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <x-flux::card class="bg-blue-50 border-blue-200">
                <div class="p-4 text-center">
                    <p class="text-3xl font-bold text-blue-700">{{ $this->occupancyData['totalUnits'] }}</p>
                    <p class="text-sm text-blue-600">Total Unités</p>
                </div>
            </x-flux::card>

            <x-flux::card class="bg-emerald-50 border-emerald-200">
                <div class="p-4 text-center">
                    <p class="text-3xl font-bold text-emerald-700">{{ $this->occupancyData['occupiedUnits'] }}</p>
                    <p class="text-sm text-emerald-600">Occupées</p>
                </div>
            </x-flux::card>

            <x-flux::card class="bg-amber-50 border-amber-200">
                <div class="p-4 text-center">
                    <p class="text-3xl font-bold text-amber-700">{{ $this->occupancyData['vacantUnits'] }}</p>
                    <p class="text-sm text-amber-600">Vacantes</p>
                </div>
            </x-flux::card>

            <x-flux::card class="bg-violet-50 border-violet-200">
                <div class="p-4 text-center">
                    <p class="text-3xl font-bold text-violet-700">{{ $this->occupancyData['occupancyRate'] }}%</p>
                    <p class="text-sm text-violet-600">Taux d'Occupation</p>
                </div>
            </x-flux::card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Occupancy Chart -->
            <x-flux::card>
                <x-flux::card.header title="Répartition" subtitle="Répartition des unités par statut." />
                <div class="p-4 h-64" x-data="{
                    init() {
                        const options = {
                            series: [{{ $this->occupancyData['occupiedUnits'] }}, {{ $this->occupancyData['vacantUnits'] }}],
                            chart: { type: 'donut', height: 220 },
                            labels: ['Occupées', 'Vacantes'],
                            colors: ['#10b981', '#f59e0b'],
                            legend: { position: 'bottom' },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        labels: {
                                            show: true,
                                            total: {
                                                show: true,
                                                label: 'Taux',
                                                formatter: () => '{{ $this->occupancyData['occupancyRate'] }}%'
                                            }
                                        }
                                    }
                                }
                            }
                        };
                        new ApexCharts(this.$el, options).render();
                    }
                }"></div>
            </x-flux::card>

            <!-- By Property Chart -->
            <x-flux::card>
                <x-flux::card.header title="Par Propriété" subtitle="Répartition des unités par propriété." />
                <div class="p-4 h-64" x-data="{
                    init() {
                        const options = {
                            series: [{
                                name: 'Occupées',
                                data: {{ Js::from($this->propertyOccupancy->pluck('occupied')->toArray()) }}
                            }, {
                                name: 'Vacantes',
                                data: {{ Js::from($this->propertyOccupancy->pluck('vacant')->toArray()) }}
                            }],
                            chart: { type: 'bar', height: 220, stacked: true, toolbar: { show: false } },
                            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                            xaxis: { 
                                categories: {{ Js::from($this->propertyOccupancy->pluck('name')->toArray()) }},
                            },
                            colors: ['#10b981', '#f59e0b'],
                            legend: { position: 'bottom' },
                            grid: { borderColor: '#f4f4f5' }
                        };
                        new ApexCharts(this.$el, options).render();
                    }
                }"></div>
            </x-flux::card>
        </div>

        <!-- Vacant Units Table -->
        <x-flux::card>
            <x-flux::card.header title="Unités Vacantes" subtitle="Liste des unités vacantes." />
            <x-flux::table search linesPerPage>
                <x-flux::table.columns>
                    <x-flux::table.column>Propriété</x-flux::table.column>
                    <x-flux::table.column>Unité</x-flux::table.column>
                    <x-flux::table.column>Loyer</x-flux::table.column>
                    <x-flux::table.column>Type</x-flux::table.column>
                </x-flux::table.columns>
                <x-flux::table.rows>
                    @forelse($this->vacantUnits as $unit)
                        <x-flux::table.row>
                            <x-flux::table.cell class="font-medium">{{ $unit->property->name }}</x-flux::table.cell>
                            <x-flux::table.cell>{{ $unit->name }}</x-flux::table.cell>
                            <x-flux::table.cell class="text-emerald-600 font-semibold">
                                {{ \Illuminate\Support\Number::currency($unit->rent_amount ?? 0, 'XOF') }}
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:badge size="sm" color="zinc">{{ $unit->type ?? 'Standard' }}</flux:badge>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @empty
                        <x-flux::table.row>
                            <x-flux::table.cell colspan="4" class="text-center text-zinc-400 py-8">
                                🎉 Toutes les unités sont occupées !
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>

    </x-layouts::content>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>