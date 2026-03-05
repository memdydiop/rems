<?php

use App\Models\Property;
use App\Models\Unit;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app', ['title' => 'Property Details'])] class extends Component {
    public Property $property;

    use Livewire\WithPagination;

    public $search = '';
    public $perPage = 10;
    public $tab = 'units';

    #[\Livewire\Attributes\On('lease-created')]
    #[\Livewire\Attributes\On('unit-updated')]
    public function refresh()
    {
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function mount(Property $property)
    {
        $this->property = $property;
    }

    public function deleteUnit($unitId)
    {
        $this->property->units()->where('id', $unitId)->delete();
        $this->js("Flux.toast('Unité supprimée avec succès.')");
    }

    public function with()
    {
        // Optimized calculations using direct database queries
        $unitIds = $this->property->units()->pluck('id');
        $monthlyRevenue = \App\Models\Lease::whereIn('unit_id', $unitIds)
            ->where('status', \App\Enums\LeaseStatus::Active)
            ->sum('rent_amount');
        $expectedRevenue = \App\Models\Lease::whereIn('unit_id', $unitIds)
            ->where('status', \App\Enums\LeaseStatus::Active)
            ->sum('rent_amount');
        $totalUnits = $this->property->units()->count();
        $occupiedUnits = $this->property->units()->whereHas('leases', function ($q) {
            $q->where('status', 'active');
        })->count();
        $vacantUnits = $totalUnits - $occupiedUnits;

        // Get paginated units for table
        $units = $this->property->units()
            ->with(['leases' => fn($q) => $q->where('status', 'active')->with('renter')])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('leases', function ($q) {
                            $q->where('status', 'active')
                                ->whereHas('renter', function ($q) {
                                    $q->where('first_name', 'like', '%' . $this->search . '%')
                                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                        ->orWhere('email', 'like', '%' . $this->search . '%');
                                });
                        });
                });
            })
            ->paginate($this->perPage);

        $maintenanceRequests = $this->property->maintenanceRequests()
            ->whereNull('unit_id')
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();

        $leases = \App\Models\Lease::whereIn('unit_id', $unitIds)
            ->with(['unit', 'renter'])
            ->latest()
            ->paginate($this->perPage);

        return [
            'units' => $units,
            'leases' => $leases,
            'monthlyRevenue' => $monthlyRevenue,
            'expectedRevenue' => $expectedRevenue,
            'vacantUnits' => $vacantUnits,
            'occupiedUnits' => $occupiedUnits,
            'occupancyRate' => $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0,
            'maintenanceRequests' => $maintenanceRequests,
        ];
    }
};
?>

<div>

    <x-layouts::content heading="{{ $property->name }}"
        subheading="{{ $property->address ?? 'Adresse non renseignée' }}">
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
                                    {{ $property->name }}
                                </h1>
                                <flux:badge size="sm" class="bg-white/20 text-white border-0 inset-0">
                                    {{ $property->type->label() }}
                                </flux:badge>
                                <flux:badge size="sm"
                                    :color="$property->status === \App\Enums\PropertyStatus::Active ? 'emerald' : 'orange'"
                                    class="border-0">{{ $property->status->label() }}</flux:badge>
                            </div>

                            <p class="text-indigo-100 flex items-center gap-2 text-lg">
                                <flux:icon.map-pin class="size-5 opacity-80" />
                                {{ $property->address ?? 'Adresse non renseignée' }}
                            </p>

                            <div class="flex items-center gap-6 mt-6">
                                <div class="flex flex-col">
                                    <span class="text-xs text-indigo-200 uppercase tracking-wider font-semibold">Ajouté
                                        le</span>
                                    <span
                                        class="text-white font-medium">{{ $property->created_at->format('d M Y') }}</span>
                                </div>
                                <div class="w-px h-8 bg-white/10"></div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-xs text-indigo-200 uppercase tracking-wider font-semibold">Dernière
                                        modif.</span>
                                    <span
                                        class="text-white font-medium">{{ $property->updated_at->diffForHumans() }}</span>
                                </div>
                                <div class="w-px h-8 bg-white/10"></div>
                                <div class="flex flex-col">
                                    <span class="text-xs text-indigo-200 uppercase tracking-wider font-semibold">Taux
                                        d'occupation</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-white font-medium">{{ $occupancyRate }}%</span>
                                        <div class="w-16 h-1.5 bg-white/20 rounded-full overflow-hidden">
                                            <div class="h-full {{ $occupancyRate >= 90 ? 'bg-emerald-400' : ($occupancyRate >= 50 ? 'bg-amber-400' : 'bg-red-400') }} rounded-full"
                                                style="width: {{ $occupancyRate }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <flux:button variant="ghost"
                            class="bg-white/10! text-white! hover:bg-white/20! border-transparent!" icon="pencil-square"
                            wire:click="$dispatch('open-modal', { name: 'edit-property', property: '{{ $property->id }}' })">
                            Modifier
                        </flux:button>
                        <flux:button variant="filled"
                            class="bg-white! text-indigo-600! hover:bg-indigo-50! border-transparent! shadow-md"
                            icon="plus" x-on:click="Flux.modal('create-unit').show()">
                            Ajouter Unité
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
                        <flux:icon.home class="size-6 text-blue-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-500">Unités</p>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-zinc-900">{{ $units->total() }}</h3>
                            <span
                                class="text-xs font-medium text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded-full">Total</span>
                        </div>
                    </div>
                </x-flux::card.body>
            </x-flux::card>


            <x-flux::card>
                <x-flux::card.body class="p-6 flex items-center gap-4">
                    <div class="size-12 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                        <flux:icon.users class="size-6 text-emerald-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-500">Occupées</p>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-zinc-900">{{ $occupiedUnits }}</h3>
                            <span class="text-xs font-medium text-zinc-500">/ {{ $units->count() }}</span>
                        </div>
                    </div>
                </x-flux::card.body>
            </x-flux::card>

            <x-flux::card>
                <x-flux::card.body class="p-6 flex items-center gap-4">
                    <div class="size-12 rounded-xl bg-violet-50 flex items-center justify-center shrink-0">
                        <flux:icon.banknotes class="size-6 text-violet-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-500">Revenu Mensuel</p>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-zinc-900">{{ Number::currency($monthlyRevenue, 'XOF') }}
                            </h3>
                        </div>
                    </div>
                </x-flux::card.body>
            </x-flux::card>
        </div>

        <!-- Main Content Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Units (2/3) -->
            <div class="lg:col-span-2">
                <!-- Tabs -->
                <div class="flex gap-1 border-b border-zinc-200">
                    <button wire:click="$set('tab', 'units')"
                        class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $tab === 'units' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}">
                        <flux:icon name="home" class="size-4" />
                        Unités
                        <flux:badge size="sm" color="zinc">{{ $units->total() }}</flux:badge>
                    </button>
                    <button wire:click="$set('tab', 'leases')"
                        class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $tab === 'leases' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}">
                        <flux:icon name="document-text" class="size-4" />
                        Baux
                        <flux:badge size="sm" color="zinc">{{ $leases->total() }}</flux:badge>
                    </button>
                </div>

                @if($tab === 'units')
                    <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm rounded-t-none border-t-0">
                        <x-flux::card.header icon="home" title="Unités Locatives" subtitle="Gestion des lots et occupation"
                            class="bg-zinc-50/50">
                            <x-slot:cardActions>
                                <flux:button variant="ghost" size="xs" x-on:click="Flux.modal('create-unit').show()">
                                    Ajouter Unité
                                </flux:button>
                            </x-slot:cardActions>
                        </x-flux::card.header>

                        <x-flux::table :paginate="$units" search linesPerPage>
                            <x-flux::table.columns>
                                <x-flux::table.column>Unité</x-flux::table.column>
                                <x-flux::table.column>Type</x-flux::table.column>
                                <x-flux::table.column>Loyer</x-flux::table.column>
                                <x-flux::table.column>Statut</x-flux::table.column>
                                <x-flux::table.column align="right">Actions</x-flux::table.column>
                            </x-flux::table.columns>
                            <x-flux::table.rows>
                                @forelse($units as $unit)
                                    <x-flux::table.row wire:key="{{ $unit->id }}">
                                        <x-flux::table.cell class="font-medium text-zinc-900">
                                            <a href="{{ route('tenant.units.show', $unit) }}" wire:navigate
                                                class="hover:text-indigo-600 transition-colors">
                                                {{ $unit->name }}
                                            </a>
                                        </x-flux::table.cell>
                                        <x-flux::table.cell>{{ $unit->type->label() }}</x-flux::table.cell>
                                        <x-flux::table.cell>
                                            @if($unit->activeLease)
                                                {{ Number::currency($unit->activeLease->rent_amount, 'XOF') }}
                                            @else
                                                <span class="text-zinc-400 italic">Non loué</span>
                                            @endif
                                        </x-flux::table.cell>
                                        <x-flux::table.cell>
                                            <div class="flex items-center gap-2">
                                                <flux:badge :color="$unit->status->color()" size="sm" inset="top bottom">
                                                    {{ $unit->status->label() }}
                                                </flux:badge>

                                                @if($unit->isUnderMaintenance())
                                                    <flux:badge color="orange" size="sm" icon="wrench-screwdriver"
                                                        inset="top bottom">
                                                        Maintenance
                                                    </flux:badge>
                                                @endif
                                            </div>
                                        </x-flux::table.cell>
                                        <x-flux::table.cell align="right">
                                            <flux:dropdown>
                                                <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                                <flux:menu>
                                                    <flux:menu.item icon="eye" :href="route('tenant.units.show', $unit)">
                                                        Voir détails
                                                    </flux:menu.item>

                                                    <flux:menu.item icon="pencil-square"
                                                        wire:click="$dispatch('edit-unit', { id: '{{ $unit->id }}' })">
                                                        Modifier
                                                    </flux:menu.item>

                                                    @if($unit->status->value === 'vacant')
                                                        <flux:menu.separator />
                                                        <flux:menu.item icon="document-text" :disabled="$unit->isUnderMaintenance()"
                                                            wire:click="$dispatch('open-modal', { name: 'create-lease', unit_id: '{{ $unit->id }}' })">
                                                            Ajouter un bail
                                                        </flux:menu.item>
                                                    @endif

                                                    <flux:menu.separator />
                                                    <flux:menu.item icon="trash" variant="danger"
                                                        wire:click="deleteUnit('{{ $unit->id }}')"
                                                        wire:confirm="Êtes-vous sûr de vouloir supprimer cette unité ?">
                                                        Supprimer
                                                    </flux:menu.item>
                                                </flux:menu>
                                            </flux:dropdown>
                                        </x-flux::table.cell>
                                    </x-flux::table.row>
                                @empty
                                    <x-flux::table.row>
                                        <x-flux::table.cell colspan="5" class="text-center text-zinc-500 py-10">
                                            <flux:icon.home class="size-10 text-zinc-300 mx-auto mb-3" />
                                            <p class="text-lg font-semibold">Aucune unité locative</p>
                                            <p class="text-sm">Commencez par ajouter votre première unité.</p>
                                            <flux:button class="mt-4" x-on:click="Flux.modal('create-unit').show()">
                                                Ajouter Unité
                                            </flux:button>
                                        </x-flux::table.cell>
                                    </x-flux::table.row>
                                @endforelse
                            </x-flux::table.rows>
                        </x-flux::table>

                        @if($units->hasPages())
                            <div class="p-4 border-t border-zinc-100">
                                {{ $units->links() }}
                            </div>
                        @endif
                    </x-flux::card>
                @endif

                @if($tab === 'leases')
                    <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm rounded-t-none border-t-0">
                        <x-flux::card.header icon="document-text" title="Baux Actifs"
                            subtitle="Gestion des contrats de location" class="bg-zinc-50/50">
                            <x-slot:cardActions>
                                <flux:button variant="ghost" size="xs" x-on:click="Flux.modal('create-lease').show()">
                                    Ajouter Bail
                                </flux:button>
                            </x-slot:cardActions>
                        </x-flux::card.header>

                        <x-flux::table :paginate="$leases" linesPerPage>
                            <x-flux::table.columns>
                                <x-flux::table.column>Locataire</x-flux::table.column>
                                <x-flux::table.column>Unité</x-flux::table.column>
                                <x-flux::table.column>Loyer+Charges</x-flux::table.column>
                                <x-flux::table.column>Dépôt</x-flux::table.column>
                                <x-flux::table.column>Avance</x-flux::table.column>
                                <x-flux::table.column>Période</x-flux::table.column>
                                <x-flux::table.column>Statut</x-flux::table.column>
                                <x-flux::table.column align="right"></x-flux::table.column>
                            </x-flux::table.columns>
                            <x-flux::table.rows>
                                @forelse($leases as $lease)
                                    <x-flux::table.row wire:key="{{ $lease->id }}">
                                        <x-flux::table.cell>
                                            <div class="flex items-center gap-3">
                                                <flux:avatar size="xs" :name="$lease->renter->full_name"
                                                    class="ring-2 ring-white shadow-sm" />
                                                <div class="flex flex-col">
                                                    <span
                                                        class="font-medium text-zinc-900 leading-none">{{ $lease->renter->full_name }}</span>
                                                    @if($lease->renter->email)
                                                        <span class="text-2xs text-zinc-500 mt-1">{{ $lease->renter->email }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </x-flux::table.cell>
                                        <x-flux::table.cell>
                                            <a href="{{ route('tenant.units.show', $lease->unit) }}" wire:navigate
                                                class="font-medium text-indigo-600 hover:text-indigo-700">
                                                {{ $lease->unit->name }}
                                            </a>
                                        </x-flux::table.cell>
                                        <x-flux::table.cell class="font-semibold text-zinc-900">
                                            {{ Number::currency(($lease->rent_amount + $lease->charges_amount), 'XOF') }}
                                        </x-flux::table.cell>
                                        <x-flux::table.cell class="text-zinc-600 italic">
                                            {{ Number::currency($lease->deposit_amount, 'XOF') }}
                                        </x-flux::table.cell>
                                        <x-flux::table.cell class="text-zinc-600 italic">
                                            {{ Number::currency($lease->advance_amount ?: 0, 'XOF') }}
                                        </x-flux::table.cell>
                                        <x-flux::table.cell class="text-zinc-500 whitespace-nowrap">
                                            {{ $lease->start_date->isoFormat('D MMM YY') }}
                                            <span class="mx-1 text-zinc-300">→</span>
                                            @if($lease->end_date)
                                                {{ $lease->end_date->isoFormat('D MMM YY') }}
                                            @else
                                                <span class="text-2xs uppercase tracking-tighter opacity-70">Indéterminé</span>
                                            @endif
                                        </x-flux::table.cell>
                                        <x-flux::table.cell>
                                            <flux:badge :color="$lease->status->color()" size="sm" inset="top bottom">
                                                {{ $lease->status->label() }}
                                            </flux:badge>
                                        </x-flux::table.cell>
                                        <x-flux::table.cell align="right">
                                            <flux:dropdown>
                                                <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                                <flux:menu>
                                                    <flux:menu.item icon="eye" :href="route('tenant.units.show', $lease->unit)">
                                                        Détails Unité
                                                    </flux:menu.item>
                                                    <flux:menu.item icon="pencil-square"
                                                        wire:click="$dispatch('open-modal', { name: 'edit-lease', lease_id: '{{ $lease->id }}' })">
                                                        Modifier le bail
                                                    </flux:menu.item>
                                                </flux:menu>
                                            </flux:dropdown>
                                        </x-flux::table.cell>
                                    </x-flux::table.row>
                                @empty
                                    <x-flux::table.row>
                                        <x-flux::table.cell colspan="7" class="text-center text-zinc-500 py-10">
                                            <flux:icon.document-text class="size-10 text-zinc-300 mx-auto mb-3" />
                                            <p class="text-lg font-semibold">Aucun bail actif</p>
                                            <p class="text-sm">Commencez par ajouter votre premier bail.</p>
                                            <flux:button class="mt-4" x-on:click="Flux.modal('create-lease').show()">
                                                Ajouter Bail
                                            </flux:button>
                                        </x-flux::table.cell>
                                    </x-flux::table.row>
                                @endforelse
                            </x-flux::table.rows>
                        </x-flux::table>

                        @if($leases->hasPages())
                            <div class="p-4 border-t border-zinc-100">
                                {{ $leases->links() }}
                            </div>
                        @endif
                    </x-flux::card>
                @endif
            </div>

            <!-- Right Column: Sidebar (1/3) -->
            <div class="space-y-6">
                <!-- Owner Widget -->
                @if($property->owner_name)
                    <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm relative">
                        <div class="absolute top-0 right-0 p-4 opacity-5">
                            <flux:icon.user class="size-24 text-zinc-900" />
                        </div>
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-zinc-900 mb-1">Propriétaire</h3>
                            <p class="text-zinc-500 text-sm mb-6">Informations de contact</p>

                            <div class="flex items-center gap-4 mb-6">
                                <div
                                    class="size-12 rounded-full bg-zinc-100 flex items-center justify-center border border-zinc-200">
                                    <span
                                        class="text-lg font-bold text-zinc-600">{{ substr($property->owner_name, 0, 1) }}</span>
                                </div>
                                <div>
                                    <p class="font-bold text-zinc-900">{{ $property->owner_name }}</p>
                                    <p class="text-xs text-zinc-500">Propriétaire principal</p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @if($property->owner_phone)
                                    <a href="tel:{{ $property->owner_phone }}"
                                        class="flex items-center gap-3 text-sm text-zinc-600 hover:text-indigo-600 transition-colors group p-2 hover:bg-indigo-50 rounded-lg -mx-2">
                                        <div
                                            class="size-8 rounded-md bg-zinc-50 group-hover:bg-indigo-100 flex items-center justify-center transition-colors">
                                            <flux:icon.phone class="size-4 text-zinc-400 group-hover:text-indigo-600" />
                                        </div>
                                        {{ $property->owner_phone }}
                                    </a>
                                @endif
                                @if($property->owner_email)
                                    <a href="mailto:{{ $property->owner_email }}"
                                        class="flex items-center gap-3 text-sm text-zinc-600 hover:text-indigo-600 transition-colors group p-2 hover:bg-indigo-50 rounded-lg -mx-2">
                                        <div
                                            class="size-8 rounded-md bg-zinc-50 group-hover:bg-indigo-100 flex items-center justify-center transition-colors">
                                            <flux:icon.envelope class="size-4 text-zinc-400 group-hover:text-indigo-600" />
                                        </div>
                                        <span class="truncate">{{ $property->owner_email }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </x-flux::card>
                @endif

                <!-- Maintenance Widget -->
                <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm">
                    <x-flux::card.header icon="wrench-screwdriver" title="Maintenance Propriété"
                        subtitle="Parties communes uniquement" class="bg-zinc-50/50 border-b border-zinc-100 py-3">
                        <x-slot:cardActions>
                            <flux:button variant="ghost" size="xs"
                                href="{{ route('tenant.maintenance.properties.index') }}" wire:navigate>
                                Tout voir
                            </flux:button>
                        </x-slot:cardActions>
                    </x-flux::card.header>

                    @if($maintenanceRequests->isEmpty())
                        <div class="p-6 text-center">
                            <div class="size-10 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-2">
                                <flux:icon.check class="size-5 text-emerald-500" />
                            </div>
                            <p class="text-xs text-zinc-500">Aucun ticket en cours</p>
                        </div>
                    @else
                        <div class="divide-y divide-zinc-100">
                            @foreach($maintenanceRequests as $request)
                                <div class="p-3 flex items-start gap-3 hover:bg-zinc-50 transition-colors cursor-pointer group">
                                    <div class="size-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5"
                                        style="background-color: var(--color-{{ $request->priority->color() }}-50); color: var(--color-{{ $request->priority->color() }}-600);">
                                        <flux:icon.wrench class="size-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm font-medium text-zinc-900 truncate group-hover:text-indigo-600 transition-colors">
                                            {{ $request->title }}
                                        </p>
                                        <div class="flex items-center justify-between mt-1">
                                            <p class="text-xs text-zinc-400">
                                                {{ $request->created_at->diffForHumans(short: true) }}
                                            </p>
                                            <flux:badge size="sm" class="px-1.5 py-0.5 text-2xs"
                                                :color="$request->status->color()" inset="top bottom">
                                                {{ $request->status->label() }}
                                            </flux:badge>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-flux::card>
            </div>
        </div>

        <livewire:pages::tenant.properties.modals.create-unit :property="$property" />
        <livewire:pages::tenant.properties.modals.create />

        <livewire:pages::tenant.units.modals.edit />

        <livewire:pages::tenant.leases.modals.create />
        <livewire:pages::tenant.leases.modals.edit />
        <livewire:pages::tenant.renters.modals.create />
    </x-layouts::content>
</div>