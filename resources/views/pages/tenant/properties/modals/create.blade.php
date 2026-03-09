<?php

use App\Enums\{PropertyType, PropertyAmenity, TransactionType};
use App\Models\{Property, Owner};
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;

new class extends Component {
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('nullable|string|max:500')]
    public $address = '';

    #[Validate('required')]
    public $type = 'residential_building';

    #[Validate('nullable|exists:owners,id')]
    public $owner_id = '';

    // Property & Standalone unit fields
    public $transaction_type = 'rental';
    public $sale_price = '';
    public $surface_area = '';
    public $rent_amount = '';
    public $notes = '';
    public $amenities = [];

    // Property & Standalone unit fields

    // Property & Standalone unit fields

    public ?Property $property = null;
    public bool $modalOpen = false;
    public bool $fromOwner = false;

    #[On('open-modal')]
    public function open($name = null, $property = null, $owner_id = null)
    {
        if ($name === 'create-property') {
            $this->resetFields();
            $this->property = null;
            $this->fromOwner = false;
            // Pre-fill owner if passed (e.g. from owner detail page)
            if ($owner_id) {
                $this->owner_id = $owner_id;
                $this->fromOwner = true;
            }
            $this->modalOpen = true;
        }

        if ($name === 'edit-property' && $property) {
            $this->property = Property::find($property);
            if ($this->property) {
                $this->name = $this->property->name ?? '';
                $this->address = $this->property->address ?? '';
                $this->type = $this->property->type?->value ?? 'apartment';
                $this->transaction_type = $this->property->transaction_type ?? 'rental';
                $this->sale_price = $this->property->sale_price ?? '';
                $this->owner_id = $this->property->owner_id ?? '';
                $this->amenities = $this->property->amenities ?? [];
                $this->amenities = $this->property->amenities ?? [];
                // For standalone properties, load unit details if available
                if ($this->property->type?->isStandalone() && $this->property->units->isNotEmpty()) {
                    $unit = $this->property->units->first();
                    $this->surface_area = $unit->surface_area ?? '';
                    $this->rent_amount = $unit->rent_amount ?? '';
                    $this->notes = $unit->notes ?? '';
                }
                $this->fromOwner = false;
                $this->isCreatingOwner = false;
                $this->modalOpen = true;
            }
        }
    }

    public function resetFields()
    {
        $this->name = '';
        $this->address = '';
        $this->type = 'residential_building';
        $this->transaction_type = 'rental';
        $this->sale_price = '';
        $this->surface_area = '';
        $this->rent_amount = '';
        $this->notes = '';
        $this->amenities = [];
        $this->amenities = [];
        $this->isCreatingOwner = false;
        $this->newOwnerFirstName = '';
        $this->newOwnerLastName = '';
        $this->newOwnerEmail = '';
        $this->newOwnerPhone = '';
    }

    public function toggleCreateOwner()
    {
        $this->dispatch('open-modal', name: 'create-owner');
    }

    #[On('owner-created')]
    public function onOwnerCreated($ownerId)
    {
        $this->owner_id = $ownerId;
    }

    public function save()
    {
        if (auth()->check() && !auth()->user()->hasVerifiedEmail()) {
            $this->addError('base', __("Veuillez vérifier votre adresse email pour effectuer cette action."));
            Flux\Flux::toast(__('Veuillez vérifier votre adresse email.'), 'danger');
            return;
        }

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'type' => 'required',
            'owner_id' => 'nullable|exists:owners,id',
            'surface_area' => 'nullable|numeric|min:0',
            'rent_amount' => 'nullable|numeric|min:0',
            'transaction_type' => 'required|string|in:' . implode(',', array_column(TransactionType::cases(), 'value')),
            'sale_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'amenities' => 'nullable|array',
            'amenities.*' => 'in:' . implode(',', array_column(PropertyAmenity::cases(), 'value')),
        ]);

        // Remove empty strings for nullable fields if necessary
        if ($data['owner_id'] === '') {
            $data['owner_id'] = null;
        }

        // Check limits before creating
        if (!$this->property) {
            $currentCount = Property::count();
            if (!tenant()->canCreate('max_properties', $currentCount)) {
                $this->addError('base', __("Limite du forfait atteinte. Passez au forfait supérieur pour ajouter plus de propriétés."));
                Flux\Flux::toast(__('Limite du forfait atteinte.'), 'danger');
                return;
            }
        }

        // Prepare common property data
        $propertyData = [
            'name' => $data['name'],
            'address' => $data['address'],
            'type' => $data['type'],
            'owner_id' => $data['owner_id'],
            'amenities' => $data['amenities'] ?: [],
            'transaction_type' => $data['transaction_type'],
        ];

        if ($this->property) {
            $this->property->update($propertyData);

            // Update unit if standalone
            if ($this->property->type?->isStandalone() && $this->property->units->isNotEmpty()) {
                $unit = $this->property->units->first();
                $unit->update([
                    'transaction_type' => $data['transaction_type'],
                    'sale_price' => $data['transaction_type'] === 'sale' ? ($data['sale_price'] ?: null) : null,
                    'rent_amount' => $data['transaction_type'] === 'rental' ? ($data['rent_amount'] ?: null) : null,
                    'surface_area' => $data['surface_area'] ?: null,
                    'notes' => $data['notes'] ?: null,
                    'amenities' => $data['amenities'] ?: [],
                ]);
            }
            $this->js("Flux.toast('Propriété mise à jour.')");
        } else {
            // Add default status
            $propertyData['status'] = 'active';
            $property = Property::create($propertyData);

            // Handle Standalone Property (Auto-create unit)
            $propertyType = PropertyType::from($this->type);
            if ($propertyType->isStandalone()) {
                $unitType = $this->getDefaultUnitType($propertyType);
                $property->units()->create([
                    'name' => $property->name,
                    'type' => $unitType,
                    'transaction_type' => $data['transaction_type'],
                    'sale_price' => $data['transaction_type'] === 'sale' ? ($data['sale_price'] ?: null) : null,
                    'rent_amount' => $data['transaction_type'] === 'rental' ? ($data['rent_amount'] ?: null) : null,
                    'surface_area' => $data['surface_area'] ?: null,
                    'notes' => $data['notes'] ?: null,
                    'amenities' => $data['amenities'] ?: [],
                    'status' => 'vacant',
                ]);
            }
            $this->js("Flux.toast('Propriété créée avec succès.')");
        }

        $this->modalOpen = false;
        $this->dispatch('property-updated');

        if (!$this->fromOwner) {
            return redirect()->route('tenant.properties.index');
        }
    }

    protected function getDefaultUnitType(PropertyType $propertyType): string
    {
        return match ($propertyType) {
            PropertyType::Villa, PropertyType::House => 'entire_house',
            PropertyType::Warehouse => 'storage',
            PropertyType::Land => 'land',
            PropertyType::Factory, PropertyType::IndustrialComplex => 'storage', // Fallback
            default => 'studio',
        };
    }
};
?>

<flux:modal wire:model="modalOpen" class="md:w-xl">
    <form wire:submit="save" class="space-y-6">
        <div>
            <h2 class="text-lg font-bold text-zinc-900">
                {{ $property ? __('Modifier la propriété') : __('Nouvelle propriété') }}
            </h2>
            <p class="text-sm text-zinc-500">{{ __('Ajoutez les détails de la propriété ci-dessous.') }}</p>
        </div>

        <div class="space-y-4">
            <flux:input wire:model="name" label="{{ __('Nom') }}" placeholder="Résidence..." />
            <flux:textarea wire:model="address" label="{{ __('Adresse') }}" placeholder="Adresse complète..." />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model.live="type" label="{{ __('Type') }}" placeholder="Choisir un type...">

                    <optgroup label="Résidentiel">
                        @foreach (PropertyType::cases() as $type_case)
                            @if($type_case->category() === 'residential')
                                <flux:select.option value="{{ $type_case->value }}">{{ $type_case->label() }}
                                </flux:select.option>
                            @endif
                        @endforeach
                    </optgroup>

                    <optgroup label="Commercial">
                        @foreach (PropertyType::cases() as $type_case)
                            @if($type_case->category() === 'commercial')
                                <flux:select.option value="{{ $type_case->value }}">{{ $type_case->label() }}
                                </flux:select.option>
                            @endif
                        @endforeach
                    </optgroup>

                    <optgroup label="Mixte">
                        @foreach (PropertyType::cases() as $type_case)
                            @if($type_case->category() === 'mixed')
                                <flux:select.option value="{{ $type_case->value }}">{{ $type_case->label() }}
                                </flux:select.option>
                            @endif
                        @endforeach
                    </optgroup>

                    <optgroup label="Industriel">
                        @foreach (PropertyType::cases() as $type_case)
                            @if($type_case->category() === 'industrial')
                                <flux:select.option value="{{ $type_case->value }}">{{ $type_case->label() }}
                                </flux:select.option>
                            @endif
                        @endforeach
                    </optgroup>

                    <optgroup label="Terrain">
                        @foreach (PropertyType::cases() as $type_case)
                            @if($type_case->category() === 'land')
                                <flux:select.option value="{{ $type_case->value }}">{{ $type_case->label() }}
                                </flux:select.option>
                            @endif
                        @endforeach
                    </optgroup>
                </flux:select>

                @if(!$fromOwner)
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <flux:select class="" wire:model="owner_id" label="{{ __('Propriétaire') }}"
                                placeholder="{{ __('Sélectionner...') }}">
                                <flux:select.option value="">{{ __('Aucun') }}</flux:select.option>
                                @foreach (Owner::orderBy('last_name')->get() as $owner)
                                    <flux:select.option value="{{ $owner->id }}">{{ $owner->full_name }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <flux:button variant="primary" wire:click="toggleCreateOwner" icon="plus"
                            tooltip="Nouveau propriétaire" />
                    </div>
                @endif
            </div>

            <div class="space-y-4 pt-2 border-t border-zinc-100">
                <flux:select wire:model.live="transaction_type" label="Type de transaction">
                    @foreach (TransactionType::cases() as $transaction_type_case)
                        <flux:select.option value="{{ $transaction_type_case->value }}">
                            {{ $transaction_type_case->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            @if(PropertyType::tryFrom($type)?->isStandalone() && !$property)
                <div class="space-y-4 pt-2 border-t border-zinc-100">
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="surface_area" type="number" step="0.01" label="Surface (m²) (Optionnel)" />

                        @if($transaction_type === 'rental')
                            <flux:input wire:model="rent_amount" type="number" step="0.01" label="Loyer attendu (Optionnel)"
                                prefix="{{ config('app.currency', 'FCFA') }}" />
                        @else
                            <flux:input wire:model="sale_price" type="number" step="0.01" label="Prix de vente (Optionnel)"
                                prefix="{{ config('app.currency', 'FCFA') }}" />
                        @endif
                    </div>

                    <flux:textarea wire:model="notes" label="Notes (Optionnel)" rows="2"
                        placeholder="Informations complémentaires..." />
                </div>
            @endif

            <div class="space-y-3 pt-4 border-t border-zinc-100">
                <flux:label>
                    {{ PropertyType::tryFrom($type)?->isStandalone() ? 'Commodités' : 'Commodités Communes (Immeuble)' }}
                </flux:label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach(PropertyAmenity::cases() as $amenity)
                        <flux:checkbox wire:model="amenities" :value="$amenity->value" :label="$amenity->label()" />
                    @endforeach
                </div>
            </div>

        </div>

        <div class="flex justify-end gap-2 pt-2">
            <flux:button variant="ghost" wire:click="$set('modalOpen', false)">{{ __('Annuler') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Enregistrer') }}</flux:button>
        </div>
    </form>
</flux:modal>