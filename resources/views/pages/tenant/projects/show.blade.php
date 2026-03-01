<?php

use App\Models\Project;
use App\Models\Task;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app', ['title' => 'Project Details'])] class extends Component {
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function deleteTask($taskId)
    {
        $this->project->tasks()->where('id', $taskId)->delete();
        $this->js("Flux.toast('Tâche supprimée.')");
    }

    public function toggleTask($taskId)
    {
        $task = $this->project->tasks()->find($taskId);

        if ($task) {
            $task->update([
                'status' => $task->status === 'completed' ? 'pending' : 'completed'
            ]);
            $this->js("Flux.toast('Statut de la tâche mis à jour.')");
        }
    }

    public function with()
    {
        return [
            'tasks' => $this->project->tasks()->latest()->paginate(10),
        ];
    }
};
?>

<div>
    <x-layouts::content :heading="$project->name" :subheading="$project->description">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="$dispatch('open-modal', { name: 'create-task' })">
                Ajouter une Tâche
            </flux:button>
        </x-slot:actions>

        <x-flux::card>
            <x-flux::card.header title="Tâches" />

            <x-flux::table :paginate="$tasks">
                <x-flux::table.columns>
                    <x-flux::table.column>Statut</x-flux::table.column>
                    <x-flux::table.column>Titre</x-flux::table.column>
                    <x-flux::table.column>Assigné à</x-flux::table.column>
                    <x-flux::table.column></x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @foreach ($tasks as $task)
                        <x-flux::table.row :key="$task->id">
                            <x-flux::table.cell>
                                @php
                                    $sLabel = match ($task->status) {
                                        'completed' => 'Terminé',
                                        'in_progress' => 'En cours',
                                        'pending' => 'En attente',
                                        default => ucfirst(str_replace('_', ' ', $task->status))
                                    };
                                @endphp
                                <flux:badge
                                    color="{{ $task->status === 'completed' ? 'green' : ($task->status === 'in_progress' ? 'yellow' : 'zinc') }}"
                                    size="sm" class="cursor-pointer" wire:click="toggleTask('{{ $task->id }}')">
                                    {{ $sLabel }}
                                </flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <span class="{{ $task->status === 'completed' ? 'line-through text-zinc-400' : '' }}">
                                    {{ $task->title }}
                                </span>
                                @if($task->description)
                                    <p class="text-xs text-zinc-500">{{ Str::limit($task->description, 50) }}</p>
                                @endif
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                {{ $task->assignee?->name ?? 'Non assigné' }}
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:button variant="ghost" size="xs" icon="trash"
                                    wire:click="deleteTask('{{ $task->id }}')" wire:confirm="Supprimer cette tâche ?"
                                    class="text-red-500" />
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforeach
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>
    </x-layouts::content>

    <livewire:pages::tenant.projects.modals.create-task :project="$project" />
</div>