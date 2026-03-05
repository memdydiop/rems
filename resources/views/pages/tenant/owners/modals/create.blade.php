<?php

use App\Models\Owner;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;

new class extends Component {
    public ?Owner $owner = null;
    public bool $modalOpen = false;

    #[Validate('required|string|max:255')]
    public $first_name = '';

    #[Validate('required|string|max:255')]
    public $last_name = '';

    #[Validate('nullable|email|max:255')]
    public $email = null;

    #[Validate('required|string|max:50')]
    public $phone = '';

    #[Validate('nullable|string')]
    public $address = '';

    #[Validate('nullable|string')]
    public $account_details = '';

    #[On('open-modal')]
    public function open($name = null, $owner = null)
    {
        if ($name === 'create-owner') {
            $this->reset();
            $this->modalOpen = true;
        }

        if ($name === 'edit-owner' && $owner) {
            $this->owner = Owner::find($owner);
            if ($this->owner) {
                $this->first_name = $this->owner->first_name;
                $this->last_name = $this->owner->last_name;
                $this->email = $this->owner->email;
                $this->phone = $this->owner->phone;
                $this->address = $this->owner->address;
                $this->account_details = $this->owner->account_details;
                $this->modalOpen = true;
            }
        }
    }

    public function save()
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:owners,email,' . ($this->owner?->id ?? 'NULL'),
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string',
            'account_details' => 'nullable|string',
        ];

        $this->validate($rules);

        if ($this->owner) {
            $this->owner->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email ?: null,
                'phone' => $this->phone,
                'address' => $this->address,
                'account_details' => $this->account_details,
            ]);
            $this->js("Flux.toast('Propriétaire mis à jour.')");
        } else {
            Owner::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email ?: null,
                'phone' => $this->phone,
                'address' => $this->address,
                'account_details' => $this->account_details,
            ]);
            $this->js("Flux.toast('Propriétaire ajouté.')");
        }

        $this->modalOpen = false;
        $this->dispatch('property-updated'); // Reload parent if needed, though here it's index
        return redirect()->route('tenant.owners.index');
    }
};
?>

<flux:modal wire:model="modalOpen" class="md:w-96">
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-bold text-zinc-900">
                {{ $owner ? 'Modifier le Propriétaire' : 'Nouveau Propriétaire' }}
            </h2>
            <p class="text-sm text-zinc-500">Remplissez les informations ci-dessous.</p>
        </div>

        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="first_name" label="Prénom" placeholder="Jean" />
                <flux:input wire:model="last_name" label="Nom" placeholder="Dupont" />
            </div>

            <flux:input wire:model="email" label="Email (Optionnel)" type="email" icon="envelope" />
            <flux:input wire:model="phone" label="Téléphone" type="tel" icon="phone" />

            <flux:textarea wire:model="address" label="Adresse (Optionnel)" rows="2" />
            <flux:textarea wire:model="account_details" label="Coordonnées Bancaires (Optionnel)" placeholder="IBAN..."
                rows="2" />
        </div>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Annuler</flux:button>
            <flux:button variant="primary" wire:click="save">Enregistrer</flux:button>
        </div>
    </div>
</flux:modal>