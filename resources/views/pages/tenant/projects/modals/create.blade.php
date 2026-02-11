<?php

use App\Models\Project;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public $name = '';
    public $description = '';

    #[On('open-modal')]
    public function open($name)
    {
        $target = is_array($name) ? ($name['name'] ?? null) : $name;

        if ($target === 'create-project') {
            $this->reset();
            $this->js("Flux.modal('create-project').show()");
        }
    }

    public function create()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Project::create([
            'name' => $this->name,
            'description' => $this->description,
            'status' => 'active',
        ]);

        $this->js("Flux.modal('create-project').close()");
        $this->js("Flux.toast('Projet créé avec succès.')");
        $this->dispatch('$refresh')->to('pages.tenant.projects.index');
    }
};
?>

<flux:modal name="create-project" class="min-w-120">
    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-xl font-medium">Créer un projet</h1>
            <p class="text-sm text-zinc-500">Ajouter un nouveau projet à votre espace de travail.</p>
        </div>

        <form wire:submit="create" class="flex flex-col gap-6">
            <flux:input wire:model="name" label="Nom" placeholder="ex: Campagne Marketing" />
            <flux:textarea wire:model="description" label="Description" placeholder="Détails du projet..." />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Annuler</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Créer le projet</flux:button>
            </div>
        </form>
    </div>
</flux:modal>