<?php

use App\Enums\UnitStatus;
use App\Models\Unit;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Flux\Flux;

new class extends Component {
    public ?Unit $unit = null;

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string')]
    public $type = '';


    #[Validate('nullable|numeric|min:0')]
    public $rent_amount = null;

    #[Validate('nullable|numeric|min:0')]
    public $surface_area = null;

    #[Validate('nullable|string')]
    public $notes = '';

    public function mount()
    {
        // Initial state is empty, will be populated when modal opens
    }

    #[\Livewire\Attributes\On('edit-unit')]
    public function openModal($id)
    {
        $this->unit = Unit::findOrFail($id);
        $this->name = $this->unit->name;
        $this->type = $this->unit->type->value;
        $this->rent_amount = $this->unit->rent_amount;
        $this->surface_area = $this->unit->surface_area;
        $this->notes = $this->unit->notes;

        Flux::modal('edit-unit')->show();
    }

    public function update()
    {
        $this->validate();

        $this->unit->update([
            'name' => $this->name,
            'type' => $this->type,
            'rent_amount' => $this->rent_amount ?: null,
            'surface_area' => $this->surface_area ?: null,
            'notes' => $this->notes ?: null,
        ]);

        Flux::toast('Unité mise à jour avec succès.');
        Flux::modal('edit-unit')->close();
        $this->dispatch('unit-updated');
    }
};
?>

<flux:modal name="edit-unit" class="min-w-[400px]">
    <form wire:submit="update" class="space-y-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Modifier l'unité</h2>
            <p class="text-sm text-gray-500">Modifier les informations de cette unité.</p>
        </div>

        <flux:input wire:model="name" label="Nom de l'unité" placeholder="ex: Appt 4B" />

        <flux:select wire:model="type" label="{{ __('Type') }}" placeholder="Choisir un type...">
            <flux:select.option value="" disabled>Sélectionner</flux:select.option>

            <optgroup label="Résidentiel">
                @foreach (\App\Enums\UnitType::cases() as $type)
                    @if(in_array($type->value, ['studio', 'f1', 'f2', 'f3', 'f4', 'f5_plus', 'room', 'entire_house']))
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
            <flux:button type="submit" variant="primary">Enregistrer</flux:button>
        </div>
    </form>
</flux:modal>