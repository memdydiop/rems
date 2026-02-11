<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\WithDataTable;

new
    #[Layout('layouts.app', ['title' => 'Journal d\'Activité'])]
    class extends Component {
    use WithPagination, WithDataTable;

    public string $type = '';

    #[Computed]
    public function activities()
    {
        $query = \Spatie\Activitylog\Models\Activity::query()
            ->with('causer')
            ->latest();

        if ($this->type) {
            $query->where('log_name', $this->type);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                    ->orWhere('subject_type', 'like', "%{$this->search}%");
            });
        }

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function logTypes()
    {
        return \Spatie\Activitylog\Models\Activity::distinct()
            ->pluck('log_name')
            ->filter()
            ->values();
    }
};
?>

<div>
    <x-layouts::content heading="Journal d'Activité" subheading="Historique des actions sur la plateforme.">

        <x-flux::card class="overflow-hidden">
            <x-flux::card.header title="Activités Récentes" class="border-b border-zinc-100 bg-white" />

            <x-flux::table :paginate="$this->activities" search linesPerPage>
                <x-slot:selectable>
                    <flux:select wire:model.live="type" placeholder="Filtrer par type" class="w-full md:w-48" size="sm">
                        <flux:select.option value="">Tous les types</flux:select.option>
                        @foreach($this->logTypes as $logType)
                            <flux:select.option value="{{ $logType }}">{{ ucfirst($logType) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </x-slot:selectable>

                <x-flux::table.columns>
                    <x-flux::table.column>Date</x-flux::table.column>
                    <x-flux::table.column>Utilisateur</x-flux::table.column>
                    <x-flux::table.column>Action</x-flux::table.column>
                    <x-flux::table.column>Cible (Sujet)</x-flux::table.column>
                    <x-flux::table.column>Type</x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @forelse($this->activities as $activity)
                        <x-flux::table.row :key="$activity->id">
                            <x-flux::table.cell class="whitespace-nowrap text-sm text-zinc-500">
                                {{ $activity->created_at->format('d/m/Y H:i') }}
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                @if($activity->causer)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar :name="$activity->causer->name ?? 'System'" size="xs" />
                                        <span
                                            class="text-sm font-medium text-zinc-900">{{ $activity->causer->name ?? 'System' }}</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <div class="size-6 rounded-full bg-zinc-100 flex items-center justify-center">
                                            <flux:icon.computer-desktop class="size-3 text-zinc-400" />
                                        </div>
                                        <span class="text-zinc-500 text-sm">Système</span>
                                    </div>
                                @endif
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <span class="text-sm text-zinc-700">{{ $activity->description }}</span>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                @if($activity->subject_type)
                                    <flux:badge size="sm" color="zinc" variant="pill">
                                        {{ class_basename($activity->subject_type) }}
                                        @if($activity->subject_id)
                                            <span class="opacity-50 ml-1">#{{ Str::limit($activity->subject_id, 8, '') }}</span>
                                        @endif
                                    </flux:badge>
                                @else
                                    <span class="text-zinc-300">-</span>
                                @endif
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                @if($activity->log_name)
                                    @php
                                        $color = match ($activity->log_name) {
                                            'created' => 'emerald',
                                            'updated' => 'blue',
                                            'deleted' => 'red',
                                            'login' => 'violet',
                                            default => 'zinc',
                                        };
                                    @endphp
                                    <flux:badge size="sm" :color="$color">{{ ucfirst($activity->log_name) }}</flux:badge>
                                @endif
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @empty
                        <x-flux::table.row>
                            <x-flux::table.cell colspan="5" class="text-center text-zinc-400 py-12">
                                <div class="flex flex-col items-center justify-center">
                                    <flux:icon.document-magnifying-glass class="size-8 text-zinc-300 mb-2" />
                                    <p>Aucune activité trouvée</p>
                                </div>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>

    </x-layouts::content>
</div>