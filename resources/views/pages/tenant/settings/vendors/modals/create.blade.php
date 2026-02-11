<?php

use App\Models\Vendor;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('nullable|email|max:255')]
    public $email = '';

    #[Validate('nullable|string|max:50')]
    public $phone = '';

    #[Validate('nullable|string|max:100')]
    public $service_type = '';

    #[Validate('nullable|string|max:500')]
    public $address = '';

    public $modalOpen = false;

    #[On('open-modal')]
    public function open($name)
    {
        if ($name === 'create-vendor') {
            $this->reset();
            $this->modalOpen = true;
        }
    }

    public function create()
    {
        $this->validate();

        Vendor::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'service_type' => $this->service_type,
            'address' => $this->address,
        ]);

        $this->modalOpen = false;
        $this->dispatch('vendor-created');
        flash()->success('Prestataire créé avec succès.');
    }
};
?>

<flux:modal wire:model="modalOpen">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Ajouter un prestataire</flux:heading>
            <flux:subheading>Ajoutez un nouveau fournisseur ou entrepreneur à vos contacts.</flux:subheading>
        </div>

        <form wire:submit="create" class="space-y-6">
            <flux:input wire:model="name" label="Société / Nom" placeholder="ex: Plomberie Dupuis" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="service_type" label="Type de service" placeholder="ex: Plomberie" />
                <flux:input wire:model="phone" label="Téléphone" placeholder="01 23 45 67 89" />
            </div>

            <flux:input wire:model="email" label="Email" type="email" />
            <flux:textarea wire:model="address" label="Adresse" placeholder="Adresse complète..." />

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Créer le prestataire</flux:button>
            </div>
        </form>
    </div>
</flux:modal>