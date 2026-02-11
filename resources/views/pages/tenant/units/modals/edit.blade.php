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

    #[Validate('required|numeric|min:0')]
    public $rent_amount = '';

    #[Validate('required|string')]
    public $type = '';

    public $status = '';

    public function mount()
    {
        // Initial state is empty, will be populated when modal opens
    }

    #[\Livewire\Attributes\On('edit-unit')]
    public function openModal($id)
    {
        $this->unit = Unit::findOrFail($id);
        $this->name = $this->unit->name;
        $this->rent_amount = $this->unit->rent_amount;
        $this->type = $this->unit->type->value;
        $this->status = $this->unit->status->value;

        Flux::modal('edit-unit')->show();
    }

    public function update()
    {
        $this->validate();

        $this->unit->update([
            'name' => $this->name,
            'type' => $this->type,
            'rent_amount' => $this->rent_amount,
            'status' => $this->status,
        ]);

        Flux::toast('Unité mise à jour avec succès.');
        Flux::modal('edit-unit')->close();

        $this->dispatch('unit-updated'); // Optional: to refresh parent if needed, though Livewire might handle it via model binding

        // Refresh the page or redirect to show updated data if needed
        return redirect()->route('tenant.units.show', $this->unit);
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
            <flux:button type="submit" variant="primary">Enregistrer</flux:button>
        </div>
    </form>
</flux:modal>