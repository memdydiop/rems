<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Unit;
use App\Models\Property;
use App\Enums\{UnitStatus, UnitType};
use Flux\Flux;

new
    #[Layout('layouts.app', ['title' => 'Gestion des Unités'])]
    class extends Component {
    use WithPagination;

    public $search = '';
    public $sortCol = 'created_at';
    public $sortAsc = false;
    public $status = 'all';
    public $propertyIdFilter = 'all';
    public $typeFilter = 'all';
    public $maintenanceFilter = 'all';

    // Form State
    public $unitId = null;
    public $name = '';
    public $property_id = '';
    public $type = ''; // Added type

    // Modal State
    public $modalOpen = false;

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'property_id' => 'required|exists:properties,id',
            'type' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'property_id.required' => 'La propriété est obligatoire.',
            'type.required' => 'Le type est obligatoire.',
        ];
    }

    #[Computed]
    public function stats()
    {
        $total = Unit::count();
        $occupied = Unit::has('leases', '>=', 1, 'and', function($query) {
            $query->where('status', 'active');
        })->count();
        
        return [
            'total' => $total,
            'occupied' => $occupied,
            'vacant' => $total - $occupied,
            'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100) : 0,
        ];
    }

    #[Computed]
    public function units()
    {
        return Unit::query()
            ->with(['property'])
            ->when($this->status !== 'all', function ($query) {
                if ($this->status === 'occupied') {
                    $query->whereHas('leases', fn($q) => $q->where('status', 'active'));
                } else {
                    $query->whereDoesntHave('leases', fn($q) => $q->where('status', 'active'));
                }
            })
            ->when($this->propertyIdFilter !== 'all', function ($query) {
                $query->where('property_id', $this->propertyIdFilter);
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $query->where('type', $this->typeFilter);
            })
            ->when($this->maintenanceFilter !== 'all', function ($query) {
                if ($this->maintenanceFilter === 'maintenance') {
                    $query->whereHas('maintenanceRequests', function ($q) {
                        $q->whereIn('status', [\App\Enums\MaintenanceStatus::Pending, \App\Enums\MaintenanceStatus::InProgress]);
                    });
                } else {
                    $query->whereDoesntHave('maintenanceRequests', function ($q) {
                        $q->whereIn('status', [\App\Enums\MaintenanceStatus::Pending, \App\Enums\MaintenanceStatus::InProgress]);
                    });
                }
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('property', function ($q) {
                            $q->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc')
            ->paginate(10);
    }

    #[Computed]
    public function properties()
    {
        return Property::orderBy('name')->get();
    }

    public function sortBy($col)
    {
        if ($this->sortCol === $col) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortCol = $col;
            $this->sortAsc = true;
        }
    }

    public function create()
    {
        $this->resetForm();
        $this->modalOpen = true;
    }

    public function edit(Unit $unit)
    {
        $this->unitId = $unit->id;
        $this->name = $unit->name;
        $this->property_id = $unit->property_id;
        $this->type = $unit->type->value;
        $this->modalOpen = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->unitId) {
            $unit = Unit::find($this->unitId);
            $unit->update([
                'name' => $this->name,
                'property_id' => $this->property_id,
                'type' => $this->type,
            ]);
            Flux::toast('Unité mise à jour avec succès.', 'success');
        } else {
            Unit::create([
                'name' => $this->name,
                'property_id' => $this->property_id,
                'type' => $this->type,
            ]);
            Flux::toast('Unité créée avec succès.', 'success');
        }

        $this->modalOpen = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        $unit = Unit::find($id);
        if ($unit) {
            $unit->delete();
            Flux::toast('Unité supprimée.', 'success');
        }
    }

    public function resetForm()
    {
        $this->reset(['unitId', 'name', 'property_id', 'type']);
    }
};
?>

<div>
    <x-layouts::content heading="Unités" subheading="Gérez les lots, appartements ou bureaux de vos propriétés.">
        
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="create">
                Nouveau Lot
            </flux:button>
        </x-slot:actions>

        <!-- Stats Widgets -->
        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-stats-card title="Total Unités" :value="$this->stats['total']" icon="key" color="indigo" />
            <x-stats-card title="Unités Occupées" :value="$this->stats['occupied']" icon="user-group" color="emerald" />
            <x-stats-card title="Taux d'Occupation" :value="$this->stats['occupancy_rate'] . '%'" icon="chart-pie" color="orange" />
        </div>

        <x-flux::card class="overflow-hidden">
            <x-flux::card.header 
                icon="key" 
                title="Liste des Unités"
                subtitle="Vue d'ensemble de vos lots locatifs" />

            <x-flux::table :paginate="$this->units" search linesPerPage>
                <x-slot:selectable>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:select wire:model.live="status" size="sm" class="w-full md:w-36">
                            <flux:select.option value="all">Tous statuts</flux:select.option>
                            <flux:select.option value="vacant">Vacant</flux:select.option>
                            <flux:select.option value="occupied">Occupé</flux:select.option>
                        </flux:select>

                        <flux:select wire:model.live="maintenanceFilter" size="sm" class="w-full md:w-40">
                            <flux:select.option value="all">Toutes maintenances</flux:select.option>
                            <flux:select.option value="maintenance">En maintenance</flux:select.option>
                            <flux:select.option value="ready">Prêt à louer</flux:select.option>
                        </flux:select>

                        <flux:select wire:model.live="propertyIdFilter" size="sm" class="w-full md:w-48">
                            <flux:select.option value="all">Toutes propriétés</flux:select.option>
                            @foreach($this->properties as $property)
                                <flux:select.option value="{{ $property->id }}">{{ $property->name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="typeFilter" size="sm" class="w-full md:w-40">
                            <flux:select.option value="all">Tous types</flux:select.option>
                            @foreach(UnitType::cases() as $typeCase)
                                <flux:select.option value="{{ $typeCase->value }}">{{ $typeCase->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </x-slot:selectable>

                <x-flux::table.columns>
                    <x-flux::table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('name')">Nom</x-flux::table.column>
                    <x-flux::table.column>Propriété</x-flux::table.column>
                    <x-flux::table.column>Type</x-flux::table.column> <!-- Added Type -->
                    <x-flux::table.column sortable :sorted="$sortCol === 'status'"
                        :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('status')">Statut</x-flux::table.column>
                    <x-flux::table.column>Actions</x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @foreach ($this->units as $unit)
                        <x-flux::table.row :key="$unit->id">
                            <x-flux::table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded bg-zinc-100 flex items-center justify-center text-zinc-500">
                                        <flux:icon name="key" class="size-4" />
                                    </div>
                                    <a href="{{ route('tenant.units.show', $unit) }}" class="font-medium text-zinc-900 hover:underline hover:text-indigo-600 transition-colors">{{ $unit->name }}</a>
                                </div>
                            </x-flux::table.cell>

                            <x-flux::table.cell>
                                <flux:badge color="zinc" size="sm" icon="home">{{ $unit->property->name }}</flux:badge>
                            </x-flux::table.cell>

                            <x-flux::table.cell>
                                <flux:badge size="sm" :color="$unit->type->color()" inset="top bottom">
                                    {{ $unit->type->label() }}
                                </flux:badge>
                            </x-flux::table.cell>

                            <x-flux::table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:badge :color="$unit->status->color()" size="sm" inset="top bottom">
                                        {{ $unit->status->label() }}
                                    </flux:badge>
                                    
                                    @if($unit->isUnderMaintenance())
                                        <flux:badge color="orange" size="sm" icon="wrench-screwdriver" inset="top bottom">
                                            Maintenance
                                        </flux:badge>
                                    @endif
                                </div>
                            </x-flux::table.cell>

                            <x-flux::table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" :href="route('tenant.units.show', $unit)">Voir</flux:menu.item>
                                        <flux:menu.item icon="pencil" wire:click="edit('{{ $unit->id }}')">Modifier
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete('{{ $unit->id }}')"
                                            wire:confirm="Êtes-vous sûr de vouloir supprimer cette unité ?">Supprimer
                                        </flux:menu.item>

                                    </flux:menu>
                                </flux:dropdown>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforeach
                </x-flux::table.rows>
            </x-flux::table>

        </x-flux::card>
    </x-layouts::content>

    <!-- Create/Edit Modal -->
    <flux:modal wire:model="modalOpen" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $unitId ? 'Modifier l\'unité' : 'Nouveau Lot' }}</flux:heading>
                <flux:subheading>Remplissez les informations ci-dessous.</flux:subheading>
            </div>

            <form wire:submit="save" class="space-y-6">
                <flux:select wire:model="property_id" label="Propriété" placeholder="Choisir une propriété...">
                    @foreach ($this->properties as $property)
                        <flux:select.option value="{{ $property->id }}">{{ $property->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="name" label="Nom du lot" placeholder="Ex: Appartement A1, Bureau 204" />

                <flux:select wire:model="type" label="{{ __('Type') }}" placeholder="Choisir un type...">
                    <flux:select.option value="" disabled>Sélectionner</flux:select.option>
                    
                    <optgroup label="Résidentiel">
                        @foreach (UnitType::cases() as $type)
                            @if(in_array($type->value, ['studio', 'f1', 'f2', 'f3', 'f4', 'f5_plus', 'room', 'entire_house']))
                                <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                            @endif
                        @endforeach
                    </optgroup>

                    <optgroup label="Commercial">
                        @foreach (UnitType::cases() as $type)
                            @if(in_array($type->value, ['office', 'retail', 'restaurant', 'storage']))
                                <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                            @endif
                        @endforeach
                    </optgroup>

                    <optgroup label="Autre">
                        @foreach (UnitType::cases() as $type)
                            @if(in_array($type->value, ['parking', 'garage', 'land']))
                                <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                            @endif
                        @endforeach
                    </optgroup>
                </flux:select>

                <flux:input wire:model="name" label="Nom du lot" placeholder="Ex: Appartement A1, Bureau 204" />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Annuler</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Enregistrer</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>