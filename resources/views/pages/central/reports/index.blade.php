<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\Tenant;

new
    #[Layout('layouts.app', ['title' => 'Rapports Plateforme'])]
    class extends Component {

    #[Computed]
    public function platformStats()
    {
        return [
            'totalTenants' => Tenant::count(),
            'totalDomains' => \Stancl\Tenancy\Database\Models\Domain::count(),
            'tenantsThisMonth' => Tenant::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    #[Computed]
    public function tenantStats()
    {
        // Return basic tenant info without accessing tenant databases
        return Tenant::with('domains')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($tenant) => [
                'id' => $tenant->id,
                'company' => $tenant->company,
                'domains' => $tenant->domains->pluck('domain')->join(', '),
                'created_at' => $tenant->created_at,
            ]);
    }

    #[Computed]
    public function chartData()
    {
        // Growth data - tenants per month for last 6 months
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = Tenant::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            $months->push([
                'month' => $date->translatedFormat('M'),
                'count' => $count,
            ]);
        }

        return [
            'labels' => $months->pluck('month')->toArray(),
            'data' => $months->pluck('count')->toArray(),
        ];
    }
};
?>

<div>
    <x-layouts::content heading="Rapports Plateforme" subheading="Statistiques globales et performances des tenants.">

        <x-slot name="actions">
            <flux:button icon="printer" variant="ghost" onclick="window.print()">
                Imprimer
            </flux:button>
        </x-slot>

        <!-- Platform Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <x-flux::card class="flex items-center gap-4 !p-6">
                <div class="flex items-center justify-center size-12 rounded-xl bg-blue-50 text-blue-600">
                    <flux:icon.building-office-2 class="size-6" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-zinc-400 uppercase tracking-wider">Total
                        Organisations</span>
                    <span class="text-3xl font-bold text-zinc-900">{{ $this->platformStats['totalTenants'] }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-4 !p-6">
                <div class="flex items-center justify-center size-12 rounded-xl bg-emerald-50 text-emerald-600">
                    <flux:icon.chart-bar class="size-6" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-zinc-400 uppercase tracking-wider">Nouveaux ce Mois</span>
                    <span class="text-3xl font-bold text-zinc-900">{{ $this->platformStats['tenantsThisMonth'] }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-4 !p-6">
                <div class="flex items-center justify-center size-12 rounded-xl bg-violet-50 text-violet-600">
                    <flux:icon.globe-alt class="size-6" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-zinc-400 uppercase tracking-wider">Domaines Actifs</span>
                    <span class="text-3xl font-bold text-zinc-900">{{ $this->platformStats['totalDomains'] }}</span>
                </div>
            </x-flux::card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Growth Chart -->
            <x-flux::card class="lg:col-span-2 !p-0 overflow-hidden">
                <div class="p-4 border-b border-zinc-100 bg-white">
                    <h3 class="font-semibold text-zinc-900">Croissance des Tenants</h3>
                </div>
                <div class="p-4 h-64" x-data="{
                    init() {
                        const options = {
                            series: [{ name: 'Nouveaux Tenants', data: {{ json_encode($this->chartData['data']) }} }],
                            chart: { 
                                type: 'bar', 
                                height: 220, 
                                toolbar: { show: false },
                                fontFamily: 'Inter, sans-serif',
                            },
                            plotOptions: { 
                                bar: { 
                                    borderRadius: 4, 
                                    columnWidth: '40%',
                                    colors: {
                                        ranges: [{
                                            from: 0,
                                            to: 1000,
                                            color: '#3b82f6' // Blue-500
                                        }]
                                    }
                                } 
                            },
                            dataLabels: { enabled: false },
                            xaxis: { 
                                categories: {{ Js::from($this->chartData['labels']) }},
                                labels: { style: { colors: '#71717a', fontSize: '12px' } },
                                axisBorder: { show: false },
                                axisTicks: { show: false }
                            },
                            yaxis: { 
                                labels: { style: { colors: '#71717a', fontSize: '12px' } } 
                            },
                            grid: { 
                                borderColor: '#f4f4f5',
                                strokeDashArray: 4,
                            },
                            tooltip: {
                                theme: 'light',
                                style: { fontSize: '12px' }
                            }
                        };
                        new ApexCharts(this.$el, options).render();
                    }
                }"></div>
            </x-flux::card>

            <!-- Tenant Summary -->
            <x-flux::card class="flex flex-col items-center justify-center text-center p-6">
                <div class="flex items-center justify-center size-16 rounded-full bg-blue-50 text-blue-600 mb-4">
                    <flux:icon.building-office class="size-8" />
                </div>
                <p class="text-4xl font-extrabold text-zinc-900">{{ $this->tenantStats->count() }}</p>
                <p class="text-sm font-medium text-zinc-500 uppercase tracking-widest mt-1">Organisations Actives</p>

                <div class="mt-6 flex flex-col gap-2 w-full">
                    <flux:button variant="primary" :href="route('central.tenants.index')" wire:navigate class="w-full">
                        Gérer les Clients
                    </flux:button>
                </div>
            </x-flux::card>
        </div>

        <!-- Tenant Details Table -->
        <x-flux::card class="!p-0 overflow-hidden">
            <div class="p-4 border-b border-zinc-100 bg-white">
                <h3 class="font-semibold text-zinc-900">Dernières Inscriptions</h3>
            </div>
            <x-flux::table>
                <x-flux::table.columns>
                    <x-flux::table.column>Organisation</x-flux::table.column>
                    <x-flux::table.column>Domaines</x-flux::table.column>
                    <x-flux::table.column>Inscrit le</x-flux::table.column>
                    <x-flux::table.column></x-flux::table.column>
                </x-flux::table.columns>
                <x-flux::table.rows>
                    @forelse($this->tenantStats->take(5) as $tenant)
                        <x-flux::table.row>
                            <x-flux::table.cell>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="size-8 rounded bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-xs">
                                        {{ substr($tenant['company'], 0, 1) }}
                                    </div>
                                    <span class="font-medium text-zinc-900">{{ $tenant['company'] }}</span>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:badge size="sm" color="zinc">{{ $tenant['domains'] ?: 'Aucun' }}</flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell>{{ $tenant['created_at']->format('d/m/Y') }}</x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:button size="xs" variant="ghost" icon="eye"
                                    :href="route('central.tenants.show', $tenant['id'])" wire:navigate />
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @empty
                        <x-flux::table.row>
                            <x-flux::table.cell colspan="4" class="text-center text-zinc-400 py-8">
                                Aucun tenant trouvé
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>

    </x-layouts::content>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>