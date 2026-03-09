<?php

use App\Models\Owner;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Enums\OwnerStatus;

new #[Layout('layouts.app', ['title' => 'Propriétaires'])] class extends Component {
    use WithPagination;

    public $search = '';
    public $status = 'all';
    public $perPage = 10;
    public $sortCol = 'created_at';
    public $sortAsc = false;

    public function sortBy($column)
    {
        if ($this->sortCol === $column) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortCol = $column;
            $this->sortAsc = true;
        }
    }

    public function delete(Owner $owner)
    {
        if ($owner->properties()->count() > 0) {
            $this->js("Flux.toast('Impossible de supprimer ce propriétaire car il possède des biens.', 'danger')");
            return;
        }

        $owner->delete();
        $this->js("Flux.toast('Propriétaire supprimé.')");
    }

    public function with()
    {
        return [
            'owners' => Owner::query()
                ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
                ->when($this->search, fn($q) => $q->where(
                    fn($sub) =>
                    $sub->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                ))
                ->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc')
                ->paginate($this->perPage),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <x-layouts::content heading="Propriétaires" subheading="Gestion des propriétaires (mandants).">
        
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="$dispatch('open-modal', { name: 'create-owner' })">
                Ajouter
            </flux:button>
        </x-slot:actions>

        <x-flux::card>
            <x-flux::card.header icon="users" title="Liste des propriétaires"
                subtitle="Gestion des propriétaires (mandants).">
            </x-flux::card.header>

            <x-flux::table :paginate="$owners" search linesPerPage>
                <x-slot:selectable>
                    <flux:select wire:model.live="status" class="w-full md:w-auto" size="sm">
                        <flux:select.option value="all">Tous</flux:select.option>
                        @foreach (\App\Enums\OwnerStatus::cases() as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </x-slot:selectable>

                <x-flux::table.columns>
                    <x-flux::table.column sortable :sorted="$sortCol === 'first_name'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('first_name')">Nom</x-flux::table.column>
                    <x-flux::table.column align="center">Statut</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'email'" :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('email')">Contact</x-flux::table.column>
                    <x-flux::table.column align="right">Biens</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'created_at'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('created_at')">Date d'ajout</x-flux::table.column>
                    <x-flux::table.column></x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @foreach ($owners as $owner)
                        <x-flux::table.row :key="$owner->id">
                            <x-flux::table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar class="size-10"
                                        :initials="substr($owner->first_name, 0, 1) . substr($owner->last_name, 0, 1)" />
                                    <div>
                                        <a href="{{ route('tenant.owners.show', $owner) }}"
                                            class="font-medium text-zinc-900 hover:text-indigo-600 transition-colors">
                                            {{ $owner->first_name }} {{ $owner->last_name }}
                                        </a>
                                    </div>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell align="center">
                                <flux:badge size="sm" :color="$owner->status->color()" inset="top bottom">
                                    {{ $owner->status->label() }}
                                </flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <div class="text-zinc-900">{{ $owner->email }}</div>
                                <div class="text-xs text-zinc-500">{{ $owner->phone }}</div>
                            </x-flux::table.cell>
                            <x-flux::table.cell align="right">
                                <flux:badge size="sm" color="zinc" inset="top bottom">{{ $owner->properties()->count() }}
                                </flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <span class="text-zinc-500">{{ $owner->created_at->format('d/m/Y') }}</span>
                            </x-flux::table.cell>
                            <x-flux::table.cell align="right">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" href="{{ route('tenant.owners.show', $owner) }}">
                                            Voir le profil</flux:menu.item>
                                        <flux:menu.item icon="pencil-square"
                                            wire:click="$dispatch('open-modal', { name: 'edit-owner', owner: '{{ $owner->id }}' })">
                                            Modifier</flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger"
                                            wire:click="delete('{{ $owner->id }}')"
                                            wire:confirm="Supprimer ce propriétaire ?">Supprimer</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforeach
                </x-flux::table.rows>
            </x-flux::table>

            @if ($owners->isEmpty())
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center size-12 rounded-full bg-zinc-100 mb-4">
                        <flux:icon.users class="size-6 text-zinc-400" />
                    </div>
                    <h3 class="text-lg font-medium text-zinc-900">Aucun propriétaire trouvé</h3>
                    <p class="text-zinc-500 max-w-sm mx-auto mt-1">
                        {{ $search ? 'Ajustez votre recherche.' : 'Commencez par ajouter un mandant.' }}
                    </p>
                </div>
            @endif
        </x-flux::card>
    </x-layouts::content>

    <livewire:pages::tenant.owners.modals.create />
</div>