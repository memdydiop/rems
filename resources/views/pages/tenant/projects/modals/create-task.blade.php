<?php

use App\Models\Project;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;

new class extends Component {
    public Project $project;

    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('nullable|string')]
    public $description = '';

    #[Validate('required|in:pending,in_progress,completed')]
    public $status = 'pending';

    #[On('open-modal')]
    public function open($name)
    {
        if ($name === 'create-task') {
            $this->reset(['title', 'description', 'status']);
            $this->js("Flux.modal('create-task').show()");
        }
    }

    public function create()
    {
        $this->validate();

        $this->project->tasks()->create([
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
        ]);

        $this->reset(['title', 'description', 'status']);

        $this->dispatch('task-created');
        $this->js("Flux.toast('Tâche créée avec succès.')");
        $this->js("Flux.modal('create-task').close()");
    }
};
?>

<flux:modal name="create-task" class="min-w-[400px]">
    <form wire:submit="create" class="space-y-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Ajouter une tâche</h2>
            <p class="text-sm text-gray-500">Créer une nouvelle tâche pour ce projet.</p>
        </div>

        <flux:input wire:model="title" label="Titre" placeholder="ex: Peindre les murs" />

        <flux:textarea wire:model="description" label="Description" placeholder="Détails sur la tâche..." />

        <flux:select wire:model="status" label="Statut">
            <flux:select.option value="pending">En attente</flux:select.option>
            <flux:select.option value="in_progress">En cours</flux:select.option>
            <flux:select.option value="completed">Terminée</flux:select.option>
        </flux:select>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Créer la tâche</flux:button>
        </div>
    </form>
</flux:modal>