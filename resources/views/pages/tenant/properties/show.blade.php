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
        $monthlyRevenue = $this->property->leases()->where('leases.status', 'active')->sum('leases.rent_amount');
        $expectedRevenue = $this->property->units()->sum('rent_amount');
        $totalUnits = $this->property->units()->count();
        $vacantUnits = $this->property->units()->where('status', 'vacant')->count();
        $occupiedUnits = $this->property->units()->where('status', 'occupied')->count();

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
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();

        return [
            'units' => $units,
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
                            <h3 class="text-2xl font-bold text-zinc-900">{{ $units->count() }}</h3>
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
            <div class="lg:col-span-2 space-y-8">
                <x-flux::card class="p-0 overflow-hidden border border-zinc-100 shadow-sm">
                    <x-flux::card.header icon="home" title="Unités Locatives" subtitle="Gestion des lots et occupation">
                        <x-slot:cardActions>
                            <flux:button variant="primary" size="sm" icon="plus" class="shadow-sm"
                                x-on:click="Flux.modal('create-unit').show()">
                                Ajouter
                            </flux:button>
                        </x-slot:cardActions>
                    </x-flux::card.header>


                    <x-flux::table :paginate="$units" search linesPerPage>
                        <x-flux::table.columns>
                            <x-flux::table.column>Nom</x-flux::table.column>
                            <x-flux::table.column>Type</x-flux::table.column> <!-- Added Type Column -->
                            <x-flux::table.column>Loyer</x-flux::table.column>
                            <x-flux::table.column>Statut</x-flux::table.column>
                            <x-flux::table.column>Locataire Actuel</x-flux::table.column>
                            <x-flux::table.column align="right"></x-flux::table.column>
                        </x-flux::table.columns>
                        <x-flux::table.rows>
                            @forelse ($units as $unit)
                                <x-flux::table.row :key="$unit->id" class="hover:bg-zinc-50/50 transition-colors">
                                    <x-flux::table.cell>
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="size-9 rounded-lg {{ $unit->status === \App\Enums\UnitStatus::Occupied ? 'bg-emerald-50 border-emerald-100' : 'bg-zinc-50 border-zinc-100' }} border flex items-center justify-center shadow-xs">
                                                <flux:icon.home
                                                    class="size-4 {{ $unit->status === \App\Enums\UnitStatus::Occupied ? 'text-emerald-600' : 'text-zinc-400' }}" />
                                            </div>
                                            <span class="font-medium text-zinc-900">{{ $unit->name }}</span>
                                        </div>
                                    </x-flux::table.cell>
                                    <x-flux::table.cell>
                                        <flux:badge size="sm" :color="$unit->type->color()" inset="top bottom">
                                            {{ $unit->type->label() }}
                                        </flux:badge>
                                    </x-flux::table.cell>
                                    <x-flux::table.cell>
                                        <span
                                            class="font-semibold text-zinc-700">{{ Number::currency($unit->rent_amount, 'XOF') }}</span>
                                    </x-flux::table.cell>
                                    <x-flux::table.cell>
                                        <flux:badge size="sm" :color="$unit->status->color()" inset="top bottom">
                                            {{ $unit->status->label() }}
                                        </flux:badge>
                                    </x-flux::table.cell>
                                    <x-flux::table.cell>
                                        @if($unit->status === \App\Enums\UnitStatus::Occupied && $unit->leases->first()?->renter)
                                            <div class="flex items-center gap-2 group cursor-pointer">
                                                <div
                                                    class="size-6 rounded-full bg-linear-to-br from-indigo-100 to-violet-100 flex items-center justify-center text-xs font-bold text-indigo-700 border border-indigo-200">
                                                    {{ strtoupper(substr($unit->leases->first()->renter->first_name, 0, 1)) }}
                                                </div>
                                                <span
                                                    class="text-sm font-medium text-zinc-700 group-hover:text-indigo-600 transition-colors">{{ $unit->leases->first()->renter->first_name }}
                                                    {{ $unit->leases->first()->renter->last_name }}</span>
                                            </div>
                                        @else
                                            <span class="text-zinc-400 text-sm italic">Vacant</span>
                                        @endif
                                    </x-flux::table.cell>
                                    <x-flux::table.cell align="right">
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal"
                                                class="text-zinc-400 hover:text-zinc-800" />
                                            <flux:menu>
                                                @if($unit->status === \App\Enums\UnitStatus::Vacant)
                                                    <flux:menu.item icon="document-plus"
                                                        wire:click="$dispatch('open-modal', { name: 'create-lease', unit_id: '{{ $unit->id }}' })">
                                                        Nouveau Bail
                                                    </flux:menu.item>
                                                @endif
                                                <flux:menu.item icon="trash" variant="danger"
                                                    wire:click="deleteUnit('{{ $unit->id }}')"
                                                    wire:confirm="Supprimer cette unité ?">
                                                    Supprimer
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </x-flux::table.cell>
                                </x-flux::table.row>
                            @empty
                                <x-flux::table.row>
                                    <x-flux::table.cell colspan="5" class="text-center py-8">
                                        <div class="flex flex-col items-center gap-2">
                                            <flux:icon.building-office-2 class="size-10 text-zinc-300" />
                                            <p class="text-zinc-500">Aucune unité trouvée</p>
                                        </div>
                                    </x-flux::table.cell>
                                </x-flux::table.row>
                            @endforelse
                        </x-flux::table.rows>
                    </x-flux::table>
                </x-flux::card>
            </div>

            <!-- Right Column: Sidebar (1/3) -->
            <div class="space-y-8">

                <!-- Owner Info Card -->
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
                    <x-flux::card.header icon="wrench-screwdriver" title="Maintenance" subtitle="Derniers tickets"
                        class="bg-zinc-50/50 border-b border-zinc-100 py-3">
                        <x-slot:cardActions>
                            <flux:button variant="ghost" size="xs" href="{{ route('tenant.maintenance.index') }}"
                                wire:navigate>
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
    </x-layouts::content>
</div>