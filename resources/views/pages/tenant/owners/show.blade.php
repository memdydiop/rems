<?php

use App\Models\Owner;
use App\Models\Property;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app', ['title' => 'Propriétaire'])] class extends Component {
    public Owner $owner;

    public function mount(Owner $owner)
    {
        $this->owner = $owner->load(['properties.units.leases' => fn($q) => $q->where('status', 'active')]);
    }

    public function with()
    {
        $properties = $this->owner->properties;
        $totalUnits = 0;
        $occupiedUnits = 0;
        $monthlyRevenue = 0;
        $maintenanceCount = 0;

        foreach ($properties as $property) {
            $units = $property->units;
            $totalUnits += $units->count();
            foreach ($units as $unit) {
                if ($unit->status === 'occupied' || $unit->leases->count() > 0) {
                    $occupiedUnits++;
                }
                $monthlyRevenue += $unit->leases->sum('rent_amount');
            }
            $maintenanceCount += $property->maintenanceRequests()
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();
        }

        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

        return [
            'properties' => $properties,
            'totalUnits' => $totalUnits,
            'occupiedUnits' => $occupiedUnits,
            'monthlyRevenue' => $monthlyRevenue,
            'occupancyRate' => $occupancyRate,
            'maintenanceCount' => $maintenanceCount,
        ];
    }
};
?>

<div>
    <x-layouts::content heading="{{ $owner->first_name }} {{ $owner->last_name }}"
        subheading="Fiche détaillée du propriétaire">

        <!-- Hero Header -->
        <div
            class="relative overflow-hidden bg-linear-to-br from-teal-600 via-emerald-600 to-green-600 rounded-3xl shadow-lg shadow-emerald-200">
            <div class="absolute inset-0 opacity-10">
                <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0 100 C 20 0 50 0 100 100 Z" fill="white" />
                </svg>
            </div>

            <div class="relative px-8 py-10 md:py-12">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                    <div class="flex items-start gap-6">
                        <div
                            class="size-24 rounded-2xl bg-white/15 backdrop-blur-md ring-1 ring-white/20 flex items-center justify-center shadow-inner shrink-0">
                            <flux:icon.user class="size-10 text-white" />
                        </div>
                        <div>
                            <div class="flex items-center gap-3 flex-wrap mb-2">
                                <h1 class="text-3xl md:text-4xl font-bold text-white tracking-tight">
                                    {{ $owner->first_name }} {{ $owner->last_name }}
                                </h1>
                                <flux:badge size="sm" :color="$owner->status->color()" class="border-0">
                                    {{ $owner->status->label() }}
                                </flux:badge>
                            </div>

                            <p class="text-emerald-100 flex items-center gap-2 text-lg">
                                <flux:icon.map-pin class="size-5 opacity-80" />
                                {{ $owner->address ?? 'Adresse non renseignée' }}
                            </p>

                            <div class="flex items-center gap-6 mt-6">
                                <div class="flex flex-col">
                                    <span class="text-xs text-emerald-200 uppercase tracking-wider font-semibold">Ajouté
                                        le</span>
                                    <span
                                        class="text-white font-medium">{{ $owner->created_at->format('d M Y') }}</span>
                                </div>
                                <div class="w-px h-8 bg-white/10"></div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-xs text-emerald-200 uppercase tracking-wider font-semibold">Propriétés</span>
                                    <span class="text-white font-medium">{{ $properties->count() }} bien(s)</span>
                                </div>
                                <div class="w-px h-8 bg-white/10"></div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-xs text-emerald-200 uppercase tracking-wider font-semibold">Unités</span>
                                    <span class="text-white font-medium">{{ $totalUnits }} unité(s)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 items-start shrink-0">
                        <flux:button variant="ghost" class="text-white! hover:bg-white/10!" icon="plus"
                            wire:click="$dispatch('open-modal', { name: 'create-property', owner_id: '{{ $owner->id }}' })">
                            Ajouter un bien
                        </flux:button>
                        <flux:button variant="ghost" class="text-white! hover:bg-white/10!" icon="arrow-left"
                            href="{{ route('tenant.owners.index') }}">
                            Retour
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <x-stats-card title="Propriétés" :value="$properties->count()" icon="building-office-2" color="teal" />
            <x-stats-card title="Taux d'occupation" :value="$occupancyRate . '%'" icon="chart-bar" color="blue" />
            <x-stats-card title="Revenus mensuels" :value="number_format($monthlyRevenue, 0, ',', ' ') . ' F'"
                icon="banknotes" color="green" />
            <x-stats-card title="Tickets ouverts" :value="$maintenanceCount" icon="wrench-screwdriver" color="orange" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Properties Table (2/3 width) -->
            <div class="lg:col-span-2">
                <x-flux::card class="p-0 overflow-hidden">
                    <x-flux::card.header icon="building-office-2" title="Propriétés"
                        subtitle="{{ $properties->count() }} bien(s) gérés" />

                    @if($properties->count() > 0)
                        <div class="divide-y divide-zinc-100">
                            @foreach($properties as $property)
                                <a href="{{ route('tenant.properties.show', $property) }}"
                                    class="flex items-center justify-between px-6 py-4 hover:bg-zinc-50 transition-colors group">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="size-12 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0 group-hover:bg-indigo-100 transition-colors">
                                            <flux:icon.building-office-2 class="size-6 text-indigo-500" />
                                        </div>
                                        <div>
                                            <p
                                                class="font-semibold text-zinc-900 group-hover:text-indigo-600 transition-colors">
                                                {{ $property->name }}
                                            </p>
                                            <p class="text-sm text-zinc-500">{{ $property->address ?? '—' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-zinc-900">
                                                {{ $property->units->count() }} unité(s)
                                            </p>
                                            @php
                                                $propOccupied = $property->units->where('status', 'occupied')->count();
                                                $propTotal = $property->units->count();
                                                $propRate = $propTotal > 0 ? round(($propOccupied / $propTotal) * 100) : 0;
                                            @endphp
                                            <p class="text-xs text-zinc-500">{{ $propRate }}% occupé</p>
                                        </div>
                                        <flux:badge size="sm" :color="$property->status->color()" inset="top bottom">
                                            {{ $property->status->label() }}
                                        </flux:badge>
                                        <flux:icon.chevron-right
                                            class="size-4 text-zinc-400 group-hover:text-indigo-500 transition-colors" />
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="inline-flex items-center justify-center size-12 rounded-full bg-zinc-100 mb-4">
                                <flux:icon.building-office-2 class="size-6 text-zinc-400" />
                            </div>
                            <h3 class="text-lg font-medium text-zinc-900">Aucune propriété</h3>
                            <p class="text-zinc-500 max-w-sm mx-auto mt-1">Ce propriétaire n'a pas encore de biens associés.
                            </p>
                        </div>
                    @endif
                </x-flux::card>
            </div>

            <!-- Owner Info Card (1/3 width) -->
            <div class="space-y-6">
                <!-- Contact Info -->
                <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm">
                    <x-flux::card.header icon="identification" title="Informations"
                        class="bg-zinc-50/50 border-b border-zinc-100 py-3" />

                    <div class="p-5 space-y-4">
                        @if($owner->email)
                            <div class="flex items-center gap-3">
                                <div class="size-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                    <flux:icon.envelope class="size-4 text-blue-500" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-zinc-500 uppercase tracking-wider font-semibold">Email</p>
                                    <a href="mailto:{{ $owner->email }}"
                                        class="text-sm text-blue-600 hover:underline truncate block">{{ $owner->email }}</a>
                                </div>
                            </div>
                        @endif

                        @if($owner->phone)
                            <div class="flex items-center gap-3">
                                <div class="size-9 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                                    <flux:icon.phone class="size-4 text-green-500" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-zinc-500 uppercase tracking-wider font-semibold">Téléphone</p>
                                    <a href="tel:{{ $owner->phone }}"
                                        class="text-sm text-green-600 hover:underline">{{ $owner->phone }}</a>
                                </div>
                            </div>
                        @endif

                        @if($owner->address)
                            <div class="flex items-center gap-3">
                                <div class="size-9 rounded-lg bg-violet-50 flex items-center justify-center shrink-0">
                                    <flux:icon.map-pin class="size-4 text-violet-500" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-zinc-500 uppercase tracking-wider font-semibold">Adresse</p>
                                    <p class="text-sm text-zinc-900">{{ $owner->address }}</p>
                                </div>
                            </div>
                        @endif

                        @if($owner->account_details)
                            <div class="flex items-center gap-3">
                                <div class="size-9 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                                    <flux:icon.credit-card class="size-4 text-amber-500" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-zinc-500 uppercase tracking-wider font-semibold">Coordonnées
                                        bancaires</p>
                                    <p class="text-sm text-zinc-900">{{ $owner->account_details }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </x-flux::card>

                <!-- Revenue Summary -->
                <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm">
                    <x-flux::card.header icon="banknotes" title="Résumé financier"
                        class="bg-zinc-50/50 border-b border-zinc-100 py-3" />

                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-zinc-500">Revenus mensuels</span>
                            <span
                                class="text-sm font-bold text-emerald-600">{{ number_format($monthlyRevenue, 0, ',', ' ') }}
                                F</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-zinc-500">Unités occupées</span>
                            <span class="text-sm font-medium text-zinc-900">{{ $occupiedUnits }} /
                                {{ $totalUnits }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-zinc-500">Taux d'occupation</span>
                            <span class="text-sm font-medium text-zinc-900">{{ $occupancyRate }}%</span>
                        </div>
                        <div class="w-full bg-zinc-100 rounded-full h-2 mt-1">
                            <div class="h-2 rounded-full {{ $occupancyRate >= 80 ? 'bg-emerald-500' : ($occupancyRate >= 50 ? 'bg-amber-500' : 'bg-red-500') }} transition-all"
                                style="width: {{ $occupancyRate }}%"></div>
                        </div>
                    </div>
                </x-flux::card>
            </div>
        </div>
    </x-layouts::content>

    <livewire:pages::tenant.properties.modals.create />
</div>