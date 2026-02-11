<?php

use App\Models\Vendor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\WithDataTable;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;
    use WithDataTable;

    public function mount()
    {
        $this->sortCol = 'name';
        $this->sortAsc = true;
    }

    #[On('vendor-created')]
    public function refresh()
    {
    }

    public function delete($id)
    {
        Vendor::findOrFail($id)->delete();
        flash()->success('Prestataire supprimé.');
    }

    public function with()
    {
        return [
            'vendors' => $this->applySorting(
                Vendor::query()
                    ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('service_type', 'like', '%' . $this->search . '%'))
            )->paginate($this->perPage),
        ];
    }
};
?>

<div>
    <x-layouts::content heading="Prestataires de services"
        subheading="Gérer les fournisseurs et entrepreneurs externes.">
        <x-slot:actions>
            <flux:button icon="plus" wire:click="$dispatch('open-modal', { name: 'create-vendor' })">
                Ajouter un prestataire
            </flux:button>
        </x-slot:actions>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <x-flux::card class="flex items-center gap-4 p-5">
                <div class="flex items-center justify-center size-12 rounded-full bg-blue-50 text-blue-600">
                    <flux:icon.user-group class="size-6" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500">Total Prestataires</span>
                    <span class="text-2xl font-bold text-zinc-900">{{ Vendor::count() }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-4 p-5">
                <div class="flex items-center justify-center size-12 rounded-full bg-indigo-50 text-indigo-600">
                    <flux:icon.wrench-screwdriver class="size-6" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500">Services Actifs</span>
                    <span
                        class="text-2xl font-bold text-zinc-900">{{ Vendor::distinct('service_type')->count() }}</span>
                </div>
            </x-flux::card>
        </div>

        <x-flux::card>
            <x-flux::card.header>
                <x-flux::card.title>Tous les prestataires</x-flux::card.title>
                <div class="flex gap-2">
                    <flux:select wire:model.live="perPage" class="w-20" size="sm">
                        <flux:select.option value="10">10</flux:select.option>
                        <flux:select.option value="25">25</flux:select.option>
                        <flux:select.option value="50">50</flux:select.option>
                    </flux:select>
                    <flux:input wire:model.live="search" icon="magnifying-glass" size="sm"
                        placeholder="Rechercher des prestataires..." class="max-w-xs" />
                </div>
            </x-flux::card.header>

            <x-flux::table :paginate="$vendors">
                <x-flux::table.columns>
                    <x-flux::table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('name')">Prestataire</x-flux::table.column>
                    <x-flux::table.column>Service</x-flux::table.column>
                    <x-flux::table.column>Contact</x-flux::table.column>
                    <x-flux::table.column align="right"></x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @foreach($vendors as $vendor)
                        <x-flux::table.row :key="$vendor->id">
                            <x-flux::table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                                        <flux:icon.briefcase class="size-5 text-indigo-600" />
                                    </div>
                                    <span class="font-medium text-zinc-900">{{ $vendor->name }}</span>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:badge size="sm" color="zinc" inset="top bottom">
                                    {{ $vendor->service_type ?? 'Général' }}
                                </flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <div class="text-sm font-medium text-zinc-900">{{ $vendor->email }}</div>
                                <div class="text-xs text-zinc-500">{{ $vendor->phone ?? 'Aucun téléphone' }}</div>
                            </x-flux::table.cell>
                            <x-flux::table.cell align="right">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square"
                                            wire:click="$dispatch('edit-vendor', { vendor: '{{ $vendor->id }}' })">Modifier
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="delete('{{ $vendor->id }}')" variant="danger"
                                            icon="trash" wire:confirm="Supprimer ce prestataire ?">Supprimer
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforeach
                </x-flux::table.rows>
            </x-flux::table>

            @if($vendors->isEmpty())
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center size-12 rounded-full bg-zinc-100 mb-4">
                        <flux:icon.briefcase class="size-6 text-zinc-400" />
                    </div>
                    <h3 class="text-lg font-medium text-zinc-900">Aucun prestataire trouvé</h3>
                    <p class="text-zinc-500 max-w-sm mx-auto mt-1">
                        {{ $search ? 'Essayez d\'ajuster votre recherche ou vos filtres.' : 'Commencez par ajouter votre premier prestataire.' }}
                    </p>
                </div>
            @endif
        </x-flux::card>
    </x-layouts::content>

    <!-- Modals -->
    <livewire:pages.tenant.settings.vendors.modals.create />
    <livewire:pages.tenant.settings.vendors.modals.edit />
</div>