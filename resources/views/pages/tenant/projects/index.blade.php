<?php

use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app', ['title' => 'Projets'])] class extends Component {
    use WithPagination;
    public $search = '';
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

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function delete(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        $this->js("Flux.toast('Project deleted successfully.')");
    }

    public function with()
    {
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', '!=', 'completed')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $completionRate = $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100) : 0;

        return [
            'projects' => Project::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                ->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc')
                ->paginate($this->perPage),
            'activeProjects' => $activeProjects,
            'completionRate' => $completionRate,
            'totalTasks' => \App\Models\Task::count(), // Assuming Task model exists
        ];
    }
};
?>

<div>
    <x-layouts::content heading="Projets" subheading="Gérez vos projets à long terme et vos rénovations majeures.">
        <x-slot name="actions">
            <flux:button icon="plus" wire:click="$dispatch('open-modal', { name: 'create-project' })">
                Nouveau Projet
            </flux:button>
        </x-slot>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <x-flux::card class="flex items-center gap-4 p-5">
                <div class="flex items-center justify-center size-12 rounded-full bg-blue-50 text-blue-600">
                    <flux:icon.briefcase class="size-6" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500">Projets Actifs</span>
                    <span class="text-2xl font-bold text-zinc-900">{{ $activeProjects }}</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-4 p-5">
                <div class="flex items-center justify-center size-12 rounded-full bg-green-50 text-green-600">
                    <flux:icon.chart-bar class="size-6" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500">Taux de Complétion</span>
                    <span class="text-2xl font-bold text-zinc-900">{{ $completionRate }}%</span>
                </div>
            </x-flux::card>

            <x-flux::card class="flex items-center gap-4 p-5">
                <div class="flex items-center justify-center size-12 rounded-full bg-orange-50 text-orange-600">
                    <flux:icon.check-circle class="size-6" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500">Total Tâches</span>
                    <span class="text-2xl font-bold text-zinc-900">{{ $totalTasks }}</span>
                </div>
            </x-flux::card>
        </div>

        <x-flux::card>
            <x-flux::card.header>
                <x-flux::card.title>Tous les Projets</x-flux::card.title>
                <div class="flex gap-2">
                    <flux:select wire:model.live="perPage" class="w-20" size="sm">
                        <flux:select.option value="10">10</flux:select.option>
                        <flux:select.option value="25">25</flux:select.option>
                        <flux:select.option value="50">50</flux:select.option>
                    </flux:select>
                    <flux:input wire:model.live="search" icon="magnifying-glass" size="sm"
                        placeholder="Rechercher des projets..." class="max-w-xs" />
                </div>
            </x-flux::card.header>

            <x-flux::table :paginate="$projects">
                <x-flux::table.columns>
                    <x-flux::table.column sortable :sorted="$sortCol === 'name'" :direction="$sortAsc ? 'asc' : 'desc'"
                        wire:click="sortBy('name')">Projet</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'status'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('status')">Statut</x-flux::table.column>
                    <x-flux::table.column align="right">Tâches</x-flux::table.column>
                    <x-flux::table.column sortable :sorted="$sortCol === 'created_at'" :direction="$sortAsc ? 'asc' : 'desc'" wire:click="sortBy('created_at')">Créé le</x-flux::table.column>
                    <x-flux::table.column align="right"></x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @foreach ($projects as $project)
                        <x-flux::table.row :key="$project->id">
                            <x-flux::table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                                        <flux:icon.briefcase class="size-5 text-orange-600" />
                                    </div>
                                    <div class="flex flex-col">
                                        <a href="{{ route('tenant.projects.show', $project) }}"
                                            class="font-medium text-zinc-900 hover:underline" wire:navigate>
                                            {{ $project->name }}
                                        </a>
                                        <span
                                            class="text-xs text-zinc-500">{{ $project->property->name ?? 'Global' }}</span>
                                    </div>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                @php
                                    $sLabel = match ($project->status) {
                                        'completed' => 'Terminé',
                                        'in_progress' => 'En cours',
                                        'pending' => 'En attente',
                                        default => ucfirst($project->status),
                                    };
                                    $sColor = match ($project->status) {
                                        'completed' => 'green',
                                        'in_progress' => 'blue',
                                        'pending' => 'zinc',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$sColor" inset="top bottom">{{ $sLabel }}</flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell align="right"
                                class="font-medium text-zinc-900">{{ $project->tasks()->count() }}</x-flux::table.cell>
                            <x-flux::table.cell
                                class="text-zinc-500">{{ $project->created_at->format('M j, Y') }}</x-flux::table.cell>
                            <x-flux::table.cell align="right">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" :href="route('tenant.projects.show', $project)"
                                            wire:navigate>Voir le Projet</flux:menu.item>
                                        <flux:menu.separator />
                                        @can('delete', $project)
                                            <flux:menu.item icon="trash" wire:click="delete({{ $project->id }})"
                                                wire:confirm="Êtes-vous sûr ?" variant="danger">Supprimer</flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforeach
                </x-flux::table.rows>
            </x-flux::table>

            @if ($projects->isEmpty())
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center size-12 rounded-full bg-zinc-100 mb-4">
                        <flux:icon.briefcase class="size-6 text-zinc-400" />
                    </div>
                    <h3 class="text-lg font-medium text-zinc-900">Aucun projet trouvé</h3>
                    <p class="text-zinc-500 max-w-sm mx-auto mt-1">
                        Commencez par créer votre premier projet.
                    </p>
                </div>
            @endif
        </x-flux::card>
    </x-layouts::content>

    <livewire:pages::tenant.projects.modals.create />
</div>