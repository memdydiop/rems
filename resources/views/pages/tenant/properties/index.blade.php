<?php

use App\Models\Property;
use App\Models\Unit;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app', ['title' => 'Propriétés'])] class extends Component {
    use WithPagination;

    public $search = '';
    public $status = 'all';
    public $perPage = 10;
    public $sortCol = 'name';
    public $sortAsc = true;

    public function sortBy($column)
    {
        if ($this->sortCol === $column) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortCol = $column;
            $this->sortAsc = true;
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function with()
    {
        $query = Property::query()
            ->withCount('units')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('address', 'like', '%' . $this->search . '%'))
            ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
            ->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc');

        // Optimized: Unit stats using Eloquent
        $totalUnits = Unit::count();
        $occupiedUnits = Unit::whereHas('leases', function ($q) {
            $q->where('status', 'active');
        })->count();
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

        return [
            'properties' => $query->paginate($this->perPage),
            'totalProperties' => Property::count(),
            'totalUnits' => $totalUnits,
            'occupancyRate' => $occupancyRate,
        ];
    }

    public function delete($id)
    {
        $property = Property::find($id);
        if ($property) {
            $property->delete();
            Flux\Flux::toast('Propriété supprimée avec succès.', 'success');
        }
    }
};
?>

<div>
    <x-layouts::content heading="Propriétés" subheading="Gérez votre patrimoine immobilier efficacement.">

        <x-slot name="actions">
            <flux:button icon="plus" variant="primary"
                wire:click="$dispatch('open-modal', { name: 'create-property' })">
                Ajouter une Propriété
            </flux:button>
        </x-slot>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-flux::card bg="bg-blue-50" padding="p-6"
                class="relative border-0 overflow-hidden h-full group hover:shadow-[0_8px_30px_-4px_rgba(59,130,246,0.1)] transition-all duration-300 rounded-[20px]">
                <div class="flex flex-col h-full relative z-10">
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-zinc-500 font-medium text-sm">Total Propriétés</span>
                            <div class="text-3xl font-bold text-zinc-900 tracking-tight">
                                {{ $totalProperties }}
                            </div>
                        </div>
                        <div
                            class="bg-blue-400/20 p-2.5 rounded backdrop-blur-sm group-hover:scale-105 transition-transform">
                            <flux:icon name="home" variant="solid" class="w-5 h-5 text-blue-500" />
                        </div>
                    </div>
                </div>
                <!-- Pattern -->
                <img src="{{ asset('img/widget-bg-abstract.png') }}"
                    class="absolute inset-0 w-64 pointer-events-none group-hover:rotate-12 transition-transform duration-700"
                    alt="" />
            </x-flux::card>

            <x-flux::card bg="bg-emerald-50" padding="p-6"
                class="relative border-0 overflow-hidden h-full group hover:shadow-[0_8px_30px_-4px_rgba(16,185,129,0.1)] transition-all duration-300 rounded-[20px]">
                <div class="flex flex-col h-full relative z-10">
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-zinc-500 font-medium text-sm">Total Unités</span>
                            <div class="text-3xl font-bold text-zinc-900 tracking-tight">
                                {{ $totalUnits }}
                            </div>
                        </div>
                        <div
                            class="bg-emerald-400/20 p-2.5 rounded backdrop-blur-sm group-hover:scale-105 transition-transform">
                            <flux:icon name="key" variant="solid" class="w-5 h-5 text-emerald-500" />
                        </div>
                    </div>
                </div>
                <!-- Pattern -->
                <img src="{{ asset('img/widget-bg-abstract.png') }}"
                    class="absolute inset-0 w-64 pointer-events-none group-hover:rotate-12 transition-transform duration-700"
                    alt="" />
            </x-flux::card>

            <x-flux::card bg="bg-orange-50" padding="p-6"
                class="relative border-0 overflow-hidden h-full group hover:shadow-[0_8px_30px_-4px_rgba(249,115,22,0.1)] transition-all duration-300 rounded-[20px]">
                <div class="flex flex-col h-full relative z-10">
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-zinc-500 font-medium text-sm">Taux d'Occupation</span>
                            <div class="text-3xl font-bold text-zinc-900 tracking-tight">
                                {{ $occupancyRate }}%
                            </div>
                        </div>
                        <div
                            class="bg-orange-400/20 p-2.5 rounded backdrop-blur-sm group-hover:scale-105 transition-transform">
                            <flux:icon name="chart-pie" variant="solid" class="w-5 h-5 text-orange-500" />
                        </div>
                    </div>
                </div>
                <!-- Pattern -->
                <img src="{{ asset('img/widget-bg-abstract.png') }}"
                    class="absolute inset-0 w-64 pointer-events-none group-hover:rotate-12 transition-transform duration-700"
                    alt="" />
            </x-flux::card>
        </div>

        <x-flux::card class="p-0 overflow-hidden">

            <x-flux::card.header :title="'Propriétés'" :subtitle="'Liste de toutes vos propriétés'" :icon="'home'" />

            <x-flux::table :paginate="$properties" search linesPerPage>
                <x-slot:selectable>
                    <flux:select wire:model.live="status" size="sm" class="w-full md:w-40">
                        <flux:select.option value="all">Tous statut</flux:select.option>
                        @foreach(\App\Enums\PropertyStatus::cases() as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </x-slot:selectable>

                <x-flux::table.columns>
                    <x-flux::table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('name')">Propriété</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'type'" :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('type')">Type</x-flux::table.column>
                    <x-flux::table.column align="end" sortable :sorted="$sortCol === 'units_count'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('units_count')">Unités</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'status'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('status')">Statut</x-flux::table.column>
                    <x-flux::table.column align="end">Actions</x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @forelse ($properties as $property)
                        <x-flux::table.row :key="$property->id">
                            <x-flux::table.cell>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="size-10 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0 border border-indigo-100">
                                        <flux:icon.home-modern class="size-5 text-indigo-600" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-zinc-900">{{ $property->name }}</span>
                                        <span
                                            class="text-xs text-zinc-500">{{ $property->address ?? 'Adresse non renseignée' }}</span>
                                    </div>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:badge size="sm" :color="$property->type->color()" inset="top bottom">
                                    {{ $property->type->label() }}
                                </flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell align="end">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-800">
                                    {{ $property->units_count }}
                                </span>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:badge size="sm" :color="$property->status->color()" inset="top bottom">
                                    {{ $property->status->label() }}
                                </flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell align="end">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" href="{{ route('tenant.properties.show', $property) }}"
                                            wire:navigate>Détails</flux:menu.item>
                                        <flux:menu.item icon="pencil-square"
                                            wire:click="$dispatch('open-modal', { name: 'edit-property', property: '{{ $property->id }}' })">
                                            Modifier</flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger"
                                            wire:click="delete('{{ $property->id }}')"
                                            wire:confirm="Êtes-vous sûr de vouloir supprimer cette propriété ?">Supprimer
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @empty
                        <x-flux::table.row>
                            <x-flux::table.cell colspan="5">
                                <div class="text-center py-12">
                                    <div
                                        class="inline-flex items-center justify-center size-12 rounded-full bg-zinc-50 mb-4 border border-zinc-100">
                                        <flux:icon.home class="size-6 text-zinc-400" />
                                    </div>
                                    <h3 class="text-base font-medium text-zinc-900">Aucune propriété</h3>
                                    <p class="text-sm text-zinc-500 mt-1 max-w-xs mx-auto">Commencez par ajouter votre
                                        première propriété immobilière.</p>
                                    <div class="mt-4">
                                        <flux:button icon="plus" size="sm" variant="primary"
                                            wire:click="$dispatch('open-modal', { name: 'create-property' })">
                                            Créer une propriété
                                        </flux:button>
                                    </div>
                                </div>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>

        </x-flux::card>

    </x-layouts::content>

    <livewire:pages::tenant.properties.modals.create />
</div>