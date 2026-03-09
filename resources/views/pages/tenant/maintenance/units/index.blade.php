<?php

use App\Models\MaintenanceRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

new #[Layout('layouts.app', ['title' => 'Maintenance'])] class extends Component {
    use WithPagination;
    public $search = '';
    public $perPage = 10;
    public $sortCol = 'created_at';
    public $sortAsc = false;
    public $status = 'all';
    public $historyRequestId = null;
    public $historyActivities = [];

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
        $baseQuery = MaintenanceRequest::where('category', \App\Enums\MaintenanceCategory::Unit);

        $stats = $baseQuery->toBase()
            ->selectRaw("count(case when status in ('pending', 'in_progress') then 1 end) as open_requests")
            ->selectRaw("count(case when priority = 'high' and status != 'resolved' then 1 end) as high_priority")
            ->selectRaw("count(case when status = 'resolved' and updated_at >= ? then 1 end) as resolved_recently", [now()->subDays(30)])
            ->selectRaw("count(*) as total")
            ->first();

        return [
            'requests' => clone $baseQuery
                ->with(['property', 'user', 'unit'])
                ->when($this->search, function ($q) {
                    $q->where(function ($subQ) {
                        $subQ->where('title', 'like', '%' . $this->search . '%')
                            ->orWhere('description', 'like', '%' . $this->search . '%')
                            ->orWhereHas('property', fn($sub) => $sub->where('name', 'like', '%' . $this->search . '%'));
                    });
                })
                ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
                ->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc')
                ->paginate($this->perPage),
            'openRequests' => $stats->open_requests ?? 0,
            'highPriority' => $stats->high_priority ?? 0,
            'resolvedRecently' => $stats->resolved_recently ?? 0,
            'totalCount' => $stats->total ?? 0,
        ];
    }

    public function delete($id)
    {
        $request = MaintenanceRequest::findOrFail($id);
        $request->delete();
        $this->dispatch('request-deleted');
        Flux\Flux::toast('Ticket supprimé.', 'success');
    }

    #[\Livewire\Attributes\On('reopen-maintenance')]
    public function reopen($id)
    {
        $request = MaintenanceRequest::findOrFail($id);
        $request->update(['status' => 'pending']);
        $this->dispatch('request-updated');
        Flux\Flux::toast('Ticket réouvert.', 'success');
    }

    public function showHistory($id)
    {
        $this->historyRequestId = $id;
        $this->historyActivities = Activity::where('subject_type', MaintenanceRequest::class)
            ->where('subject_id', $id)
            ->latest()
            ->get()
            ->map(function ($activity) {
                $changes = [];
                $old = $activity->properties['old'] ?? [];
                $new = $activity->properties['attributes'] ?? [];

                foreach ($new as $key => $value) {
                    $oldVal = $old[$key] ?? null;
                    if ($oldVal !== $value) {
                        $changes[] = [
                            'field' => $this->translateField($key),
                            'old' => $this->translateValue($key, $oldVal),
                            'new' => $this->translateValue($key, $value),
                        ];
                    }
                }

                return [
                    'event' => $activity->event ?? $activity->description,
                    'date' => $activity->created_at->format('d/m/Y H:i'),
                    'ago' => $activity->created_at->diffForHumans(),
                    'user' => $activity->causer?->name ?? 'Système',
                    'changes' => $changes,
                ];
            })
            ->toArray();

        Flux\Flux::modal('maintenance-history')->show();
    }

    private function translateField($field): string
    {
        return match ($field) {
            'status' => 'Statut',
            'category' => 'Type/Catégorie',
            'priority' => 'Priorité',
            'title' => 'Titre',
            'description' => 'Description',
            'internal_notes' => 'Notes Internes',
            'property_id' => 'Propriété',
            'unit_id' => 'Unité',
            default => ucfirst($field),
        };
    }

    private function translateValue($field, $value): string
    {
        if ($value === null)
            return '—';
        if ($field === 'status') {
            return \App\Enums\MaintenanceStatus::tryFrom($value)?->label() ?? $value;
        }
        if ($field === 'category') {
            return \App\Enums\MaintenanceCategory::tryFrom($value)?->label() ?? $value;
        }
        if ($field === 'priority') {
            return \App\Enums\MaintenancePriority::tryFrom($value)?->label() ?? $value;
        }
        return (string) $value;
    }
};
?>

<div>
    <x-layouts::content heading="Maintenance (Privatif)"
        subheading="Gérez les tickets liés aux unités privatives des clients.">
        <x-slot name="actions">
            <flux:button variant="primary" icon="plus"
                wire:click="$dispatch('open-modal', { name: 'unit-create-maintenance' })">
                Nouveau Ticket
            </flux:button>
        </x-slot>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <x-stats-card title="Tickets Ouverts" :value="$openRequests" icon="wrench-screwdriver" color="orange" />
            <x-stats-card title="Haute Priorité" :value="$highPriority" icon="exclamation-triangle" color="red" />
            <x-stats-card title="Résolus (30j)" :value="$resolvedRecently" icon="check-circle" color="green" />
        </div>


        <x-flux::card class="p-0 overflow-hidden rounded-t-none border-t-0">
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
                    <x-flux::table.column>Propriété / Unité</x-flux::table.column>
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
                                    <span class="text-sm text-zinc-900 font-medium">
                                        {{ $request->property?->name ?? 'N/A' }}
                                        @if($request->property?->trashed())
                                            <span class="text-rose-500 text-2xs">(Supprimé)</span>
                                        @endif
                                    </span>
                                    <span class="text-xs text-zinc-500">{{ $request->unit->name ?? '—' }}</span>
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
                                            <flux:menu.item icon="clock" wire:click="showHistory('{{ $request->id }}')">
                                                Historique</flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item icon="trash" wire:click="delete('{{ $request->id }}')"
                                                wire:confirm="Êtes-vous sûr ?" variant="danger">Supprimer</flux:menu.item>
                                        @else
                                            <flux:menu.item icon="arrow-path"
                                                wire:click="$dispatch('reopen-maintenance', { id: '{{ $request->id }}' })"
                                                wire:confirm="Voulez-vous réouvrir ce ticket ?">
                                                Réouvrir
                                            </flux:menu.item>
                                            <flux:menu.item icon="clock" wire:click="showHistory('{{ $request->id }}')">
                                                Historique</flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item icon="information-circle" disabled>
                                                {{ $request->status->label() }}
                                            </flux:menu.item>
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
                                    <flux:button variant="primary" icon="plus" size="sm"
                                        wire:click="$dispatch('open-modal', { name: 'unit-create-maintenance' })">
                                        Créer un Ticket
                                    </flux:button>
                                </div>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>
    </x-layouts::content>

    <livewire:pages::tenant.maintenance.units.create-modal />
    <livewire:pages::tenant.maintenance.units.edit-modal />

    <!-- History Modal -->
    <flux:modal name="maintenance-history" class="min-w-100 md:w-150">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Historique du ticket</flux:heading>
                <flux:subheading>Suivi des modifications</flux:subheading>
            </div>

            @if(count($historyActivities) === 0)
                <div class="text-center py-8 text-zinc-500">
                    <flux:icon name="clock" class="size-8 mx-auto mb-2 text-zinc-300" />
                    <p>Aucun historique disponible.</p>
                </div>
            @else
                <div class="relative space-y-0 max-h-96 overflow-y-auto">
                    @foreach($historyActivities as $activity)
                                <div class="flex gap-3 pb-4 relative">
                                    <!-- Timeline line -->
                                    @if(!$loop->last)
                                        <div class="absolute left-3.75 top-8 bottom-0 w-px bg-zinc-200"></div>
                                    @endif
                                    <!-- Dot -->
                                    <div class="shrink-0 mt-1">
                                        <div class="size-2.5 rounded-full ring-4 ring-white
                                                                                                                                                                                    {{ match ($activity['event']) {
                            'created' => 'bg-emerald-500',
                            'updated' => 'bg-blue-500',
                            'deleted' => 'bg-red-500',
                            default => 'bg-zinc-400',
                        } }}"></div>
                                    </div>
                                    <!-- Content -->
                                    <div class="flex-1 min-w-0 -mt-0.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-sm font-medium text-zinc-900">
                                                {{ match ($activity['event']) {
                            'created' => 'Création',
                            'updated' => 'Modification',
                            'deleted' => 'Suppression',
                            default => ucfirst($activity['event']),
                        } }}
                                            </span>
                                            <span class="text-xs text-zinc-400 shrink-0"
                                                title="{{ $activity['date'] }}">{{ $activity['ago'] }}</span>
                                        </div>
                                        <p class="text-xs text-zinc-500">par {{ $activity['user'] }} · {{ $activity['date'] }}</p>

                                        @if(count($activity['changes']) > 0)
                                            <div class="mt-2 space-y-1">
                                                @foreach($activity['changes'] as $change)
                                                    <div class="text-xs bg-zinc-50 rounded-md px-2.5 py-1.5 border border-zinc-100">
                                                        <span class="font-medium text-zinc-700">{{ $change['field'] }}</span>
                                                        <span class="text-zinc-400 mx-1">:</span>
                                                        <span class="text-red-600 line-through">{{ $change['old'] }}</span>
                                                        <span class="text-zinc-400 mx-1">→</span>
                                                        <span class="text-emerald-600 font-medium">{{ $change['new'] }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Fermer</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>