<?php

use App\Enums\PropertyType;
use App\Models\Property;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;

new class extends Component {
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('nullable|string|max:500')]
    public $address = '';

    #[Validate('required')]
    public $type = 'apartment'; // Default to existing value or specific enum case like PropertyType::Apartment->value

    #[Validate('nullable|exists:owners,id')]
    public $owner_id = '';

    public ?Property $property = null;
    public bool $modalOpen = false;

    #[On('open-modal')]
    public function open($name = null, $property = null)
    {
        if ($name === 'create-property') {
            $this->resetFields();
            $this->property = null;
            $this->modalOpen = true;
        }

        if ($name === 'edit-property' && $property) {
            $this->property = Property::find($property);
            if ($this->property) {
                $this->name = $this->property->name ?? '';
                $this->address = $this->property->address ?? '';
                $this->type = $this->property->type?->value ?? 'apartment';
                $this->owner_id = $this->property->owner_id ?? '';
                $this->modalOpen = true;
            }
        }
    }

    public function resetFields()
    {
        $this->name = '';
        $this->address = '';
        $this->type = 'apartment';
        $this->owner_id = '';
    }

    public function save()
    {
        $data = $this->validate();

        // Remove empty strings for nullable fields if necessary, specifically for owner_id if it's strictly UUID or null
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

        if ($this->property) {
            $this->property->update($data);
            $this->js("Flux.toast('Propriété mise à jour.')");
        } else {
            // Add default status
            $data['status'] = 'active';
            Property::create($data);
            $this->js("Flux.toast('Propriété créée avec succès.')");
        }

        $this->modalOpen = false;
        $this->dispatch('property-updated');
        return redirect()->route('tenant.properties.index');
    }
};
?>

<flux:modal wire:model="modalOpen" class="md:w-[28rem]">
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-bold text-zinc-900">
                {{ $property ? __('Modifier la propriété') : __('Nouvelle propriété') }}
            </h2>
            <p class="text-sm text-zinc-500">{{ __('Ajoutez les détails de la propriété ci-dessous.') }}</p>
        </div>

        <div class="space-y-4">
            <flux:input wire:model="name" label="{{ __('Nom') }}" placeholder="Résidence..." />
            <flux:textarea wire:model="address" label="{{ __('Adresse') }}" placeholder="Adresse complète..." />

            <flux:select wire:model="type" label="{{ __('Type') }}" placeholder="Choisir un type...">
                <flux:select.option value="" disabled>Sélectionner</flux:select.option>

                <optgroup label="Résidentiel">
                    @foreach (\App\Enums\PropertyType::cases() as $type)
                        @if(in_array($type->value, ['house', 'apartment', 'studio', 'duplex_triplex', 'multi_family', 'villa']))
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endif
                    @endforeach
                </optgroup>

                <optgroup label="Commercial">
                    @foreach (\App\Enums\PropertyType::cases() as $type)
                        @if(in_array($type->value, ['office', 'retail', 'restaurant', 'hotel']))
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endif
                    @endforeach
                </optgroup>

                <optgroup label="Industriel">
                    @foreach (\App\Enums\PropertyType::cases() as $type)
                        @if(in_array($type->value, ['warehouse', 'factory', 'industrial_space']))
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endif
                    @endforeach
                </optgroup>

                <optgroup label="Autre">
                    @foreach (\App\Enums\PropertyType::cases() as $type)
                        @if(in_array($type->value, ['land', 'parking']))
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endif
                    @endforeach
                </optgroup>
            </flux:select>

            <flux:select wire:model="owner_id" label="{{ __('Propriétaire') }}"
                placeholder="{{ __('Sélectionner un propriétaire...') }}">
                <flux:select.option value="">{{ __('Aucun') }}</flux:select.option>
                @foreach (\App\Models\Owner::all() as $owner)
                    <flux:select.option value="{{ $owner->id }}">{{ $owner->first_name }} {{ $owner->last_name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('modalOpen', false)">{{ __('Annuler') }}</flux:button>
            <flux:button variant="primary" wire:click="save">{{ __('Enregistrer') }}</flux:button>
        </div>
    </div>
</flux:modal>