<?php

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
        $this->unit = $unit->load(['property', 'activeLease.renter']);
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
            ->with('renter')
            ->latest('start_date')
            ->paginate(10);
    }
};
?>

<div>
    <x-layouts::content heading="{{ $unit->name }}"
        subheading="{{ $unit->property->name }} - {{ $unit->type->label() }}">

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
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                    <div class="flex items-start gap-6">
                        <div
                            class="size-24 rounded-2xl bg-white/15 backdrop-blur-md ring-1 ring-white/20 flex items-center justify-center shadow-inner shrink-0">
                            <flux:icon.building-office-2 class="size-10 text-white" />
                        </div>
                        <div>
                            <div class="flex items-center gap-3 flex-wrap mb-2">
                                <h1 class="text-3xl md:text-4xl font-bold text-white tracking-tight">
                                    {{ $unit->name }}
                                </h1>
                                <flux:badge size="sm" class="bg-white/20 text-white border-0 inset-0">
                                    {{ $unit->type->label() }}
                                </flux:badge>
                                <flux:badge size="sm" :color="$unit->status->color()" class="border-0">
                                    {{ $unit->status->label() }}
                                </flux:badge>
                            </div>

                            <p class="text-indigo-100 flex items-center gap-2 text-lg">
                                <flux:icon.home class="size-5 opacity-80" />
                                <a href="{{ route('tenant.properties.show', $unit->property) }}"
                                    class="hover:underline hover:text-white transition-colors">
                                    {{ $unit->property->name }}
                                </a>
                            </p>

                            <div class="flex items-center gap-6 mt-6">
                                <div class="flex flex-col">
                                    <span class="text-xs text-indigo-200 uppercase tracking-wider font-semibold">Loyer
                                        Actuel</span>
                                    <span
                                        class="text-white font-medium">{{ Number::currency($unit->rent_amount, 'XOF') }}</span>
                                </div>
                                <div class="w-px h-8 bg-white/10"></div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-xs text-indigo-200 uppercase tracking-wider font-semibold">Locataire</span>
                                    <span class="text-white font-medium">
                                        {{ $unit->activeLease?->renter->first_name ?? 'Vacant' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <flux:button variant="ghost"
                            class="bg-white/10! text-white! hover:bg-white/20! border-transparent!" icon="pencil-square"
                            wire:click="$dispatch('edit-unit', { id: '{{ $unit->id }}' })">
                            Modifier
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-flux::card>
                <x-flux::card.body class="p-6 flex items-center gap-4">
                    <div class="size-12 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                        <flux:icon.banknotes class="size-6 text-blue-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-500">Loyer Mensuel</p>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-zinc-900">
                                {{ Number::currency($unit->rent_amount, 'XOF') }}
                            </h3>
                        </div>
                    </div>
                </x-flux::card.body>
            </x-flux::card>

            <x-flux::card>
                <x-flux::card.body class="p-6 flex items-center gap-4">
                    <div class="size-12 rounded-xl bg-violet-50 flex items-center justify-center shrink-0">
                        <flux:icon.calendar class="size-6 text-violet-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-500">Fin du bail</p>
                        <div class="flex items-baseline gap-2">
                            @if($unit->activeLease && $unit->activeLease->end_date)
                                <h3 class="text-2xl font-bold text-zinc-900">
                                    {{ $unit->activeLease->end_date->format('d/m/Y') }}
                                </h3>
                                <span
                                    class="text-xs font-medium text-zinc-500">({{ $unit->activeLease->end_date->diffForHumans() }})</span>
                            @elseif($unit->activeLease)
                                <h3 class="text-2xl font-bold text-zinc-900">Indéterminé</h3>
                            @else
                                <h3 class="text-xl font-bold text-zinc-400">Aucun bail</h3>
                            @endif
                        </div>
                    </div>
                </x-flux::card.body>
            </x-flux::card>

            <x-flux::card>
                <x-flux::card.body class="p-6 flex items-center gap-4">
                    <div class="size-12 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                        <flux:icon.check-circle class="size-6 text-emerald-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-500">Statut</p>
                        <flux:badge size="sm" :color="$unit->status->color()" inset="top bottom">
                            {{ $unit->status->label() }}
                        </flux:badge>
                    </div>
                </x-flux::card.body>
            </x-flux::card>
        </div>

        <!-- Tabs & Content -->
        <div class="space-y-6">
            <div class="border-b border-zinc-200">
                <div class="flex gap-6">
                    <button wire:click="$set('tab', 'overview')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $tab === 'overview' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}">
                        Vue d'ensemble
                    </button>
                    <button wire:click="$set('tab', 'maintenance')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $tab === 'maintenance' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}">
                        Maintenance
                    </button>
                    <button wire:click="$set('tab', 'history')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $tab === 'history' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}">
                        Historique
                    </button>
                </div>
            </div>

            <div>
                @if($tab === 'overview')
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Current Lease / Tenant Info -->
                        <div class="lg:col-span-2 space-y-6">
                            <x-flux::card>
                                <x-flux::card.header icon="user-group" title="Locataire Actuel"
                                    subtitle="Information sur l'occupation actuelle." />
                                <x-flux::card.body>
                                    @if($unit->activeLease)
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <div class="text-sm font-medium text-zinc-500 mb-1">Nom</div>
                                                <div class="text-base font-semibold text-zinc-900">
                                                    {{ $unit->activeLease->renter->first_name }}
                                                    {{ $unit->activeLease->renter->last_name }}
                                                </div>
                                                <div class="text-sm text-zinc-500">{{ $unit->activeLease->renter->email }}</div>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-zinc-500 mb-1">Période du bail</div>
                                                <div class="text-base text-zinc-900">
                                                    Du {{ $unit->activeLease->start_date->isoFormat('D MMM YYYY') }}
                                                    @if($unit->activeLease->end_date)
                                                        au {{ $unit->activeLease->end_date->isoFormat('D MMM YYYY') }}
                                                    @else
                                                        (Indéterminé)
                                                    @endif
                                                </div>
                                                <div class="text-sm text-zinc-500 mt-1">
                                                    Dépôt de garantie : <span
                                                        class="font-medium text-zinc-900">{{ Number::currency($unit->activeLease->deposit_amount, 'XOF') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <flux:separator />

                                        <div class="flex justify-end">
                                            <flux:button size="sm" icon="document-text"
                                                href="{{ route('tenant.leases.index', ['search' => $unit->activeLease->renter->last_name]) }}">
                                                Voir le bail</flux:button>
                                        </div>
                                    @else
                                        <div
                                            class="text-center py-12 bg-zinc-50 rounded-xl border border-dashed border-zinc-200">
                                            <div
                                                class="size-12 rounded-full bg-zinc-100 flex items-center justify-center mx-auto mb-3 text-zinc-400">
                                                <flux:icon.key class="size-6" />
                                            </div>
                                            <h3 class="text-sm font-medium text-zinc-900">Aucun locataire</h3>
                                            <p class="text-sm text-zinc-500 mt-1 mb-4">Cette unité est actuellement vacante.</p>
                                            <flux:button variant="primary" icon="plus"
                                                wire:click="$dispatch('open-modal', { name: 'create-lease', unit_id: '{{ $unit->id }}' })">
                                                Créer un nouveau bail</flux:button>
                                        </div>
                                    @endif

                                </x-flux::card.body>
                            </x-flux::card>
                        </div>

                        <!-- Sidebar Stats / Extra Info -->
                        <div class="space-y-6">
                            <!-- Can add more widgets here like "Quick Actions" or "Notes" -->
                            <x-flux::card class="bg-indigo-50 border-indigo-100">
                                <x-flux::card.header icon="banknotes" title="Rentabilité Annuelle"
                                    subtitle="Revenu potentiel" />
                                <x-flux::card.body>
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600">
                                            <flux:icon.banknotes class="size-5" />
                                        </div>
                                        <h3 class="font-medium text-indigo-900">Rentabilité Annuelle</h3>
                                    </div>
                                    <div class="text-2xl font-bold text-indigo-700">
                                        {{ Number::currency($unit->rent_amount * 12, 'XOF') }}
                                    </div>
                                    <div class="text-sm text-indigo-600/80">Revenu potentiel</div>
                                </x-flux::card.body>
                            </x-flux::card>
                        </div>
                    </div>
                @elseif($tab === 'maintenance')
                    <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm">
                        <x-flux::card.header icon="wrench-screwdriver" title="Maintenance"
                            subtitle="Historique des demandes" class="bg-zinc-50 border-b border-zinc-100 py-3">
                            <x-slot:cardActions>
                                <flux:button size="sm" icon="plus"
                                    wire:click="$dispatch('open-modal', { name: 'create-maintenance', unit_id: '{{ $unit->id }}' })">
                                    Signaler</flux:button>
                            </x-slot:cardActions>
                        </x-flux::card.header>

                        <x-flux::table :paginate="$this->maintenanceRequests">
                            <x-flux::table.columns>
                                <x-flux::table.column>Titre</x-flux::table.column>
                                <x-flux::table.column>Priorité</x-flux::table.column>
                                <x-flux::table.column>Statut</x-flux::table.column>
                                <x-flux::table.column>Date</x-flux::table.column>
                            </x-flux::table.columns>

                            <x-flux::table.rows>
                                @forelse($this->maintenanceRequests as $request)
                                    <x-flux::table.row :key="$request->id">
                                        <x-flux::table.cell class="font-medium">{{ $request->title }}</x-flux::table.cell>
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
                                        <x-flux::table.cell class="text-zinc-500">{{ $request->created_at->diffForHumans() }}
                                        </x-flux::table.cell>
                                    </x-flux::table.row>
                                @empty
                                    <x-flux::table.row>
                                        <x-flux::table.cell colspan="4" class="text-center py-8 text-zinc-500">Aucune demande de
                                            maintenance.</x-flux::table.cell>
                                    </x-flux::table.row>
                                @endforelse
                            </x-flux::table.rows>
                        </x-flux::table>
                    </x-flux::card>
                @elseif($tab === 'history')
                    <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm">
                        <x-flux::card.header icon="clock" title="Historique des Baux" subtitle="Baux passés et actuels"
                            class="bg-zinc-50 border-b border-zinc-100 py-3" />

                        <x-flux::table :paginate="$this->leases">
                            <x-flux::table.columns>
                                <x-flux::table.column>Locataire</x-flux::table.column>
                                <x-flux::table.column>Période</x-flux::table.column>
                                <x-flux::table.column>Loyer</x-flux::table.column>
                                <x-flux::table.column>Statut</x-flux::table.column>
                            </x-flux::table.columns>

                            <x-flux::table.rows>
                                @forelse($this->leases as $lease)
                                    <x-flux::table.row :key="$lease->id">
                                        <x-flux::table.cell>
                                            <div class="font-medium">{{ $lease->renter->first_name }}
                                                {{ $lease->renter->last_name }}
                                            </div>
                                        </x-flux::table.cell>
                                        <x-flux::table.cell>
                                            {{ $lease->start_date->format('d/m/Y') }} -
                                            {{ $lease->end_date ? $lease->end_date->format('d/m/Y') : 'En cours' }}
                                        </x-flux::table.cell>
                                        <x-flux::table.cell>{{ Number::currency($lease->rent_amount, 'XOF') }}</x-flux::table.cell>
                                        <x-flux::table.cell>
                                            @if($lease->status === 'active')
                                                <flux:badge color="green" size="sm" inset="top bottom">Actif</flux:badge>
                                            @else
                                                <flux:badge color="zinc" size="sm" inset="top bottom">Terminé</flux:badge>
                                            @endif
                                        </x-flux::table.cell>
                                    </x-flux::table.row>
                                @empty
                                    <x-flux::table.row>
                                        <x-flux::table.cell colspan="4" class="text-center py-8 text-zinc-500">Aucun historique de
                                            bail.</x-flux::table.cell>
                                    </x-flux::table.row>
                                @endforelse
                            </x-flux::table.rows>
                        </x-flux::table>
                    </x-flux::card>
                @endif
            </div>
        </div>

        <!-- Modals -->
        <livewire:pages::tenant.units.modals.edit />
        <livewire:pages::tenant.leases.modals.create />
        <livewire:pages::tenant.maintenance.modals.create />

    </x-layouts::content>
</div>