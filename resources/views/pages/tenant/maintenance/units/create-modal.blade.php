<?php

use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Unit;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Models\Renter;
use Flux\Flux;

new class extends Component {
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('required|string')]
    public $description = '';

    #[Validate('required|in:low,medium,high,urgent')]
    public $priority = 'medium';

    #[Validate('required|exists:properties,id')]
    public $property_id = '';

    #[Validate('required|exists:units,id')]
    public $unit_id = '';

    #[Validate('nullable|string|max:255')]
    public $reported_by = '';

    #[Validate('nullable|string|max:50')]
    public $reporter_phone = '';


    #[Validate('nullable|image|max:10240')]
    public $photo;

    public $properties = [];
    public $units = [];

    public $category = 'unit';

    #[Validate('nullable|string')]
    public $internal_notes = '';

    public function mount()
    {
        $this->properties = Property::orderBy('name')->get();
        $this->units = Unit::orderBy('name')->get();
    }

    #[On('open-modal')]
    public function open($name)
    {
        $target = is_array($name) ? ($name['name'] ?? null) : $name;
        $params = is_array($name) ? $name : [];

        if ($target === 'unit-create-maintenance') {
            $this->reset(['title', 'description', 'internal_notes', 'priority', 'property_id', 'unit_id', 'photo', 'reported_by', 'reporter_phone']);

            // Recharger tout par défaut
            $this->properties = Property::orderBy('name')->get();
            $this->units = Unit::orderBy('name')->get();

            // Si un unit_id est passé (ex: depuis la page détail d'une unité)
            if (isset($params['unit_id'])) {
                $unit = Unit::with('property')->find($params['unit_id']);
                if ($unit) {
                    $this->unit_id = $unit->id;
                    $this->property_id = $unit->property_id;
                    $this->units = Unit::where('property_id', $unit->property_id)->get();
                }
            }

            $this->js("Flux.modal('unit-create-maintenance').show()");
        }
    }

    public function updatedPropertyId($value)
    {
        if ($value) {
            $this->units = Unit::where('property_id', $value)->orderBy('name')->get();
            // Si l'unité sélectionnée n'appartient plus à la nouvelle propriété, on reset l'unité
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

        $photoPath = $this->photo ? $this->photo->store('maintenance-photos', 'public') : null;

        MaintenanceRequest::create([
            'title' => $this->title,
            'description' => $this->description,
            'internal_notes' => $this->internal_notes,
            'category' => \App\Enums\MaintenanceCategory::Unit,
            'priority' => $this->priority,
            'property_id' => $this->property_id,
            'unit_id' => $this->unit_id,
            'reported_by' => $this->reported_by,
            'reporter_phone' => $this->reporter_phone,
            'status' => 'pending',
            'user_id' => auth()->id(),
            'photo_path' => $photoPath,
        ]);

        $this->reset(['title', 'description', 'internal_notes', 'priority', 'property_id', 'unit_id', 'photo', 'reported_by', 'reporter_phone']);
        $this->units = Unit::orderBy('name')->get();
        $this->dispatch('request-created');
        Flux::toast('Ticket créé avec succès.', 'success');
        $this->js("Flux.modal('unit-create-maintenance').close()");
    }
};
?>

<flux:modal name="unit-create-maintenance" class="min-w-100 md:w-150">
    <form wire:submit="save" class="space-y-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Nouvelle demande de maintenance (Lot)</h2>
            <p class="text-sm text-gray-500">Enregistrer un nouveau problème dans les parties privatives.</p>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="title" label="Titre" placeholder="ex: Robinet qui fuit" />

                <flux:select wire:model.live="priority" label="Priorité">
                    @foreach(\App\Enums\MaintenancePriority::cases() as $p)
                        <flux:select.option value="{{ $p->value }}">{{ $p->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model.live="property_id" label="Propriété" placeholder="Sélectionner une propriété"
                    searchable>
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


            <flux:textarea wire:model="description" label="Description publique / pour le prestataire"
                placeholder="Décrivez le problème en détail..." />

            <div class="space-y-3">
                <flux:input wire:model="photo" type="file" label="Photo du problème (optionnel)" />

                @if ($photo)
                    <div class="relative inline-block">
                        <img src="{{ $photo->temporaryUrl() }}"
                            class="w-32 h-32 object-cover rounded-xl shadow-sm border border-zinc-200">
                        <button type="button" wire:click="$set('photo', null)"
                            class="absolute -top-2 -right-2 bg-white rounded-full shadow-md p-1 border border-zinc-200 hover:bg-zinc-50">
                            <flux:icon.x-mark class="size-4 text-zinc-500" />
                        </button>
                    </div>
                @endif
            </div>

            <flux:textarea wire:model="internal_notes" label="Notes internes (Privé)"
                placeholder="Notes pour la gestion, budget estimé, etc." />
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Soumettre la demande</flux:button>
        </div>
    </form>
</flux:modal>