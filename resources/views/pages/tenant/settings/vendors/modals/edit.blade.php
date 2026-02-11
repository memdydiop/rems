<?php

use App\Models\Vendor;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public Vendor $vendor;

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

    #[On('edit-vendor')]
    public function open($vendor)
    {
        $this->vendor = Vendor::findOrFail($vendor);
        $this->name = $this->vendor->name;
        $this->email = $this->vendor->email;
        $this->phone = $this->vendor->phone;
        $this->service_type = $this->vendor->service_type;
        $this->address = $this->vendor->address;

        $this->modalOpen = true;
    }

    public function update()
    {
        $this->validate();

        $this->vendor->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'service_type' => $this->service_type,
            'address' => $this->address,
        ]);

        $this->modalOpen = false;
        $this->dispatch('vendor-created'); // Refresh list
        flash()->success('Prestataire mis à jour avec succès.');
    }
};
?>

<flux:modal wire:model="modalOpen">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Modifier le prestataire</flux:heading>
        </div>

        <form wire:submit="update" class="space-y-6">
            <flux:input wire:model="name" label="Société / Nom" placeholder="ex: Plomberie Dupuis" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="service_type" label="Type de service" placeholder="ex: Plomberie" />
                <flux:input wire:model="phone" label="Téléphone" placeholder="01 23 45 67 89" />
            </div>

            <flux:input wire:model="email" label="Email" type="email" />
            <flux:textarea wire:model="address" label="Adresse" placeholder="Adresse complète..." />

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer les modifications</flux:button>
            </div>
        </form>
    </div>
</flux:modal>