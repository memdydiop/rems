<?php

use App\Models\MaintenanceRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

new #[Layout('layouts.app', ['title' => 'Maintenance'])] class extends Component {
    use WithPagination;
    public $search = '';
    public $perPage = 10;
    public $sortCol = 'created_at';
    public $sortAsc = false;
    public $status = 'all'; // Added for filter consistency

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

    public function updatedStatus()
    {
        $this->resetPage();
    }

    #[\Livewire\Attributes\On('request-created')]
    #[\Livewire\Attributes\On('request-updated')]
    #[\Livewire\Attributes\On('request-deleted')]
    public function refresh()
    {
        $this->resetPage();
    }

    public function with()
    {
        $stats = MaintenanceRequest::toBase()
            ->selectRaw("count(case when status in ('pending', 'in_progress') then 1 end) as open_requests")
            ->selectRaw("count(case when priority = 'high' and status != 'resolved' then 1 end) as high_priority")
            ->selectRaw("count(case when status = 'resolved' and updated_at >= ? then 1 end) as resolved_recently", [now()->subDays(30)])
            ->first();

        return [
            'requests' => MaintenanceRequest::query()
                ->with(['property', 'user', 'unit'])
                ->when($this->search, function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('property', fn($sub) => $sub->where('name', 'like', '%' . $this->search . '%'));
                })
                ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
                ->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc')
                ->paginate($this->perPage),
            'openRequests' => $stats->open_requests ?? 0,
            'highPriority' => $stats->high_priority ?? 0,
            'resolvedRecently' => $stats->resolved_recently ?? 0,
        ];
    }

    public function delete($id)
    {
        $request = MaintenanceRequest::findOrFail($id);
        $request->delete();
        $this->dispatch('request-deleted');
        Flux\Flux::toast('Ticket supprimé.', 'success');
    }
};
?>

<div>
    <x-layouts::content heading="Maintenance" subheading="Gérez vos tickets de réparation et les problèmes de service.">
        <x-slot name="actions">
            <flux:button variant="primary" icon="plus"
                wire:click="$dispatch('open-modal', { name: 'create-maintenance' })">
                Nouveau Ticket
            </flux:button>
        </x-slot>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-stats-card title="Tickets Ouverts" :value="$openRequests" icon="wrench-screwdriver" color="orange" />
            <x-stats-card title="Haute Priorité" :value="$highPriority" icon="exclamation-triangle" color="red" />
            <x-stats-card title="Résolus (30j)" :value="$resolvedRecently" icon="check-circle" color="green" />
        </div>

        <x-flux::card class="p-0 overflow-hidden">
            <x-flux::card.header icon="wrench" title="Journal de Maintenance"
                subtitle="Historique complet des interventions" />

            <x-flux::table :paginate="$requests" search linesPerPage>
                <x-slot:selectable>
                    <flux:select wire:model.live="status" size="sm" class="w-full md:w-40">
                        <flux:select.option value="all">Tous statut</flux:select.option>
                        @foreach(\App\Enums\MaintenanceStatus::cases() as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </x-slot:selectable>

                <x-flux::table.columns>
                    <x-flux::table.column sortable :sorted="$sortCol === 'title'" :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('title')">Ticket</x-flux::table.column>
                    <x-flux::table.column>Propriété</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'priority'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('priority')">Priorité</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'status'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('status')">Statut</x-flux::table.column>
                    <x-flux::table.column>Auteur</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'created_at'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('created_at')">Créé le</x-flux::table.column>
                    <x-flux::table.column align="right"></x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @forelse ($requests as $request)
                        <x-flux::table.row :key="$request->id">
                            <x-flux::table.cell>
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900">{{ $request->title }}</span>
                                    <span
                                        class="text-xs text-zinc-500 truncate max-w-xs">{{ Str::limit($request->description, 50) }}</span>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm text-zinc-900 font-medium">{{ $request->property->name ?? 'N/A' }}</span>
                                    @if($request->unit)
                                        <flux:badge size="sm" color="zinc" class="mt-1 w-fit">{{ $request->unit->name }}
                                        </flux:badge>
                                    @endif
                                </div>
                            </x-flux::table.cell>
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
                            <x-flux::table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:avatar src="https://i.pravatar.cc/150?u={{ $request->user->email ?? 'unknown' }}"
                                        size="xs" class="ring-1 ring-white shadow-sm" />
                                    <span
                                        class="text-sm font-medium text-zinc-700">{{ $request->user->name ?? 'Inconnu' }}</span>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell
                                class="text-zinc-500">{{ $request->created_at->format('d M Y') }}</x-flux::table.cell>
                            <x-flux::table.cell align="right">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        @if($request->isEditable())
                                            <flux:menu.item icon="pencil-square"
                                                wire:click="$dispatch('edit-maintenance', { id: '{{ $request->id }}' })">
                                                Modifier</flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item icon="trash" wire:click="delete('{{ $request->id }}')"
                                                wire:confirm="Êtes-vous sûr ?" variant="danger">Supprimer</flux:menu.item>
                                        @else
                                            <flux:menu.item icon="eye" disabled>Voir (Lecture seule)</flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @empty
                        <x-flux::table.row>
                            <x-flux::table.cell colspan="7">
                                <div class="flex flex-col items-center justify-center py-12 text-center">
                                    <div class="bg-zinc-50 p-4 rounded-full mb-3 border border-zinc-100">
                                        <flux:icon name="wrench-screwdriver" class="text-zinc-300 w-8 h-8" />
                                    </div>
                                    <h3 class="text-base font-medium text-zinc-900">Aucun ticket de maintenance</h3>
                                    <p class="text-zinc-500 mt-1 max-w-sm mx-auto">Commencez par créer votre premier ticket
                                        pour suivre les réparations.</p>
                                    <div class="mt-4">
                                        <flux:button variant="primary" icon="plus" size="sm"
                                            wire:click="$dispatch('open-modal', { name: 'create-maintenance' })">
                                            Créer un Ticket
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

    <livewire:pages::tenant.maintenance.modals.create />
    <livewire:pages::tenant.maintenance.modals.edit />
</div>