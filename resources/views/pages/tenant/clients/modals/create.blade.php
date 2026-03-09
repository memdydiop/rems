<?php

use App\Models\Client;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;

new class extends Component {
    #[On('create-client')]
    public function open()
    {
        $this->reset();
        $this->modalOpen = true;
    }

    #[Validate('required|string|max:255')]
    public $first_name = '';

    #[Validate('required|string|max:255')]
    public $last_name = '';

    #[Validate('nullable|email|max:255|unique:clients,email')]
    public $email = null;

    #[Validate('required|string|max:20')]
    public $phone = '';

    #[Validate('required|in:active,lead,past')]
    public $status = 'active';

    public $modalOpen = false;

    public function create()
    {
        $this->validate();

        Client::create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email ?: null,
            'phone' => $this->phone,
            'status' => $this->status,
        ]);

        $this->reset();
        $this->modalOpen = false;
        $this->dispatch('client-created');
        flash()->success('Client ajouté avec succès.');
    }
};
?>

<flux:modal wire:model="modalOpen" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Nouveau Client</flux:heading>
            <flux:subheading>Ajoutez un nouveau client ou prospect.</flux:subheading>
        </div>

        <form wire:submit="create" class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="first_name" label="Prénom" placeholder="Jean" />
                <flux:input wire:model="last_name" label="Nom" placeholder="Dupont" />
            </div>

            <flux:input wire:model="email" label="Email (Optionnel)" type="email"
                placeholder="jean.dupont@example.com" />
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