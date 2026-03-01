<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;

new
    #[Layout('layouts.app', ['title' => 'Performance des Propriétés'])]
    class extends Component {

    #[Computed]
    public function properties()
    {
        return \App\Models\Property::with(['units.leases.payments', 'units'])
            ->get()
            ->map(function ($property) {
                $units = $property->units;
                $totalUnits = $units->count();
                $occupiedUnits = $units->where('status', 'occupied')->count();
                $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

                // Monthly revenue from active leases
                $monthlyRevenue = $units->flatMap->leases
                    ->where('status', 'active')
                    ->sum('rent_amount');

                // Collected this month
                $thisMonth = now()->format('Y-m');
                $collected = $units->flatMap->leases
                    ->flatMap->payments
                    ->filter(fn($p) => $p->paid_at?->format('Y-m') === $thisMonth && $p->status === 'completed')
                    ->sum('amount');

                return [
                    'id' => $property->id,
                    'name' => $property->name,
                    'address' => $property->address,
                    'totalUnits' => $totalUnits,
                    'occupiedUnits' => $occupiedUnits,
                    'occupancyRate' => $occupancyRate,
                    'monthlyRevenue' => $monthlyRevenue,
                    'collected' => $collected,
                ];
            });
    }

    #[Computed]
    public function chartData()
    {
        return [
            'names' => $this->properties->pluck('name')->toArray(),
            'revenue' => $this->properties->pluck('monthlyRevenue')->toArray(),
            'occupancy' => $this->properties->pluck('occupancyRate')->toArray(),
        ];
    }
};
?>

<div>
    <x-layouts::content heading="Performance des Propriétés" subheading="Comparez les performances de vos propriétés.">

        <x-slot name="actions">
            <flux:button icon="printer" variant="ghost" onclick="window.print()">
                Imprimer
            </flux:button>
            <flux:button icon="arrow-left" variant="ghost" href="{{ route('tenant.reports.index') }}">
                Retour
            </flux:button>
        </x-slot>

        <!-- Comparison Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <x-flux::card>
                <x-flux::card.header title="Revenus par Propriété" />
                <div class="p-4 h-80" x-data="{
                    init() {
                        const options = {
                            series: [{ name: 'Revenu Mensuel', data: {{ json_encode($this->chartData['revenue']) }} }],
                            chart: { type: 'bar', height: 300, toolbar: { show: false } },
                            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                            dataLabels: { enabled: false },
                            xaxis: { 
                                labels: { 
                                    formatter: (val) => '$' + val,
                                    style: { colors: '#71717a' }
                                }
                            },
                            yaxis: { 
                                categories: {{ Js::from($this->chartData['names']) }},
                                labels: { style: { colors: '#71717a' } }
                            },
                            colors: ['#2563eb'],
                            grid: { borderColor: '#f4f4f5' }
                        };
                        new ApexCharts(this.$el, options).render();
                    }
                }"></div>
            </x-flux::card>

            <x-flux::card>
                <x-flux::card.header title="Taux d'Occupation" />
                <div class="p-4 h-80" x-data="{
                    init() {
                        const options = {
                            series: {{ json_encode($this->chartData['occupancy']) }},
                            chart: { type: 'donut', height: 300 },
                            labels: {{ Js::from($this->chartData['names']) }},
                            colors: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
                            legend: { position: 'bottom' },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        labels: {
                                            show: true,
                                            total: { show: true, label: 'Moyenne', formatter: () => '{{ round($this->properties->avg('occupancyRate')) }}%' }
                                        }
                                    }
                                }
                            }
                        };
                        new ApexCharts(this.$el, options).render();
                    }
                }"></div>
            </x-flux::card>
        </div>

        <!-- Properties Table -->
        <x-flux::card>
            <x-flux::card.header title="Détail par Propriété" />
            <x-flux::table>
                <x-flux::table.columns>
                    <x-flux::table.column>Propriété</x-flux::table.column>
                    <x-flux::table.column>Unités</x-flux::table.column>
                    <x-flux::table.column>Occupation</x-flux::table.column>
                    <x-flux::table.column>Revenu Mensuel</x-flux::table.column>
                    <x-flux::table.column>Collecté (ce mois)</x-flux::table.column>
                </x-flux::table.columns>
                <x-flux::table.rows>
                    @forelse($this->properties as $property)
                        <x-flux::table.row>
                            <x-flux::table.cell>
                                <div>
                                    <p class="font-medium text-zinc-900">{{ $property['name'] }}</p>
                                    <p class="text-xs text-zinc-500">{{ $property['address'] }}</p>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                {{ $property['occupiedUnits'] }} / {{ $property['totalUnits'] }}
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-zinc-100 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $property['occupancyRate'] >= 80 ? 'bg-emerald-500' : ($property['occupancyRate'] >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                            style="width: {{ $property['occupancyRate'] }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium">{{ $property['occupancyRate'] }}%</span>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell class="font-semibold">
                                {{ \Illuminate\Support\Number::currency($property['monthlyRevenue'], 'USD') }}
                            </x-flux::table.cell>
                            <x-flux::table.cell class="text-emerald-600 font-semibold">
                                {{ \Illuminate\Support\Number::currency($property['collected'], 'USD') }}
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @empty
                        <x-flux::table.row>
                            <x-flux::table.cell colspan="5" class="text-center text-zinc-400 py-8">
                                Aucune propriété trouvée
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>

    </x-layouts::content>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>