<?php

use App\Models\Renter;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;

new class extends Component {
    #[On('create-renter')]
    public function open()
    {
        $this->reset();
        $this->modalOpen = true;
    }

    #[Validate('required|string|max:255')]
    public $first_name = '';

    #[Validate('required|string|max:255')]
    public $last_name = '';

    #[Validate('required|email|max:255|unique:renters,email')]
    public $email = '';

    #[Validate('nullable|string|max:20')]
    public $phone = '';

    #[Validate('required|in:active,lead,past')]
    public $status = 'active';

    public $modalOpen = false;

    public function create()
    {
        $this->validate();

        Renter::create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
        ]);

        $this->reset();
        $this->modalOpen = false;
        $this->dispatch('renter-created');
        flash()->success('Locataire ajouté avec succès.');
    }
};
?>

<flux:modal wire:model="modalOpen" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Nouveau Locataire</flux:heading>
            <flux:subheading>Ajoutez un nouveau locataire ou prospect.</flux:subheading>
        </div>

        <form wire:submit="create" class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="first_name" label="Prénom" placeholder="Jean" />
                <flux:input wire:model="last_name" label="Nom" placeholder="Dupont" />
            </div>

            <flux:input wire:model="email" label="Email" type="email" placeholder="jean.dupont@example.com" />
            <flux:input wire:model="phone" label="Téléphone" placeholder="+221 77 123 45 67" />

            <flux:select wire:model="status" label="Statut" placeholder="Sélectionner un statut...">
                <flux:select.option value="active">Actif</flux:select.option>
                <flux:select.option value="lead">Prospect</flux:select.option>
                <flux:select.option value="past">Ancien</flux:select.option>
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Ajouter</flux:button>
            </div>
        </form>
    </div>
</flux:modal>