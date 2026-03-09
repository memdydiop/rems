<?php

use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Unit;
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

    #[Validate('nullable|string')]
    public $internal_notes = '';

    #[Validate('required|in:low,medium,high,urgent')]
    public $priority = '';

    #[Validate('required|in:pending,in_progress,resolved,cancelled')]
    public $status = '';

    #[Validate('required|exists:properties,id')]
    public $property_id = '';

    #[Validate('required|exists:units,id')]
    public $unit_id = '';

    #[Validate('nullable|string|max:255')]
    public $reported_by = '';

    #[Validate('nullable|string|max:50')]
    public $reporter_phone = '';


    public $category = 'unit';

    public $properties = [];
    public $units = [];

    public function mount()
    {
        $this->properties = Property::orderBy('name')->get();
        $this->units = Unit::orderBy('name')->get();
    }

    #[On('edit-maintenance')]
    public function open($id)
    {
        $this->request = MaintenanceRequest::findOrFail($id);

        $this->title = $this->request->title;
        $this->description = $this->request->description;
        $this->internal_notes = $this->request->internal_notes;
        $this->priority = $this->request->priority->value;
        $this->status = $this->request->status->value;
        $this->property_id = $this->request->property_id;
        $this->unit_id = $this->request->unit_id;
        $this->reported_by = $this->request->reported_by;
        $this->reporter_phone = $this->request->reporter_phone;
        $this->category = $this->request->category->value;

        if ($this->property_id) {
            $this->units = Unit::where('property_id', $this->property_id)->orderBy('name')->get();
        } else {
            $this->units = Unit::orderBy('name')->get();
        }

        $this->js("Flux.modal('unit-edit-maintenance').show()");
    }

    public function updatedPropertyId($value)
    {
        if ($value) {
            $this->units = Unit::where('property_id', $value)->orderBy('name')->get();
            if ($this->unit_id) {
                $unit = Unit::find($this->unit_id);
                if ($unit && $unit->property_id !== $value) {
                    $this->unit_id = '';
                }
            }
        } else {
            $this->units = Unit::orderBy('name')->get();
        }
    }

    public function updatedUnitId($value)
    {
        if ($value) {
            $unit = Unit::find($value);
            if ($unit && $unit->property_id) {
                $this->property_id = $unit->property_id;
            }
        }
    }

    public function save()
    {
        $this->validate();

        if (!$this->request->isEditable()) {
            Flux::toast('Ce ticket ne peut plus être modifié.', 'danger');
            $this->js("Flux.modal('unit-edit-maintenance').close()");
            return;
        }

        $this->request->update([
            'title' => $this->title,
            'description' => $this->description,
            'internal_notes' => $this->internal_notes,
            'priority' => $this->priority,
            'status' => $this->status,
            'category' => \App\Enums\MaintenanceCategory::Unit,
            'property_id' => $this->property_id,
            'unit_id' => $this->unit_id,
            'reported_by' => $this->reported_by,
            'reporter_phone' => $this->reporter_phone,
        ]);

        Flux::toast('Ticket mis à jour.', 'success');
        $this->js("Flux.modal('unit-edit-maintenance').close()");
        $this->dispatch('request-updated'); // Refresh parent
    }
};
?>

<flux:modal name="unit-edit-maintenance" class="min-w-125">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">Modifier le ticket (Lot)</flux:heading>
            <flux:subheading>Mettre à jour les informations de la demande.</flux:subheading>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="title" label="Titre" />

                <flux:select wire:model.live="priority" label="Priorité">
                    @foreach(\App\Enums\MaintenancePriority::cases() as $p)
                        <flux:select.option value="{{ $p->value }}">{{ $p->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model.live="property_id" label="Propriété" searchable>
                    @foreach($properties as $property)
                        <flux:select.option value="{{ $property->id }}">{{ $property->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="unit_id" label="Unité (Lot)" placeholder="Sélectionner une unité"
                    searchable>
                    <flux:select.option value="">— Aucune unité —</flux:select.option>
                    @foreach($units as $unit)
                        <flux:select.option value="{{ $unit->id }}">
                            {{ $unit->name }} {{ !$property_id ? '(' . $unit->property->name . ')' : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model.live="status" label="Statut" class="col-span-2">
                    @foreach(\App\Enums\MaintenanceStatus::cases() as $s)
                        <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="reported_by" label="Signalé par (optionnel)"
                    placeholder="ex: Le client, ou le gardien" />

                <flux:input wire:model="reporter_phone" label="Téléphone (optionnel)" placeholder="N° de téléphone" />
            </div>

            <flux:textarea wire:model="description" label="Description publique" rows="3" />

            <flux:textarea wire:model="internal_notes" label="Notes internes (Privé)" rows="3" />

            @if ($request && $request->photo_path)
                <div class="space-y-2">
                    <flux:label>Photo jointe</flux:label>
                    <a href="{{ asset('storage/' . $request->photo_path) }}" target="_blank" class="block">
                        <img src="{{ asset('storage/' . $request->photo_path) }}"
                            class="w-32 h-32 object-cover rounded-xl shadow-sm border border-zinc-200 hover:opacity-90 transition-opacity">
                    </a>
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Enregistrer</flux:button>
        </div>
    </form>
</flux:modal>