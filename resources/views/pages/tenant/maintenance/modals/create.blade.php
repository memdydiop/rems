<?php

use App\Models\MaintenanceRequest;
use App\Models\Property;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('required|string')]
    public $description = '';

    #[Validate('required|in:low,medium,high')]
    public $priority = 'medium';

    #[Validate('required|exists:properties,id')]
    public $property_id = '';

    public $properties = [];

    public function mount()
    {
        $this->properties = Property::all();
    }

    #[On('open-modal')]
    public function open($name)
    {
        $target = is_array($name) ? ($name['name'] ?? null) : $name;

        if ($target === 'create-maintenance') {
            $this->reset(['title', 'description', 'priority', 'property_id']);
            $this->js("Flux.modal('create-maintenance').show()");
        }
    }

    public function save()
    {
        $this->validate();

        MaintenanceRequest::create([
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'property_id' => $this->property_id,
            'status' => 'pending',
            'user_id' => auth()->id(),
        ]);

        $this->reset();
        $this->reset();
        $this->dispatch('request-created');
        Flux::toast('Ticket créé avec succès.', 'success');
        $this->js("Flux.modal('create-maintenance').close()");
    }
};
?>

<flux:modal name="create-maintenance" class="min-w-125">
    <form wire:submit="save" class="space-y-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Nouvelle demande de maintenance</h2>
            <p class="text-sm text-gray-500">Enregistrer un nouveau problème pour une propriété.</p>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <flux:input wire:model="title" label="Titre" placeholder="ex: Robinet qui fuit" />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="property_id" label="Propriété" placeholder="Sélectionner une propriété">
                    @foreach($properties as $property)
                        <flux:select.option value="{{ $property->id }}">{{ $property->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="priority" label="Priorité">
                    <flux:select.option value="low">Basse</flux:select.option>
                    <flux:select.option value="medium">Moyenne</flux:select.option>
                    <flux:select.option value="high">Haute</flux:select.option>
                </flux:select>
            </div>

            <flux:textarea wire:model="description" label="Description"
                placeholder="Décrivez le problème en détail..." />
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Soumettre la demande</flux:button>
        </div>
    </form>
</flux:modal>