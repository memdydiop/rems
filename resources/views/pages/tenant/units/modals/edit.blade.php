<?php

use App\Enums\UnitStatus;
use App\Enums\TransactionType;
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


    public $transaction_type = 'rental';

    #[Validate('required_if:transaction_type,sale|nullable|numeric|min:0')]
    public $sale_price = null;

    #[Validate('nullable|numeric|min:0')]
    public $surface_area = null;

    #[Validate('nullable|string')]
    public $notes = '';

    #[Validate('nullable|integer|min:0')]
    public $rooms_count = null;

    #[Validate('nullable|integer|min:0')]
    public $bathrooms_count = null;

    #[Validate('nullable|integer')]
    public $floor_number = null;

    #[Validate('nullable|string')]
    public $electricity_meter_number = '';

    #[Validate('nullable|string')]
    public $water_meter_number = '';

    #[Validate('nullable|string')]
    public $kitchen_type = '';

    public $amenities = [];

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
        $this->transaction_type = $this->unit->transaction_type->value;
        $this->sale_price = $this->unit->sale_price;
        $this->surface_area = $this->unit->surface_area;
        $this->rooms_count = $this->unit->rooms_count;
        $this->bathrooms_count = $this->unit->bathrooms_count;
        $this->floor_number = $this->unit->floor_number;
        $this->electricity_meter_number = $this->unit->electricity_meter_number;
        $this->water_meter_number = $this->unit->water_meter_number;
        $this->kitchen_type = $this->unit->kitchen_type ?: '';
        $this->notes = $this->unit->notes;
        $this->amenities = $this->unit->amenities ?: [];

        Flux::modal('edit-unit')->show();
    }

    public function update()
    {
        $this->validate();

        $this->unit->update([
            'name' => $this->name,
            'type' => $this->type,
            'transaction_type' => $this->transaction_type,
            'sale_price' => $this->transaction_type === 'sale' ? $this->sale_price : null,
            'surface_area' => $this->surface_area ?: null,
            'rooms_count' => $this->rooms_count ?: null,
            'bathrooms_count' => $this->bathrooms_count ?: null,
            'floor_number' => $this->floor_number !== '' ? $this->floor_number : null,
            'electricity_meter_number' => $this->electricity_meter_number ?: null,
            'water_meter_number' => $this->water_meter_number ?: null,
            'kitchen_type' => $this->kitchen_type ?: null,
            'notes' => $this->notes ?: null,
            'amenities' => $this->amenities ?: [],
        ]);

        Flux::toast('Unité mise à jour avec succès.');
        Flux::modal('edit-unit')->close();
        $this->dispatch('unit-updated');
    }
};
?>

<flux:modal name="edit-unit" class="min-w-100">
    <form wire:submit="update" class="space-y-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Modifier l'unité</h2>
            <p class="text-sm text-gray-500">Modifier les informations de cette unité.</p>
        </div>

        <flux:input wire:model="name" label="Nom de l'unité" placeholder="ex: Appt 4B" />

        <div class="grid grid-cols-2 gap-4">
            <flux:select wire:model.live="transaction_type" label="Type de transaction">
                @foreach (TransactionType::cases() as $transactionType)
                    <flux:select.option value="{{ $transactionType->value }}">{{ $transactionType->label() }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="type" label="{{ __('Type d\'unité') }}" placeholder="Choisir...">
                <flux:select.option value="" disabled>Sélectionner</flux:select.option>

                <optgroup label="Résidentiel">
                    @foreach (\App\Enums\UnitType::cases() as $type)
                        @if(in_array($type->value, ['studio', 'apartment', 'room', 'entire_house']))
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
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:input wire:model="surface_area" type="number" step="0.01" label="Surface (m²) (Optionnel)" />

            @if($transaction_type === 'sale')
                <flux:input wire:model="sale_price" type="number" step="0.01" label="Prix de vente"
                    prefix="{{ config('app.currency', 'FCFA') }}" />
            @endif
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:input wire:model="rooms_count" type="number" label="Pièces" placeholder="0" />
            <flux:input wire:model="bathrooms_count" type="number" label="SDB" placeholder="0" />
            <flux:input wire:model="floor_number" type="number" label="Étage" placeholder="0" />
            <flux:select wire:model="kitchen_type" label="Cuisine">
                <flux:select.option value="">Non défini</flux:select.option>
                <flux:select.option value="independent">Séparée</flux:select.option>
                <flux:select.option value="open">Américaine / Ouverte</flux:select.option>
            </flux:select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="electricity_meter_number" label="Compteur CIE" placeholder="N° compteur élec." />
            <flux:input wire:model="water_meter_number" label="Compteur SODECI" placeholder="N° compteur eau" />
        </div>

        <flux:textarea wire:model="notes" label="Notes (Optionnel)" rows="2"
            placeholder="Informations complémentaires..." />

        <div class="space-y-3">
            <flux:label>Commodités (Parties privatives)</flux:label>
            <div class="grid grid-cols-2 gap-2">
                @foreach(\App\Enums\UnitAmenity::cases() as $amenity)
                    <flux:checkbox wire:model="amenities" :value="$amenity->value" :label="$amenity->label()" />
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Enregistrer</flux:button>
        </div>
    </form>
</flux:modal>