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
                                    </a>
                                </p>
                            </div>

                            @if($unit->activeLease)
                                <!-- Renter Profile Mini-Card -->
                                <div
                                    class="flex items-center gap-4 p-4 rounded-2xl bg-white/10 border border-white/10 backdrop-blur-xs max-w-md">
                                    <flux:avatar size="lg" :name="$unit->activeLease->renter->full_name"
                                        class="ring-2 ring-white/20" />
                                    <div class="min-w-0">
                                        <p class="text-white font-bold truncate">{{ $unit->activeLease->renter->full_name }}
                                        </p>
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center gap-x-3 text-xs text-indigo-100">
                                            <a href="mailto:{{ $unit->activeLease->renter->email }}"
                                                class="hover:text-white flex items-center gap-1">
                                                <flux:icon.envelope class="size-3" />
                                                <span
                                                    class="truncate">{{ $unit->activeLease->renter->email ?: 'Pas d\'email' }}</span>
                                            </a>
                                            <a href="tel:{{ $unit->activeLease->renter->phone }}"
                                                class="hover:text-white flex items-center gap-1">
                                                <flux:icon.phone class="size-3" />
                                                <span>{{ $unit->activeLease->renter->phone }}</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex gap-3">
                                    <flux:button variant="ghost"
                                        class="bg-white/10! text-white! hover:bg-white/20! border-transparent!"
                                        icon="pencil-square" wire:click="$dispatch('edit-unit', { id: '{{ $unit->id }}' })">
                                        Modifier l'unité
                                    </flux:button>
                                    @if($unit->status->value === 'vacant')
                                        <flux:button icon="document-plus" variant="filled"
                                            :disabled="$unit->isUnderMaintenance()"
                                            class="bg-white! text-indigo-600! font-bold hover:bg-indigo-50!"
                                            wire:click="$dispatch('open-modal', { name: 'create-lease', unit_id: '{{ $unit->id }}' })">
                                            Ajouter un bail
                                        </flux:button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right: Lease Quick Metrics (If Occupied) -->
                    @if($unit->activeLease)
                        <div class="flex flex-wrap gap-3 sm:gap-4 w-full lg:w-auto lg:max-w-xl lg:justify-end">
                            <div
                                class="flex flex-col p-4 rounded-2xl bg-white/10 border border-white/10 backdrop-blur-xs min-w-35 flex-1 sm:flex-none">
                                <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold mb-1">Loyer +
                                    Charges</span>
                                <span class="text-white text-lg font-bold">
                                    {{ Number::currency(($unit->activeLease->rent_amount + $unit->activeLease->charges_amount), 'XOF') }}
                                </span>
                            </div>
                            <div
                                class="flex flex-col p-4 rounded-2xl bg-white/10 border border-white/10 backdrop-blur-xs min-w-35 flex-1 sm:flex-none">
                                <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold mb-1">Dépôt</span>
                                <span class="text-white text-lg font-bold">
                                    {{ Number::currency($unit->activeLease->deposit_amount, 'XOF') }}
                                </span>
                            </div>
                            <div
                                class="flex flex-col p-4 rounded-2xl bg-white/10 border border-white/10 backdrop-blur-xs min-w-35 flex-1 sm:flex-none">
                                <span
                                    class="text-2xs text-indigo-200 uppercase tracking-widest font-bold mb-1">Avance</span>
                                <span class="text-white text-lg font-bold">
                                    {{ Number::currency($unit->activeLease->advance_amount ?: 0, 'XOF') }}
                                </span>
                            </div>
                            <div
                                class="flex flex-col p-4 rounded-2xl bg-white/10 border border-white/10 backdrop-blur-xs min-w-35 flex-1 sm:flex-none">
                                <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold mb-1">Type</span>
                                <span
                                    class="text-white text-lg font-bold capitalize">{{ $unit->activeLease->lease_type ?: 'Standard' }}</span>
                            </div>
                            <div
                                class="flex flex-col p-4 rounded-2xl bg-white/10 border border-white/10 backdrop-blur-xs min-w-35 flex-1 sm:flex-none">
                                <span class="text-2xs text-indigo-200 uppercase tracking-widest font-bold mb-1">Fin du
                                    bail</span>
                                <span class="text-white text-lg font-bold">
                                    @if($unit->activeLease->end_date)
                                        {{ $unit->activeLease->end_date->isoFormat('D MMM YY') }}
                                    @else
                                        Sans fin
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                @if($unit->activeLease)
                    <div class="mt-8 flex justify-end gap-3">
                        <flux:button variant="ghost" class="bg-white/10! text-white! hover:bg-white/20! border-transparent!"
                            icon="pencil-square" wire:click="$dispatch('edit-unit', { id: '{{ $unit->id }}' })">
                            Modifier l'unité
                        </flux:button>
                        <flux:button variant="ghost" class="bg-white/10! text-white! hover:bg-white/20! border-transparent!"
                            icon="pencil"
                            wire:click="$dispatch('open-modal', { name: 'edit-lease', lease_id: '{{ $unit->activeLease->id }}' })">
                            Modifier le bail
                        </flux:button>
                    </div>
                @endif
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
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left: Info Details -->
                        <x-flux::card>
                            <x-flux::card.header icon="information-circle" title="Détails de l'unité" />
                            <x-flux::card.body class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-1">Surface
                                        </div>
                                        <div class="text-sm text-zinc-900 font-medium">
                                            {{ $unit->surface_area ? $unit->surface_area . ' m²' : 'Non renseignée' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-1">Loyer
                                            de base</div>
                                        <div class="text-sm text-zinc-900 font-medium">
                                            {{ $unit->rent_amount ? Number::currency($unit->rent_amount, 'XOF') : 'Non renseigné' }}
                                        </div>
                                    </div>
                                </div>
                                @if($unit->notes)
                                    <flux:separator variant="subtle" />
                                    <div>
                                        <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider mb-1">Notes</div>
                                        <div class="text-sm text-zinc-700 whitespace-pre-line leading-relaxed">
                                            {{ $unit->notes }}
                                        </div>
                                    </div>
                                @endif
                            </x-flux::card.body>
                        </x-flux::card>

                        <!-- Right: Financial / Quick Actions -->
                        <x-flux::card class="bg-indigo-50/50 border-indigo-100 shadow-sm">
                            <x-flux::card.body class="space-y-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="size-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                        <flux:icon.banknotes class="size-6" />
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-indigo-900">Rentabilité Annuelle</h3>
                                        <p class="text-xs text-indigo-600/80">Revenu potentiel basé sur le bail actif</p>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <div class="text-3xl font-black text-indigo-700">
                                        {{ $unit->activeLease ? Number::currency($unit->activeLease->rent_amount * 12, 'XOF') : '—' }}
                                    </div>
                                    @if($unit->activeLease)
                                        <p class="text-xs text-indigo-500">Calculé sur
                                            {{ Number::currency($unit->activeLease->rent_amount, 'XOF') }} / mois
                                        </p>
                                    @endif
                                </div>

                                @if($unit->activeLease)
                                    <flux:separator variant="subtle" class="bg-indigo-200/50" />
                                    <div class="flex gap-2">
                                        <flux:button size="sm" icon="document-text" variant="ghost"
                                            class="text-indigo-600! hover:bg-indigo-100!"
                                            href="{{ route('tenant.leases.index', ['search' => $unit->activeLease->renter->last_name]) }}">
                                            Gérer le bail
                                        </flux:button>
                                    </div>
                                @endif
                            </x-flux::card.body>
                        </x-flux::card>
                    </div>
                @elseif($tab === 'maintenance')
                    <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm">
                        <x-flux::card.header icon="wrench-screwdriver" title="Maintenance"
                            subtitle="Historique des demandes" class="bg-zinc-50 border-b border-zinc-100 py-3">
                            <x-slot:cardActions>
                                <flux:button variant="primary" size="sm" icon="plus" class="w-full sm:w-auto"
                                    wire:click="$dispatch('open-modal', { name: 'unit-create-maintenance', unit_id: '{{ $unit->id }}' })">
                                    Signaler</flux:button>
                            </x-slot:cardActions>
                        </x-flux::card.header>

                        <x-flux::table :paginate="$this->maintenanceRequests">
                            <x-flux::table.columns>
                                <x-flux::table.column>Titre</x-flux::table.column>
                                <x-flux::table.column>Priorité</x-flux::table.column>
                                <x-flux::table.column>Statut</x-flux::table.column>
                                <x-flux::table.column>Date</x-flux::table.column>
                                <x-flux::table.column align="right"></x-flux::table.column>
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
                                        <x-flux::table.cell align="right">
                                            <flux:dropdown>
                                                <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                                <flux:menu>
                                                    @if($request->isEditable())
                                                        @foreach(\App\Enums\MaintenanceStatus::cases() as $s)
                                                            @if($s !== $request->status)
                                                                                        <flux:menu.item
                                                                                            wire:click="updateMaintenanceStatus('{{ $request->id }}', '{{ $s->value }}')"
                                                                                            icon="{{ match ($s) {
                                                                    \App\Enums\MaintenanceStatus::Pending => 'clock',
                                                                    \App\Enums\MaintenanceStatus::InProgress => 'arrow-path',
                                                                    \App\Enums\MaintenanceStatus::Resolved => 'check-circle',
                                                                    \App\Enums\MaintenanceStatus::Cancelled => 'x-circle',
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
                                                            wire:confirm="Voulez-vous réouvrir ce ticket ?">
                                                            Réouvrir
                                                        </flux:menu.item>
                                                        <flux:menu.separator />
                                                        <flux:menu.item icon="clock" disabled>
                                                            {{ $request->status->label() }}
                                                        </flux:menu.item>
                                                    @endif
                                                </flux:menu>
                                            </flux:dropdown>
                                        </x-flux::table.cell>
                                    </x-flux::table.row>
                                @empty
                                    <x-flux::table.row>
                                        <x-flux::table.cell colspan="5" class="text-center py-8 text-zinc-500">Aucune demande de
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
                                            <div class="flex items-center gap-3">
                                                <flux:avatar size="xs" :name="$lease->renter->full_name"
                                                    class="ring-2 ring-white shadow-sm" />
                                                <span class="font-medium text-zinc-900">{{ $lease->renter->full_name }}</span>
                                            </div>
                                        </x-flux::table.cell>
                                        <x-flux::table.cell>
                                            {{ $lease->start_date->format('d/m/Y') }} -
                                            {{ $lease->end_date ? $lease->end_date->format('d/m/Y') : 'En cours' }}
                                        </x-flux::table.cell>
                                        <x-flux::table.cell>{{ Number::currency($lease->rent_amount, 'XOF') }}</x-flux::table.cell>
                                        <x-flux::table.cell>
                                            <flux:badge :color="$lease->status->color()" size="sm" inset="top bottom">
                                                {{ $lease->status->label() }}
                                            </flux:badge>
                                        </x-flux::table.cell>
                                    </x-flux::table.row>
                                @empty
                                    <x-flux::table.row>
                                        <x-flux::table.cell colspan="4" class="text-center py-8 text-zinc-500">Aucun historique
                                            de
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
        <livewire:pages::tenant.leases.modals.edit />
        <livewire:pages::tenant.maintenance.units.create-modal />
        <livewire:pages::tenant.maintenance.units.edit-modal />
        <livewire:pages::tenant.renters.modals.create />

    </x-layouts::content>
</div>