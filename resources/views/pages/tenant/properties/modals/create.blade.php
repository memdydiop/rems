<?php

use App\Enums\PropertyType;
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

    // New Owner Fields
    public bool $isCreatingOwner = false;
    public $newOwnerFirstName = '';
    public $newOwnerLastName = '';
    public $newOwnerEmail = '';
    public $newOwnerPhone = '';

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
                $this->owner_id = $this->property->owner_id ?? '';
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
        $this->owner_id = '';
        $this->isCreatingOwner = false;
        $this->newOwnerFirstName = '';
        $this->newOwnerLastName = '';
        $this->newOwnerEmail = '';
        $this->newOwnerPhone = '';
    }

    public function toggleCreateOwner()
    {
        $this->isCreatingOwner = !$this->isCreatingOwner;
        if ($this->isCreatingOwner) {
            $this->owner_id = ''; // Clear selection if switching to create mode
        }
    }

    public function save()
    {
        if (auth()->check() && !auth()->user()->hasVerifiedEmail()) {
            $this->addError('base', __("Veuillez vérifier votre adresse email pour effectuer cette action."));
            Flux\Flux::toast(__('Veuillez vérifier votre adresse email.'), 'danger');
            return;
        }

        $data = $this->validate();

        // Handle Inline Owner Creation
        if ($this->isCreatingOwner && !$this->fromOwner) {
            $ownerData = $this->validate([
                'newOwnerFirstName' => 'required|string|max:255',
                'newOwnerLastName' => 'required|string|max:255',
                'newOwnerEmail' => 'nullable|email|max:255',
                'newOwnerPhone' => 'nullable|string|max:20',
            ], [
                'newOwnerFirstName.required' => 'Le prénom du propriétaire est requis.',
                'newOwnerLastName.required' => 'Le nom du propriétaire est requis.',
            ]);

            $owner = Owner::create([
                'first_name' => $this->newOwnerFirstName,
                'last_name' => $this->newOwnerLastName,
                'email' => $this->newOwnerEmail,
                'phone' => $this->newOwnerPhone,
            ]);

            $data['owner_id'] = $owner->id;
        } else {
            // Remove empty strings for nullable fields if necessary
            if ($data['owner_id'] === '') {
                $data['owner_id'] = null;
            }
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

        if (!$this->fromOwner) {
            return redirect()->route('tenant.properties.index');
        }
    }
};
?>

<flux:modal wire:model="modalOpen" class="md:w-[28rem]">
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

            <flux:select wire:model="type" label="{{ __('Type') }}" placeholder="Choisir un type...">
                <flux:select.option value="" disabled>Sélectionner</flux:select.option>

                <optgroup label="Résidentiel">
                    @foreach (PropertyType::cases() as $type)
                        @if(in_array($type->value, ['residential_building', 'villa', 'house', 'compound']))
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endif
                    @endforeach
                </optgroup>

                <optgroup label="Commercial">
                    @foreach (PropertyType::cases() as $type)
                        @if(in_array($type->value, ['commercial_building', 'shopping_center', 'hotel']))
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endif
                    @endforeach
                </optgroup>

                <optgroup label="Mixte">
                    @foreach (PropertyType::cases() as $type)
                        @if(in_array($type->value, ['mixed_use']))
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endif
                    @endforeach
                </optgroup>

                <optgroup label="Industriel">
                    @foreach (PropertyType::cases() as $type)
                        @if(in_array($type->value, ['warehouse', 'factory', 'industrial_complex']))
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endif
                    @endforeach
                </optgroup>

                <optgroup label="Terrain">
                    @foreach (PropertyType::cases() as $type)
                        @if(in_array($type->value, ['land']))
                            <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                        @endif
                    @endforeach
                </optgroup>
            </flux:select>

            @if(!$fromOwner)
                <div class="border-t border-zinc-200 py-4 mt-2">
                    @if(!$isCreatingOwner)
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <flux:select wire:model="owner_id" label="{{ __('Propriétaire') }}"
                                    placeholder="{{ __('Sélectionner un propriétaire...') }}">
                                    <flux:select.option value="">{{ __('Aucun') }}</flux:select.option>
                                    @foreach (Owner::all() as $owner)
                                        <flux:select.option value="{{ $owner->id }}">{{ $owner->first_name }}
                                            {{ $owner->last_name }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <flux:button variant="primary" wire:click="toggleCreateOwner" icon="plus" class=""
                                tooltip="Nouveau propriétaire"/>
                        </div>
                    @else
                        <div class="space-y-4 bg-zinc-50 p-4 rounded-xl border border-zinc-100">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-sm font-medium text-zinc-900">Nouveau propriétaire</h3>
                                <button type="button" wire:click="toggleCreateOwner"
                                    class="text-xs text-zinc-500 hover:text-zinc-700">
                                    Annuler
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <flux:input wire:model="newOwnerFirstName" label="Prénom" placeholder="Jean" />
                                <flux:input wire:model="newOwnerLastName" label="Nom" placeholder="Dupont" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:input wire:model="newOwnerEmail" type="email" label="Email" placeholder="Optionnel" />
                                <flux:input wire:model="newOwnerPhone" label="Téléphone" placeholder="Optionnel" />
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <flux:button variant="ghost" wire:click="$set('modalOpen', false)">{{ __('Annuler') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Enregistrer') }}</flux:button>
        </div>
    </form>
</flux:modal>