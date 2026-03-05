<?php

use App\Models\Lease;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app', ['title' => 'Baux'])] class extends Component {
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortCol = 'start_date';
    public $sortAsc = false;

    #[On('lease-created')]
    #[On('lease-updated')]
    public function refresh()
    {
    }

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

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        $lease = Lease::findOrFail($id);

        // If the lease is active, free up the unit
        if ($lease->status === 'active') {
            $lease->unit->update(['status' => \App\Enums\UnitStatus::Vacant]);
        }

        $lease->delete();
        $this->js("Flux.toast('Bail supprimé.')");
    }

    public function terminate($id)
    {
        $lease = Lease::findOrFail($id);

        if ($lease->status === 'active') {
            $lease->update([
                'status' => \App\Enums\LeaseStatus::Terminated,
                'end_date' => now(),
            ]);

            // Free the unit
            $lease->unit->update(['status' => \App\Enums\UnitStatus::Vacant]);

            $this->js("Flux.toast('Bail résilié avec succès.')");
        }
    }

    public function with()
    {
        return [
            'leases' => Lease::query()
                ->with(['unit.property', 'renter'])
                ->when($this->search, function ($q) {
                    $q->whereHas('renter', fn($sub) => $sub->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('unit', fn($sub) => $sub->where('name', 'like', '%' . $this->search . '%'));
                })
                ->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc')
                ->paginate($this->perPage),
            'activeLeases' => Lease::where('status', 'active')->count(),
            'expiringSoon' => Lease::where('status', 'active')->where('end_date', '<', now()->addMonths(2))->count(),
            'totalRevenue' => Lease::where('status', 'active')->sum('rent_amount'),
        ];
    }
};
?>

<div>
    <x-layouts::content heading="Baux" subheading="Gérez vos contrats de location et revenus.">
        <x-slot:actions>
            <flux:button icon="plus" wire:click="$dispatch('open-modal', { name: 'create-lease' })">
                Nouveau Bail
            </flux:button>
        </x-slot:actions>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <x-flux::card bg="bg-blue-50"
                class="p-6 border-0 relative overflow-hidden h-full group hover:shadow-[0_8px_30px_-4px_rgba(59,130,246,0.1)] transition-all duration-300 rounded-[20px]">
                <div class="flex flex-col h-full relative z-10">
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-zinc-500 font-medium text-sm">Baux Actifs</span>
                            <div class="text-3xl font-bold text-zinc-900 tracking-tight">
                                {{ $activeLeases }}
                            </div>
                        </div>
                        <div
                            class="bg-blue-400/20 p-2.5 rounded backdrop-blur-sm group-hover:scale-105 transition-transform">
                            <flux:icon name="document-text" variant="solid" class="w-5 h-5 text-blue-500" />
                        </div>
                    </div>
                </div>
                <!-- Pattern -->
                <img src="{{ asset('img/widget-bg-abstract.png') }}"
                    class="absolute inset-0 w-64 pointer-events-none group-hover:rotate-12 transition-transform duration-700"
                    alt="" />
            </x-flux::card>

            <x-flux::card bg="bg-orange-50"
                class="p-6 border-0 relative overflow-hidden h-full group hover:shadow-[0_8px_30px_-4px_rgba(249,115,22,0.1)] transition-all duration-300 rounded-[20px]">
                <div class="flex flex-col h-full relative z-10">
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-zinc-500 font-medium text-sm">Expire Bientôt</span>
                            <div class="text-3xl font-bold text-zinc-900 tracking-tight">
                                {{ $expiringSoon }}
                            </div>
                        </div>
                        <div
                            class="bg-orange-400/20 p-2.5 rounded backdrop-blur-sm group-hover:scale-105 transition-transform">
                            <flux:icon name="clock" variant="solid" class="w-5 h-5 text-orange-500" />
                        </div>
                    </div>
                </div>
                <!-- Pattern -->
                <img src="{{ asset('img/widget-bg-abstract.png') }}"
                    class="absolute inset-0 w-64 pointer-events-none group-hover:rotate-12 transition-transform duration-700"
                    alt="" />
            </x-flux::card>

            <x-flux::card bg="bg-green-50"
                class="p-6 border-0 relative overflow-hidden h-full group hover:shadow-[0_8px_30px_-4px_rgba(34,197,94,0.1)] transition-all duration-300 rounded-[20px]">
                <div class="flex flex-col h-full relative z-10">
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-zinc-500 font-medium text-sm">Loyer Mensuel Total</span>
                            <div class="text-3xl font-bold text-zinc-900 tracking-tight">
                                {{ number_format($totalRevenue, 0, ',', ' ') . ' FCFA' }}
                            </div>
                        </div>
                        <div
                            class="bg-green-400/20 p-2.5 rounded backdrop-blur-sm group-hover:scale-105 transition-transform">
                            <flux:icon name="banknotes" variant="solid" class="w-5 h-5 text-green-500" />
                        </div>
                    </div>
                </div>
                <!-- Pattern -->
                <img src="{{ asset('img/widget-bg-abstract.png') }}"
                    class="absolute inset-0 w-64 pointer-events-none group-hover:rotate-12 transition-transform duration-700"
                    alt="" />
            </x-flux::card>
        </div>

        <x-flux::card class="overflow-hidden">
            <x-flux::card.header :title="'Tous les Baux' . ($leases->total() > 0 ? ' (' . $leases->total() . ')' : '')"
                subtitle="Gérez vos contrats de location et revenus." />


            <x-flux::table :paginate="$leases" search linesPerPage>
                <x-flux::table.columns>
                    <x-flux::table.column>Locataire</x-flux::table.column>
                    <x-flux::table.column>Unité</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'start_date'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('start_date')">Période</x-flux::table.column>
                    <x-flux::table.column align="right" sortable :sorted="$sortCol === 'rent_amount'"
                        :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('rent_amount')">Loyer</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'status'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('status')">Statut</x-flux::table.column>
                    <x-flux::table.column align="right"></x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @forelse ($leases as $lease)
                        <x-flux::table.row :key="$lease->id">
                            <x-flux::table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" :name="$lease->renter->full_name"
                                        class="ring-2 ring-white shadow-sm" />
                                    <div class="flex flex-col">
                                        <span class="font-medium text-zinc-900">{{ $lease->renter->full_name }}</span>
                                        <span class="text-xs text-zinc-500">{{ $lease->renter->email }}</span>
                                    </div>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <a href="{{ route('tenant.units.show', $lease->unit) }}"
                                    class="text-sm text-zinc-900 font-medium hover:text-indigo-600 transition-colors">{{ $lease->unit->name }}</a>
                                <a href="{{ route('tenant.properties.show', $lease->unit->property) }}"
                                    class="text-xs text-zinc-500 hover:text-indigo-500 transition-colors">{{ $lease->unit->property->name }}</a>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <div class="text-sm text-zinc-600">{{ $lease->start_date->format('d M Y') }} -
                                    {{ $lease->end_date ? $lease->end_date->format('d M Y') : 'Sans fin' }}
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell variant="strong" align="right">
                                {{ number_format($lease->rent_amount, 0, ',', ' ') . ' FCFA' }}
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:badge size="sm" :color="$lease->status->color()" inset="top bottom">
                                    {{ $lease->status->label() }}
                                </flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell align="right">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square"
                                            wire:click="$dispatch('open-modal', { name: 'edit-lease', lease_id: '{{ $lease->id }}' })">
                                            Modifier</flux:menu.item>
                                        <flux:menu.separator />
                                        @if($lease->status === 'active')
                                            <flux:menu.item icon="x-circle" wire:click="terminate('{{ $lease->id }}')"
                                                wire:confirm="Êtes-vous sûr de vouloir résilier ce bail ? Cette action libérera l'unité.">
                                                Mettre fin au bail</flux:menu.item>
                                            <flux:menu.separator />
                                        @endif
                                        <flux:menu.item icon="trash" wire:click="delete('{{ $lease->id }}')"
                                            wire:confirm="Supprimer ce bail d'historique ?" variant="danger">Supprimer
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @empty
                        <x-flux::table.row>
                            <x-flux::table.cell colspan="6">
                                <div class="flex flex-col items-center justify-center p-6 text-center">
                                    <div class="bg-zinc-50 p-3 rounded-full mb-3">
                                        <flux:icon name="document-text" class="text-zinc-300 w-6 h-6" />
                                    </div>
                                    <p class="text-sm font-medium text-zinc-900">Aucun bail trouvé</p>
                                    <p class="text-zinc-500 mt-1">Commencez par créer votre premier contrat de bail.</p>
                                </div>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>
    </x-layouts::content>

    <livewire:pages::tenant.leases.modals.create />
    <livewire:pages::tenant.leases.modals.edit />
    <livewire:pages::tenant.renters.modals.create />
</div>