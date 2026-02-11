<?php

use App\Models\Owner;
use App\Models\Property;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.owner')] class extends Component {
    public function with()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $owner = Owner::where('email', $user->email)->orWhere('user_id', $user->id)->firstOrFail();

        $properties = $owner->properties()->with(['units.leases' => fn($q) => $q->where('status', 'active')])->get();

        $totalProperties = $properties->count();
        $totalUnits = $properties->sum(fn($p) => $p->units->count());
        $occupiedUnits = $properties->sum(fn($p) => $p->units->filter(fn($u) => $u->leases->isNotEmpty())->count());
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

        $monthlyRevenue = $properties->sum(
            fn($p) =>
            $p->units->sum(
                fn($u) =>
                $u->leases->where('status', 'active')->sum('rent_amount')
            )
        );

        return [
            'owner' => $owner,
            'properties' => $properties,
            'stats' => [
                'total_properties' => $totalProperties,
                'occupancy_rate' => $occupancyRate,
                'monthly_revenue' => $monthlyRevenue,
            ]
        ];
    }
};
?>

<div class="space-y-8">
    <!-- Welcome Header -->
    <div
        class="relative overflow-hidden rounded-xl bg-linear-to-r from-indigo-600 to-violet-600 p-8 text-white shadow-lg">
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <h1 class="text-3xl font-bold">Bonjour, {{ $owner->first_name }} 👋</h1>
                <p class="text-indigo-100 mt-2 text-lg">Votre portefeuille immobilier à portée de main.</p>
            </div>
            <div>
                <flux:button variant="filled" class="bg-white/10 hover:bg-white/20 text-white border-0"
                    icon="document-arrow-down"
                    href="{{ route('owner.report', ['year' => now()->year, 'month' => now()->month]) }}"
                    target="_blank">
                    Rapport {{ now()->locale('fr')->monthName }}
                </flux:button>
            </div>
        </div>

        <!-- Decorative Circle -->
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-48 h-48 rounded-full bg-black/10 blur-3xl"></div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
            class="bg-white rounded-xl p-6 shadow-sm border border-zinc-100 relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                <flux:icon.home class="size-24 text-blue-600" />
            </div>
            <p class="text-sm font-medium text-zinc-500 mb-1">Biens en gestion</p>
            <p class="text-3xl font-bold text-zinc-900">{{ $stats['total_properties'] }}</p>
            <div
                class="mt-4 flex items-center gap-2 text-xs font-medium text-blue-600 bg-blue-50 w-fit px-2 py-1 rounded-full">
                <flux:icon.building-office class="size-3" />
                <span>Actifs</span>
            </div>
        </div>

        <div
            class="bg-white rounded-xl p-6 shadow-sm border border-zinc-100 relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                <flux:icon.banknotes class="size-24 text-green-600" />
            </div>
            <p class="text-sm font-medium text-zinc-500 mb-1">Revenus Mensuels (Est.)</p>
            <p class="text-3xl font-bold text-zinc-900">{{ number_format($stats['monthly_revenue'], 0, ',', ' ') }}
                <span class="text-lg text-zinc-400 font-normal">FCFA</span>
            </p>
            <div
                class="mt-4 flex items-center gap-2 text-xs font-medium text-green-600 bg-green-50 w-fit px-2 py-1 rounded-full">
                <flux:icon.arrow-trending-up class="size-3" />
                <span>Ce mois</span>
            </div>
        </div>

        <div
            class="bg-white rounded-xl p-6 shadow-sm border border-zinc-100 relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                <flux:icon.chart-pie class="size-24 text-orange-600" />
            </div>
            <p class="text-sm font-medium text-zinc-500 mb-1">Taux d'Occupation</p>
            <p class="text-3xl font-bold text-zinc-900">{{ $stats['occupancy_rate'] }}%</p>
            <div class="mt-4 w-full bg-zinc-100 rounded-full h-1.5">
                <div class="bg-orange-500 h-1.5 rounded-full" style="width: {{ $stats['occupancy_rate'] }}%"></div>
            </div>
        </div>
    </div>

    <!-- Property List -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-zinc-900">Performance des Propriétés</h3>
        </div>

        <x-flux::card class="overflow-hidden p-0 border-0 shadow-sm ring-1 ring-zinc-200">
            <x-flux::table>
                <x-flux::table.columns>
                    <x-flux::table.column class="bg-zinc-50/50">Propriété</x-flux::table.column>
                    <x-flux::table.column class="bg-zinc-50/50" align="right">Unités</x-flux::table.column>
                    <x-flux::table.column class="bg-zinc-50/50" align="right">Revenus</x-flux::table.column>
                    <x-flux::table.column class="bg-zinc-50/50">État</x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @forelse ($properties as $property)
                        <x-flux::table.row :key="$property->id" class="hover:bg-zinc-50/50 transition-colors">
                            <x-flux::table.cell>
                                <div class="font-bold text-zinc-900">{{ $property->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $property->address }}</div>
                            </x-flux::table.cell>
                            <x-flux::table.cell align="right">
                                <flux:badge size="sm" color="zinc">{{ $property->units->count() }}</flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell align="right" class="font-mono font-medium text-zinc-700">
                                {{ number_format($property->units->sum(fn($u) => $u->leases->where('status', 'active')->sum('rent_amount')), 0, ',', ' ') }}
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:badge size="sm" color="{{ $property->status->color() }}" inset="top bottom">
                                    {{ $property->status->label() }}
                                </flux:badge>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @empty
                        <x-flux::table.row>
                            <x-flux::table.cell colspan="4" class="text-center py-12 text-zinc-500">
                                <div class="flex flex-col items-center gap-2">
                                    <flux:icon.building-storefront class="size-8 text-zinc-300" />
                                    <p>Votre portefeuille est vide pour le moment.</p>
                                </div>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>
    </div>
</div>