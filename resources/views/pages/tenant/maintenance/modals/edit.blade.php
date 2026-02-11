<?php

use App\Models\MaintenanceRequest;
use App\Models\Property;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    public ?MaintenanceRequest $request = null;

    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('required|string')]
    public $description = '';

    #[Validate('required|in:low,medium,high')]
    public $priority = '';

    #[Validate('required|in:pending,in_progress,resolved,cancelled')]
    public $status = '';

    #[Validate('required|exists:properties,id')]
    public $property_id = '';

    public $properties = [];

    public function mount()
    {
        $this->properties = Property::all();
    }

    #[On('edit-maintenance')]
    public function open($id)
    {
        $this->request = MaintenanceRequest::findOrFail($id);

        $this->title = $this->request->title;
        $this->description = $this->request->description;
        $this->priority = $this->request->priority->value;
        $this->status = $this->request->status->value;
        $this->property_id = $this->request->property_id;

        $this->js("Flux.modal('edit-maintenance').show()");
    }

    public function save()
    {
        $this->validate();

        if (!$this->request->isEditable()) {
            Flux::toast('Ce ticket ne peut plus être modifié.', 'danger');
            $this->js("Flux.modal('edit-maintenance').close()");
            return;
        }

        $this->request->update([
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'property_id' => $this->property_id,
        ]);

        Flux::toast('Ticket mis à jour.', 'success');
        $this->js("Flux.modal('edit-maintenance').close()");
        $this->dispatch('request-updated'); // Refresh parent
    }
};
?>

<flux:modal name="edit-maintenance" class="min-w-125">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">Modifier le ticket</flux:heading>
            <flux:subheading>Mettre à jour les informations de la demande.</flux:subheading>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <flux:input wire:model="title" label="Titre" />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="property_id" label="Propriété">
                    @foreach($properties as $property)
                        <flux:select.option value="{{ $property->id }}">{{ $property->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="priority" label="Priorité">
                    @foreach(\App\Enums\MaintenancePriority::cases() as $priority)
                        <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:select wire:model="status" label="Statut">
                @foreach(\App\Enums\MaintenanceStatus::cases() as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="description" label="Description" rows="4" />
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Enregistrer</flux:button>
        </div>
    </form>
</flux:modal>