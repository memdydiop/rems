<?php

use App\Enums\UnitStatus;
use App\Models\Property;
use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    public Property $property;

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|numeric|min:0')]
    public $rent_amount = '';

    #[Validate('required|string')]
    public $type = ''; // Added type

    public string $status = 'vacant';

    public function create()
    {
        $this->validate();

        $this->property->units()->create([
            'name' => $this->name,
            'type' => $this->type,
            'rent_amount' => $this->rent_amount,
            'status' => $this->status,
        ]);

        $this->reset(['name', 'type', 'rent_amount', 'status']);

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
                @foreach (\App\Enums\UnitType::cases() as $type)
                    @if(in_array($type->value, ['apartment', 'studio', 'room', 'entire_house']))
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endif
                @endforeach
            </optgroup>

            <optgroup label="Commercial">
                @foreach (\App\Enums\UnitType::cases() as $type)
                    @if(in_array($type->value, ['office', 'retail', 'restaurant', 'storage']))
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endif
                @endforeach
            </optgroup>

            <optgroup label="Autre">
                @foreach (\App\Enums\UnitType::cases() as $type)
                    @if(in_array($type->value, ['parking', 'garage', 'land']))
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endif
                @endforeach
            </optgroup>
        </flux:select>

        <flux:input wire:model="rent_amount" label="Montant du loyer (XOF)" type="number" step="0.01" />

        <flux:select wire:model="status" label="Statut">
            @foreach (UnitStatus::cases() as $unitStatus)
                <flux:select.option value="{{ $unitStatus->value }}">{{ $unitStatus->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Ajouter l'unité</flux:button>
        </div>
    </form>
</flux:modal>