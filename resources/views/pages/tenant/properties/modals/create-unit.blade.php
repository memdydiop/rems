<?php

use App\Enums\{UnitStatus, UnitType, TransactionType};
use App\Models\Property;
use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    public Property $property;

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string')]
    public $type = ''; // Added type

    #[Validate('required|string')]
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
    public $water_meter_number = '';

    #[Validate('nullable|string')]
    public $kitchen_type = '';

    public $amenities = [];

    public function create()
    {
        if ($this->property->type->isStandalone()) {
            $this->addError('name', 'Impossible d\'ajouter des unités supplémentaires à une propriété de type ' . $this->property->type->label() . '.');
            return;
        }

        $this->validate();

        $this->property->units()->create([
            'name' => $this->name,
            'type' => $this->type,
            'transaction_type' => $this->transaction_type,
            'sale_price' => $this->transaction_type === 'sale' ? ($this->sale_price ?: null) : null,
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

        $this->reset(['name', 'type', 'transaction_type', 'sale_price', 'surface_area', 'rooms_count', 'bathrooms_count', 'floor_number', 'electricity_meter_number', 'water_meter_number', 'kitchen_type', 'notes', 'amenities']);

        $this->js("Flux.toast('Unité ajoutée avec succès.')");
        $this->js("Flux.modal('create-unit').close()");

        return redirect()->route('tenant.properties.show', $this->property);
    }
};
?>

<flux:modal name="create-unit" class="min-w-100">
    <form wire:submit="create" class="space-y-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Ajouter une unité</h2>
            <p class="text-sm text-gray-500">Ajouter une unité de location à cette propriété.</p>
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
                    @foreach (UnitType::cases() as $type)
                        @if(in_array($type->value, ['studio', 'apartment', 'villa', 'room', 'entire_house']))
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
            <flux:button type="submit" variant="primary">Ajouter l'unité</flux:button>
        </div>
    </form>
</flux:modal>