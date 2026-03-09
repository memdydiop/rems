<?php

use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use App\Models\Unit;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Number;

use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public Unit $unit;
    public $tab = 'overview';


    public function mount(Unit $unit)
    {
        $this->unit = $unit->load([
            'property',
            'activeLease.client',
            'activeLease.deposits',
            'activeLease.payments' => function ($query) {
                $query->latest('paid_at')->take(5);
            },
            'activeLease.media',
            'activeLease.adjustments',
            'sales.client'
        ]);
    }

    #[On('deposit-updated')]
    #[On('payment-recorded')]
    #[On('lease-updated')]
    public function refreshUnitData()
    {
        $this->unit->refresh();
        $this->unit->load([
            'property',
            'activeLease.client',
            'activeLease.deposits',
            'activeLease.payments' => function ($query) {
                $query->latest('paid_at')->take(5);
            },
            'activeLease.media',
            'activeLease.adjustments',
            'sales.client'
        ]);
        unset($this->annualPerformance);
    }

    public function deleteDocument($mediaId)
    {
        if ($this->unit->activeLease) {
            $media = $this->unit->activeLease->media()->findOrFail($mediaId);
            $media->delete();
            $this->refreshUnitData();
            $this->js("Flux.toast('Document supprimé avec succès.')");
        }
    }

    public function updatedTab()
    {
        $this->resetPage();
    }

    #[Computed]
    public function maintenanceRequests()
    {
        return $this->unit->maintenanceRequests()
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function leases()
    {
        return $this->unit->leases()
            ->with('client')
            ->latest('start_date')
            ->paginate(10);
    }

    #[Computed]
    public function sales()
    {
        return $this->unit->sales()
            ->with('client')
            ->latest('sold_at')
            ->paginate(10);
    }

    public function updateMaintenanceStatus($requestId, $status)
    {
        $request = MaintenanceRequest::findOrFail($requestId);
        $request->update(['status' => $status]);

        unset($this->maintenanceRequests);

        $statusLabel = MaintenanceStatus::from($status)->label();
        $this->js("Flux.toast('Statut mis à jour : {$statusLabel}')");
    }

    public function deleteMaintenanceRequest($requestId)
    {
        $request = MaintenanceRequest::findOrFail($requestId);
        $request->delete();

        unset($this->maintenanceRequests);

        $this->js("Flux.toast('Ticket supprimé.')");
    }

    public function reopenMaintenanceRequest($requestId)
    {
        $request = MaintenanceRequest::findOrFail($requestId);
        $request->update(['status' => 'pending']);

        unset($this->maintenanceRequests);

        $this->js("Flux.toast('Ticket réouvert.')");
    }

    #[Computed]
    public function annualPerformance()
    {
        return $this->unit->getAnnualPerformance();
    }
};
?>

<div>
    <x-layouts::content heading="{{ $unit->name }}"
        subheading="{{ $unit->property->name }} {{ $unit->property?->trashed() ? '(Supprimé)' : '' }} - {{ $unit->type->label() }}">

        <!-- Hero Header -->
        <div
            class="relative overflow-hidden bg-linear-to-br from-indigo-600 via-violet-600 to-purple-600 rounded-3xl shadow-lg shadow-indigo-200">
            <!-- Abstract Pattern Overlay -->
            <div class="absolute inset-0 opacity-10">
                <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0 100 C 20 0 50 0 100 100 Z" fill="white" />
                </svg>
            </div>

            <div class="relative px-8 py-10 md:py-12">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8">
                    <!-- Left: Unit & Property Info -->
                    <div class="flex items-start gap-6">
                        <div
                            class="size-20 rounded-2xl bg-white/15 backdrop-blur-md ring-1 ring-white/20 items-center justify-center shadow-inner shrink-0 hidden sm:flex">
                            <flux:icon.building-office-2 class="size-10 text-white" />
                        </div>
                        <div class="space-y-4">
                            <div>
                                <div class="flex items-center gap-3 flex-wrap mb-2">
                                    <h1 class="text-3xl md:text-4xl font-bold text-white tracking-tight">
                                        {{ $unit->name }}
                                    </h1>
                                    <flux:badge size="sm" class="bg-white/20 text-white border-0">
                                        {{ $unit->type->label() }}
                                    </flux:badge>
                                    <flux:badge size="sm" :color="$unit->status->color()" class="border-0">
                                        {{ $unit->status->label() }}
                                    </flux:badge>

                                    @if($unit->isUnderMaintenance())
                                        <flux:badge color="orange" size="sm" icon="wrench-screwdriver" class="border-0">
                                            Maintenance
                                        </flux:badge>
                                    @endif
                                </div>
                                <p class="text-indigo-100 flex items-center gap-2 text-lg">
                                    <flux:icon.home class="size-5 opacity-80" />
                                    <a href="{{ route('tenant.properties.show', $unit->property) }}"
                                        class="hover:underline hover:text-white transition-colors">
                                        {{ $unit->property->name }}
                                        @if($unit->property?->trashed())
                                            <span class="text-white/70 text-sm font-normal ml-1">(Supprimé)</span>
                                        @endif
                                    </a>
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-4 items-center">
                                @if($unit->activeLease)
                                    <!-- Client Mini-Profile -->
                                    <div class="flex items-center gap-3 pr-6 border-r border-white/10">
                                        <flux:avatar size="md" :name="$unit->activeLease->client->full_name" class="ring-2 ring-white/20" />
                                        <div class="min-w-0">
                                            <p class="text-white text-sm font-bold truncate">{{ $unit->activeLease->client->full_name }}</p>
                                            <p class="text-xs text-indigo-100 opacity-80">Locataire</p>
                                        </div>
                                    </div>
                                @endif

                                <!-- Unit Technical Specs & Amenities -->
                                <div class="flex flex-col gap-3">
                                    <div class="flex items-center gap-6">
                                        <div class="flex flex-col">
                                            <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold">Surface</span>
                                            <span class="text-white text-sm font-medium">{{ $unit->surface_area ? $unit->surface_area . ' m²' : '--' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold">Pièces</span>
                                            <span class="text-white text-sm font-medium">{{ $unit->rooms_count ?: '--' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold">SDB</span>
                                            <span class="text-white text-sm font-medium">{{ $unit->bathrooms_count ?: '--' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold">Cuisine</span>
                                            <span class="text-white text-sm font-medium">{{ $unit->getKitchenTypeLabel() }}</span>
                                        </div>
                                        <div class="flex flex-col line-clamp-1">
                                            <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold">CIE</span>
                                            <span class="text-white text-sm font-medium truncate" title="{{ $unit->electricity_meter_number }}">{{ $unit->electricity_meter_number ?: '--' }}</span>
                                        </div>
                                        <div class="flex flex-col line-clamp-1">
                                            <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold">SODECI</span>
                                            <span class="text-white text-sm font-medium truncate" title="{{ $unit->water_meter_number }}">{{ $unit->water_meter_number ?: '--' }}</span>
                                        </div>
                                    </div>

                                    @php
                                        $allAmenities = array_unique(array_merge($unit->property?->getAmenityLabels() ?? [], $unit->getAmenityLabels() ?? []));
                                    @endphp
                                    @if(count($allAmenities) > 0)
                                        <div class="flex flex-wrap gap-1.5 mt-1">
                                            @foreach($allAmenities as $amenity)
                                                <flux:badge size="2xs" color="indigo" variant="flat" class="bg-white/10! text-white! border-transparent! text-[9px] px-1.5 h-4.5">
                                                    {{ $amenity }}
                                                </flux:badge>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Action Buttons -->
                    <div class="flex flex-col gap-2 w-full lg:w-auto">
                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            <flux:button variant="ghost"
                                class="bg-white/10! text-white! hover:bg-white/20! border-transparent!"
                                icon="pencil-square" wire:click="$dispatch('edit-unit', { id: '{{ $unit->id }}' })">
                                Modifier l'unité
                            </flux:button>
                            @if($unit->activeLease)
                                <flux:button variant="ghost"
                                    class="bg-white/10! text-white! hover:bg-white/20! border-transparent!"
                                    icon="pencil"
                                    wire:click="$dispatch('open-modal', { name: 'edit-lease', lease_id: '{{ $unit->activeLease->id }}' })">
                                    Gérer le bail
                                </flux:button>
                            @elseif($unit->status->value === 'vacant' && $unit->isForRental())
                                <flux:button icon="document-plus" variant="filled"
                                    :disabled="$unit->isUnderMaintenance()"
                                    class="bg-white! text-indigo-600! font-bold hover:bg-indigo-50!"
                                    wire:click="$dispatch('open-modal', { name: 'create-lease', unit_id: '{{ $unit->id }}' })">
                                    Nouveau bail
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>

                @php
                    $performance = $unit->getAnnualPerformance();
                @endphp
                <div class="mt-10 pt-8 border-t border-white/10 grid grid-cols-2 md:grid-cols-5 gap-6">
                    @if($unit->activeLease)
                        <div class="flex flex-col">
                            <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold mb-1">Loyer Mensuel</span>
                            <div class="flex items-baseline gap-1">
                                <span class="text-white text-xl font-black">
                                    {{ Number::currency($unit->activeLease->rent_amount, 'XOF') }}
                                </span>
                                @if($unit->activeLease->charges_amount > 0)
                                    <span class="text-xs text-indigo-200">+ {{ Number::currency($unit->activeLease->charges_amount, 'XOF') }} (Ch.)</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold mb-1">Caution</span>
                            <span class="text-white text-xl font-black">
                                {{ Number::currency($unit->activeLease->deposit_amount, 'XOF') }}
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold mb-1">Avance de Loyer</span>
                            <span class="text-white text-xl font-black">
                                {{ Number::currency($unit->activeLease->advance_amount, 'XOF') }}
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold mb-1">Frais d'entrée (Total)</span>
                            <span class="text-indigo-200 text-xl font-bold">
                                {{ Number::currency($unit->activeLease->deposit_amount + $unit->activeLease->advance_amount, 'XOF') }}
                            </span>
                        </div>
                    @elseif($unit->isForSale())
                        <div class="flex flex-col">
                            <span class="text-2xs text-green-200 uppercase tracking-widest font-bold mb-1">Prix de Vente</span>
                            <span class="text-white text-xl font-black">
                                {{ $unit->sale_price ? Number::currency($unit->sale_price, 'XOF') : 'Non défini' }}
                            </span>
                        </div>
                    @else
                        <div class="flex flex-col col-span-4">
                            <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold mb-1">Status</span>
                            <span class="text-white text-xl font-black">{{ $unit->status->label() }}</span>
                        </div>
                    @endif

                    <div class="flex flex-col">
                        <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold mb-1">Revenu Net (12m)</span>
                        <div class="flex items-baseline gap-2">
                            <span class="text-white text-xl font-black">
                                {{ Number::currency($performance['net'], 'XOF') }}
                            </span>
                            <span class="text-2xs @if($performance['margin'] >= 90) text-emerald-400 @elseif($performance['margin'] >= 70) text-amber-400 @else text-rose-400 @endif font-bold">
                                {{ number_format($performance['margin'], 1) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Tabs & Content -->
        <div class="space-y-6">
            <div class="border-b border-zinc-200">
                <div class="flex gap-6">
                    <button wire:click="$set('tab', 'overview')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $tab === 'overview' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}">
                        Vue d'ensemble
                    </button>
                    <button wire:click="$set('tab', 'leases')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $tab === 'leases' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}">
                        Baux
                    </button>
                    <button wire:click="$set('tab', 'maintenance')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $tab === 'maintenance' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}">
                        Maintenance
                    </button>
                    <button wire:click="$set('tab', 'documents')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $tab === 'documents' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}">
                        Documents
                    </button>
                    <button wire:click="$set('tab', 'history')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $tab === 'history' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}">
                        Historique
                    </button>
                    @if($unit->sales()->exists())
                        <button wire:click="$set('tab', 'sales')"
                            class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $tab === 'sales' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}">
                            Ventes
                        </button>
                    @endif
                </div>
            </div>

            <div>
                <!-- Content Area -->
                <div class="mt-6">
                    @switch($tab)
                        @case('leases')
                            <div class="space-y-6">
                                <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm">
                                    <x-flux::card.header icon="key" title="Historique des Baux"
                                        subtitle="Tous les contrats de location passés et présents" class="bg-zinc-50 border-b border-zinc-100 py-3">
                                    </x-flux::card.header>

                                    <x-flux::table>
                                        <x-flux::table.columns>
                                            <x-flux::table.column>Client</x-flux::table.column>
                                            <x-flux::table.column>Période</x-flux::table.column>
                                            <x-flux::table.column>Loyer</x-flux::table.column>
                                            <x-flux::table.column>Statut</x-flux::table.column>
                                            <x-flux::table.column align="right"></x-flux::table.column>
                                        </x-flux::table.columns>

                                        <x-flux::table.rows>
                                            @foreach($unit->leases->sortByDesc('start_date') as $lease)
                                                <x-flux::table.row :key="$lease->id">
                                                    <x-flux::table.cell class="flex items-center gap-2">
                                                        <flux:avatar size="xs" :name="$lease->client->full_name" />
                                                        <span class="font-medium text-zinc-900">{{ $lease->client->full_name }}</span>
                                                    </x-flux::table.cell>
                                                    <x-flux::table.cell class="text-xs text-zinc-600">
                                                        {{ $lease->start_date->format('d/m/Y') }}
                                                        @if($lease->end_date)
                                                            — {{ $lease->end_date->format('d/m/Y') }}
                                                        @else
                                                            — <span class="italic text-zinc-400">En cours</span>
                                                        @endif
                                                    </x-flux::table.cell>
                                                    <x-flux::table.cell class="font-bold text-zinc-700">
                                                        {{ Number::currency($lease->rent_amount, 'XOF') }}
                                                    </x-flux::table.cell>
                                                    <x-flux::table.cell>
                                                        @php
                                                            $color = match($lease->status->value ?? $lease->status) {
                                                                'active' => 'green',
                                                                'expired' => 'zinc',
                                                                'terminated' => 'red',
                                                                'draft' => 'amber',
                                                                default => 'zinc',
                                                            };
                                                        @endphp
                                                        <flux:badge :color="$color" size="sm">
                                                            {{ $lease->status->label() ?? $lease->status }}
                                                        </flux:badge>
                                                    </x-flux::table.cell>
                                                    <x-flux::table.cell align="right">
                                                        <flux:button variant="ghost" size="xs" icon="eye"
                                                            wire:click="$dispatch('open-modal', { name: 'edit-lease', lease_id: '{{ $lease->id }}' })" />
                                                    </x-flux::table.cell>
                                                </x-flux::table.row>
                                            @endforeach
                                        </x-flux::table.rows>
                                    </x-flux::table>
                                </x-flux::card>
                            </div>
                        @break
                        @case('overview')
                            <div class="space-y-6">
                                @if($unit->notes)
                                    <x-flux::card>
                                        <x-flux::card.body class="space-y-4">
                                            <div>
                                                <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                                                    <flux:icon.document-text class="size-4 text-indigo-500" />
                                                    Notes & Observations
                                                </div>
                                                <div class="text-sm text-zinc-600 italic border-l-2 border-indigo-200 pl-4 py-1">
                                                    {{ $unit->notes }}
                                                </div>
                                            </div>
                                        </x-flux::card.body>
                                    </x-flux::card>
                                @endif

                                @if($unit->activeLease && $unit->activeLease->payments->count() > 0)
                                    <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm">
                                        <x-flux::card.header icon="banknotes" title="Derniers Paiements de Loyer"
                                            subtitle="État des encaissements récents" class="bg-zinc-50 border-b border-zinc-100 py-3">
                                            <x-slot:cardActions>
                                                <flux:button size="sm" icon="plus" variant="primary"
                                                    wire:click="$dispatch('record-payment', { lease_id: '{{ $unit->activeLease->id }}' })">
                                                    Enregistrer
                                                </flux:button>
                                            </x-slot:cardActions>
                                        </x-flux::card.header>

                                        <x-flux::table>
                                            <x-flux::table.columns>
                                                <x-flux::table.column>Période</x-flux::table.column>
                                                <x-flux::table.column>Montant Payé</x-flux::table.column>
                                                <x-flux::table.column>Date de Paiement</x-flux::table.column>
                                                <x-flux::table.column>Méthode</x-flux::table.column>
                                                <x-flux::table.column align="right"></x-flux::table.column>
                                            </x-flux::table.columns>

                                            <x-flux::table.rows>
                                                @foreach($unit->activeLease->payments->sortByDesc('period_start')->take(5) as $payment)
                                                    <x-flux::table.row :key="$payment->id">
                                                        <x-flux::table.cell class="font-bold text-zinc-700">
                                                            @if($payment->period_start)
                                                                @if($payment->months_count === 1)
                                                                    {{ $payment->period_start->translatedFormat('F Y') }}
                                                                @else
                                                                    <span class="whitespace-nowrap italic text-zinc-500">
                                                                        {{ $payment->period_start->translatedFormat('M Y') }}
                                                                        @if($payment->period_end)
                                                                            → {{ $payment->period_end->translatedFormat('M Y') }}
                                                                        @endif
                                                                    </span>
                                                                @endif
                                                                @if($payment->months_count > 1)
                                                                    <flux:badge size="2xs" color="indigo" class="ml-1">{{ $payment->months_count }} mois</flux:badge>
                                                                @endif
                                                            @else
                                                                <span class="text-zinc-400">--</span>
                                                            @endif
                                                        </x-flux::table.cell>
                                                        <x-flux::table.cell class="font-black text-indigo-600">
                                                            {{ Number::currency($payment->amount, 'XOF') }}
                                                        </x-flux::table.cell>
                                                        <x-flux::table.cell class="text-xs text-zinc-500">
                                                            {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y') : '--' }}
                                                        </x-flux::table.cell>
                                                        <x-flux::table.cell>
                                                            {{ match($payment->method) {
                                                                'cash' => 'Espèces',
                                                                'transfer' => 'Virement',
                                                                'check' => 'Chèque',
                                                                'mobile_money' => 'Mobile Money',
                                                                default => $payment->method
                                                            } }}
                                                        </x-flux::table.cell>
                                                        <x-flux::table.cell align="right">
                                                            <flux:button size="xs" variant="ghost" icon="document-text"
                                                                tooltip="Détails"
                                                                wire:click="$dispatch('open-modal', { name: 'payment-details', id: '{{ $payment->id }}' })" />
                                                        </x-flux::table.cell>
                                                    </x-flux::table.row>
                                                @endforeach
                                            </x-flux::table.rows>
                                        </x-flux::table>
                                    </x-flux::card>
                                @endif

                                @if($unit->activeLease && $unit->activeLease->adjustments->count() > 0)
                                    <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm mt-6">
                                        <x-flux::card.header icon="arrows-up-down" title="Historique des Révisions"
                                            subtitle="Évolution du montant du bail" class="bg-zinc-50 border-b border-zinc-100 py-3">
                                        </x-flux::card.header>

                                        <x-flux::card.body class="p-0">
                                            <x-flux::table>
                                                <x-flux::table.columns>
                                                    <x-flux::table.column>Date d'effet</x-flux::table.column>
                                                    <x-flux::table.column>Ancien Montant</x-flux::table.column>
                                                    <x-flux::table.column>Nouveau Montant</x-flux::table.column>
                                                    <x-flux::table.column>Différence</x-flux::table.column>
                                                </x-flux::table.columns>

                                                <x-flux::table.rows>
                                                    @foreach($unit->activeLease->adjustments->sortByDesc('effective_date') as $adj)
                                                        @php
                                                            $diff = $adj->new_amount - $adj->old_amount;
                                                        @endphp
                                                        <x-flux::table.row :key="$adj->id">
                                                            <x-flux::table.cell
                                                                class="font-medium text-xs">{{ $adj->effective_date->format('d/m/Y') }}</x-flux::table.cell>
                                                            <x-flux::table.cell
                                                                class="text-zinc-500 text-xs">{{ Number::currency($adj->old_amount, 'XOF') }}</x-flux::table.cell>
                                                            <x-flux::table.cell
                                                                class="font-medium text-indigo-700 text-xs">{{ Number::currency($adj->new_amount, 'XOF') }}</x-flux::table.cell>
                                                            <x-flux::table.cell
                                                                class="font-medium text-xs {{ $diff > 0 ? 'text-green-600' : ($diff < 0 ? 'text-red-500' : 'text-zinc-500') }}">
                                                                {{ $diff > 0 ? '+' : '' }}{{ Number::currency($diff, 'XOF') }}
                                                            </x-flux::table.cell>
                                                        </x-flux::table.row>
                                                    @endforeach
                                                </x-flux::table.rows>
                                            </x-flux::table>
                                        </x-flux::card.body>
                                    </x-flux::card>
                                @endif

                                @if($unit->activeLease && $unit->activeLease->getMedia('documents')->count() > 0)
                                    <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm mt-6">
                                        <x-flux::card.header icon="document-duplicate" title="Documents du bail"
                                            subtitle="Accès rapide aux fichiers joints" class="bg-zinc-50 border-b border-zinc-100 py-3">
                                            <x-slot:cardActions>
                                                <flux:button size="xs" variant="ghost" wire:click="$set('tab', 'documents')">Voir tout</flux:button>
                                            </x-slot:cardActions>
                                        </x-flux::card.header>

                                        <div class="divide-y divide-zinc-100">
                                            @foreach($unit->activeLease->getMedia('documents')->take(3) as $media)
                                                <div class="px-4 py-3 flex items-center justify-between group hover:bg-zinc-50 transition-colors">
                                                    <div class="flex items-center gap-3">
                                                        <flux:icon.document class="size-4 text-zinc-400" />
                                                        <span class="text-xs font-medium text-zinc-700 truncate max-w-xs">{{ $media->file_name }}</span>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <flux:button variant="ghost" size="2xs" icon="eye" href="{{ $media->getUrl() }}" target="_blank" />
                                                        <flux:button variant="ghost" size="2xs" icon="arrow-down-tray" href="{{ $media->getUrl() }}" download />
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </x-flux::card>
                                @endif
                            </div>
                        @break

                        @case('maintenance')
                            <div class="space-y-6">
                                <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm">
                                    <x-flux::card.header icon="wrench-screwdriver" title="Maintenance"
                                        subtitle="Historique des demandes" class="bg-zinc-50 border-b border-zinc-100 py-3">
                                        <x-slot:cardActions>
                                            <flux:button variant="primary" size="sm" icon="plus" class="w-full sm:w-auto"
                                                wire:click="$dispatch('open-modal', { name: 'unit-create-maintenance', unit_id: '{{ $unit->id }}' })">
                                                Signaler</flux:button>
                                        </x-slot:cardActions>
                                    </x-flux::card.header>

                                    @php $maintenanceRequests = $this->maintenanceRequests; @endphp
                                    <x-flux::table :paginate="$maintenanceRequests">
                                        <x-flux::table.columns>
                                            <x-flux::table.column>Titre</x-flux::table.column>
                                            <x-flux::table.column>Priorité</x-flux::table.column>
                                            <x-flux::table.column>Statut</x-flux::table.column>
                                            <x-flux::table.column>Date</x-flux::table.column>
                                            <x-flux::table.column align="right"></x-flux::table.column>
                                        </x-flux::table.columns>

                                        <x-flux::table.rows>
                                            @forelse($maintenanceRequests as $request)
                                                <x-flux::table.row :key="$request->id">
                                                    <x-flux::table.cell class="font-medium text-sm">{{ $request->title }}</x-flux::table.cell>
                                                    <x-flux::table.cell>
                                                        <flux:badge size="sm" :color="$request->priority->color()" inset="top bottom">
                                                            {{ $request->priority->label() }}
                                                        </flux:badge>
                                                    </x-flux::table.cell>
                                                    <x-flux::table.cell>
                                                        <flux:badge size="sm" :color="$request->status->color()" inset="top bottom">
                                                            {{ $request->status->label() }}
                                                        </flux:badge>
                                                    </x-flux::table.cell>
                                                    <x-flux::table.cell class="text-xs text-zinc-500">{{ $request->created_at->diffForHumans() }}</x-flux::table.cell>
                                                    <x-flux::table.cell align="right">
                                                        <flux:dropdown>
                                                            <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                                            <flux:menu>
                                                                @if($request->isEditable())
                                                                    @foreach(MaintenanceStatus::cases() as $s)
                                                                        @if($s !== $request->status)
                                                                            <flux:menu.item
                                                                                wire:click="updateMaintenanceStatus('{{ $request->id }}', '{{ $s->value }}')"
                                                                                icon="{{ match ($s) {
                                                                                    MaintenanceStatus::Pending => 'clock',
                                                                                    MaintenanceStatus::InProgress => 'arrow-path',
                                                                                    MaintenanceStatus::Resolved => 'check-circle',
                                                                                    MaintenanceStatus::Cancelled => 'x-circle',
                                                                                } }}">
                                                                                {{ $s->label() }}
                                                                            </flux:menu.item>
                                                                        @endif
                                                                    @endforeach
                                                                    <flux:menu.separator />
                                                                    <flux:menu.item icon="trash" variant="danger"
                                                                        wire:click="deleteMaintenanceRequest('{{ $request->id }}')"
                                                                        wire:confirm="Êtes-vous sûr de vouloir supprimer ce ticket ?">
                                                                        Supprimer
                                                                    </flux:menu.item>
                                                                @else
                                                                    <flux:menu.item icon="arrow-path"
                                                                        wire:click="reopenMaintenanceRequest('{{ $request->id }}')"
                                                                        wire:confirm="Réouvrir ce ticket ?">
                                                                        Réouvrir
                                                                    </flux:menu.item>
                                                                @endif
                                                            </flux:menu>
                                                        </flux:dropdown>
                                                    </x-flux::table.cell>
                                                </x-flux::table.row>
                                            @empty
                                                <x-flux::table.row>
                                                    <x-flux::table.cell colspan="5" class="text-center py-12 text-zinc-400">
                                                        Aucune demande de maintenance.
                                                    </x-flux::table.cell>
                                                </x-flux::table.row>
                                            @endforelse
                                        </x-flux::table.rows>
                                    </x-flux::table>
                                </x-flux::card>
                            </div>
                        @break

                        @case('documents')
                            <div class="space-y-6">
                                <x-flux::card>
                                    <x-flux::card.header icon="document-duplicate" title="Documents du bail" description="Contrats, pièces d'identité et justificatifs liés au bail actif." />
                                    <x-flux::card.body class="p-0">
                                        @if($unit->activeLease && $unit->activeLease->getMedia('documents')->count() > 0)
                                            <div class="divide-y divide-zinc-100">
                                                @foreach($unit->activeLease->getMedia('documents') as $media)
                                                    <div class="p-4 flex items-center justify-between group hover:bg-zinc-50 transition-colors">
                                                        <div class="flex items-center gap-4">
                                                            <div class="size-10 rounded bg-zinc-100 flex items-center justify-center text-zinc-500">
                                                                <flux:icon.document class="size-5" />
                                                            </div>
                                                            <div>
                                                                <div class="text-sm font-medium text-zinc-900">{{ $media->file_name }}</div>
                                                                <div class="text-xs text-zinc-500">{{ $media->human_readable_size }} • ajouté le {{ $media->created_at->format('d/m/Y') }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <flux:button variant="ghost" size="sm" icon="eye" href="{{ $media->getUrl() }}" target="_blank">Voir</flux:button>
                                                            <flux:button variant="ghost" size="sm" icon="arrow-down-tray" href="{{ $media->getUrl() }}" download>Télécharger</flux:button>
                                                            <flux:button size="sm" variant="ghost" icon="trash"
                                                                class="text-red-500"
                                                                wire:click="deleteDocument({{ $media->id }})" wire:confirm="Supprimer ce document ?" />
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="p-12 flex flex-col items-center justify-center text-center">
                                                <div class="size-16 bg-zinc-50 rounded-full flex items-center justify-center mb-4">
                                                    <flux:icon.document-duplicate class="size-8 text-zinc-300" />
                                                </div>
                                                <h3 class="text-sm font-medium text-zinc-900">Aucun document</h3>
                                                <p class="text-xs text-zinc-500 mt-1">Aucun document n'a été rattaché à ce bail pour le moment.</p>
                                            </div>
                                        @endif
                                    </x-flux::card.body>
                                </x-flux::card>
                            </div>
                        @break

                        @case('history')
                            <div class="space-y-6">
                                <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm">
                                    <x-flux::card.header icon="clock" title="Historique Complet"
                                        subtitle="Tous les événements liés à cette unité" class="bg-zinc-50 border-b border-zinc-100 py-3">
                                    </x-flux::card.header>
                                    <x-flux::card.body class="p-6">
                                        <div class="relative pl-6 border-l-2 border-zinc-100 space-y-8">
                                            @forelse($unit->activities()->orderByDesc('created_at')->get() as $log)
                                                <div class="relative">
                                                    <div class="absolute -left-6.5 top-1.5 size-3 rounded-full bg-white border-2 border-indigo-500 shadow-sm"></div>
                                                    <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1">
                                                        <h4 class="text-sm font-bold text-zinc-900">{{ $log->description }}</h4>
                                                        <span class="text-2xs text-zinc-400 font-mono">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                                    </div>
                                                    <p class="text-xs text-zinc-500 mt-0.5">Par {{ $log->causer?->name ?? 'Système' }}</p>
                                                </div>
                                            @empty
                                                <div class="text-center py-6">
                                                    <p class="text-xs text-zinc-400 italic">Aucun historique disponible.</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </x-flux::card.body>
                                </x-flux::card>
                            </div>
                        @break

                        @case('sales')
                            <div class="space-y-6">
                                {{-- Sales Tab Logic here if needed --}}
                            </div>
                        @break
                    @endswitch
                </div>
            </div>
        </div>

        <!-- Modals -->
        <livewire:pages::tenant.units.modals.edit />
        <livewire:pages::tenant.leases.modals.create />
        <livewire:pages::tenant.leases.modals.edit />
        <livewire:pages::tenant.maintenance.units.create-modal />
        <livewire:pages::tenant.maintenance.units.edit-modal />
        <livewire:pages::tenant.clients.modals.create />
        <livewire:pages::tenant.sales.modals.record-sale />
        <livewire:pages::tenant.deposits.manage />
        <livewire:pages::tenant.payments.modals.record-payment />

    </x-layouts::content>
</div>