<?php

use App\Enums\{UnitStatus, UnitType};
use App\Models\Property;
use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    public Property $property;

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string')]
    public $type = ''; // Added type

    #[Validate('nullable|numeric|min:0')]
    public $rent_amount = null;

    #[Validate('nullable|numeric|min:0')]
    public $surface_area = null;

    #[Validate('nullable|string')]
    public $notes = '';

    public function create()
    {
        $this->validate();

        $this->property->units()->create([
            'name' => $this->name,
            'type' => $this->type,
            'rent_amount' => $this->rent_amount ?: null,
            'surface_area' => $this->surface_area ?: null,
            'notes' => $this->notes ?: null,
        ]);

        $this->reset(['name', 'type', 'rent_amount', 'surface_area', 'notes']);

        $this->js("Flux.toast('Unité ajoutée avec succès.')");
        $this->js("Flux.modal('create-unit').close()");

        return redirect()->route('tenant.properties.show', $this->property);
    }
};
?>

<flux:modal name="create-unit" class="min-w-[400px]">
    <form wire:submit="create" class="space-y-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Ajouter une unité</h2>
            <p class="text-sm text-gray-500">Ajouter une unité de location à cette propriété.</p>
        </div>

        <flux:input wire:model="name" label="Nom de l'unité" placeholder="ex: Appt 4B" />

        <flux:select wire:model="type" label="{{ __('Type') }}" placeholder="Choisir un type...">
            <flux:select.option value="" disabled>Sélectionner</flux:select.option>

            <optgroup label="Résidentiel">
                @foreach (UnitType::cases() as $type)
                    @if(in_array($type->value, ['studio', 'f1', 'f2', 'f3', 'f4', 'f5_plus', 'room', 'entire_house']))
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endif
                @endforeach
            </optgroup>

            <optgroup label="Commercial">
                @foreach (UnitType::cases() as $type)
                    @if(in_array($type->value, ['office', 'retail', 'restaurant', 'storage']))
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endif
                @endforeach
            </optgroup>

            <optgroup label="Autre">
                @foreach (UnitType::cases() as $type)
                    @if(in_array($type->value, ['parking', 'garage', 'land']))
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endif
                @endforeach
            </optgroup>
        </flux:select>


        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:input wire:model="surface_area" type="number" step="0.01" label="Surface (m²) (Optionnel)" />
            <flux:input wire:model="rent_amount" type="number" step="0.01" label="Loyer attendu (Optionnel)"
                prefix="{{ config('app.currency', 'FCFA') }}" />
        </div>

        <flux:textarea wire:model="notes" label="Notes (Optionnel)" rows="2"
            placeholder="Informations complémentaires..." />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Ajouter l'unité</flux:button>
        </div>
    </form>
</flux:modal>