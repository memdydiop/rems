<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Traits\WithDataTable;
use Spatie\Activitylog\Models\Activity;

new #[Layout('layouts.app', ['title' => 'Activity Log'])] class extends Component {
    use WithPagination, WithDataTable;

    public function mount()
    {
        $this->sortCol = 'created_at';
        $this->sortAsc = false;
    }

    public function with()
    {
        return [
            'activities' => Activity::with(['causer', 'subject'])
                ->where(function ($query) {
                    $query->whereDoesntHaveMorph('causer', [User::class], function ($q) {
                        $q->whereNotGhost();
                    })->orWhereNull('causer_id');
                })
                ->when($this->search, function ($query) {
                    $query->where('description', 'like', '%' . $this->search . '%')
                        ->orWhere('subject_type', 'like', '%' . $this->search . '%');
                })
                ->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc')
                ->paginate($this->perPage),
        ];
    }
};
?>

<div>
    <x-layouts::content heading="Journal d'activité"
        subheading="Piste d'audit des actions effectuées dans cet espace de travail.">

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <x-flux::card class="flex items-center gap-4 p-5">
                <div class="flex items-center justify-center size-12 rounded-full bg-green-50 text-green-600">
                    <flux:icon.bolt class="size-6" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500">Actions Aujourd'hui</span>
                    <span
                        class="text-2xl font-bold text-zinc-900">{{ Activity::whereDate('created_at', now())->count() }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-4 p-5">
                <div class="flex items-center justify-center size-12 rounded-full bg-zinc-50 text-zinc-600">
                    <flux:icon.shield-exclamation class="size-6" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500">Alertes Système</span>
                    <span class="text-2xl font-bold text-zinc-900">0</span>
                </div>
            </x-flux::card>
        </div>

        <x-flux::card>
            <x-flux::card.header>
                <x-flux::card.title>Historique des actions</x-flux::card.title>
                <div class="flex gap-2">
                    <flux:select wire:model.live="perPage" class="w-20" size="sm">
                        <flux:select.option value="10">10</flux:select.option>
                        <flux:select.option value="25">25</flux:select.option>
                        <flux:select.option value="50">50</flux:select.option>
                    </flux:select>
                    <flux:input wire:model.live="search" icon="magnifying-glass" size="sm"
                        placeholder="Rechercher une action..." class="max-w-xs" />
                </div>
            </x-flux::card.header>

            <x-flux::table :paginate="$activities">
                <x-flux::table.columns>
                    <x-flux::table.column>Action</x-flux::table.column>
                    <x-flux::table.column>Sujet</x-flux::table.column>
                    <x-flux::table.column>Auteur</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'created_at'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('created_at')">Date</x-flux::table.column>
                    <x-flux::table.column align="right"></x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @foreach ($activities as $activity)
                        <x-flux::table.row :key="$activity->id">
                            <x-flux::table.cell>
                                @php
                                    $desc = match ($activity->description) {
                                        'created' => 'Créé',
                                        'updated' => 'Mis à jour',
                                        'deleted' => 'Supprimé',
                                        default => $activity->description
                                    };
                                    $aColor = match ($activity->description) {
                                        'created' => 'green',
                                        'updated' => 'blue',
                                        'deleted' => 'red',
                                        default => 'zinc'
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$aColor" inset="top bottom">{{ $desc }}</flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                @php
                                    $subjectType = class_basename($activity->subject_type);
                                    $subjectName = match ($subjectType) {
                                        'Property' => 'Propriété',
                                        'Unit' => 'Unité',
                                        'Lease' => 'Bail',
                                        'Client' => 'Client',
                                        'RentPayment' => 'Paiement',
                                        'MaintenanceRequest' => 'Ticket',
                                        'Expense' => 'Dépense',
                                        'User' => 'Utilisateur',
                                        default => 'Élément'
                                    };

                                    if ($activity->subject) {
                                        $identifier = $activity->subject->name
                                            ?? $activity->subject->title
                                            ?? $activity->subject->reference
                                            ?? $activity->subject->first_name
                                            ?? ('#' . $activity->subject->id);
                                    } else {
                                        $identifier = '#' . $activity->subject_id;
                                    }
                                @endphp
                                <span class="font-medium text-zinc-900">{{ $subjectName }}</span>
                                <span class="text-zinc-500 text-xs ml-1">{{ $identifier }}</span>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                @if($activity->causer)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar :name="$activity->causer->name" size="xs" />
                                        <span class="text-sm font-medium text-zinc-700">{{ $activity->causer->name }}</span>
                                    </div>
                                @else
                                    <span class="text-zinc-400 text-sm italic">Système</span>
                                @endif
                            </x-flux::table.cell>
                            <x-flux::table.cell
                                class="text-zinc-500">{{ $activity->created_at->format('M j, Y H:i') }}</x-flux::table.cell>
                            <x-flux::table.cell align="right">
                                @if($activity->properties->has('attributes'))
                                    <flux:button variant="ghost" size="xs" icon="eye"
                                        wire:click="$dispatch('open-modal', { name: 'activity-details-{{ $activity->id }}' })" />

                                    <flux:modal name="activity-details" class="min-w-100">
                                        <div class="space-y-6">
                                            <div>
                                                <h3 class="font-bold text-lg text-zinc-900 mb-2">Modifications</h3>
                                                <div
                                                    class="bg-zinc-50 border border-zinc-200 p-4 rounded-lg overflow-auto max-h-60">
                                                    <pre
                                                        class="text-xs text-zinc-700 font-mono">{{ json_encode($activity->properties['attributes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            </div>

                                            @if($activity->properties->has('old'))
                                                <div>
                                                    <h3 class="font-bold text-lg text-zinc-900 mb-2">Anciennes Valeurs</h3>
                                                    <div
                                                        class="bg-zinc-50 border border-zinc-200 p-4 rounded-lg overflow-auto max-h-60">
                                                        <pre
                                                            class="text-xs text-zinc-700 font-mono">{{ json_encode($activity->properties['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </flux:modal>
                                @endif
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforeach
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>
    </x-layouts::content>
</div>