<?php

use App\Models\Client;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;

new class extends Component {
    public Client $client;

    #[Validate('required|string|max:255')]
    public $first_name = '';

    #[Validate('required|string|max:255')]
    public $last_name = '';

    #[Validate('nullable|email|max:255')] // Unique check in rules()
    public $email = '';

    #[Validate('required|string|max:20')]
    public $phone = '';

    #[Validate('required|in:active,lead,past')]
    public $status = 'active';

    public $modalOpen = false;

    public function rules()
    {
        return [
            'email' => 'nullable|email|max:255|unique:clients,email,' . $this->client->id,
        ];
    }

    #[On('edit-client')]
    public function open($client)
    {
        $this->client = Client::findOrFail($client);
        $this->first_name = $this->client->first_name;
        $this->last_name = $this->client->last_name;
        $this->email = $this->client->email;
        $this->phone = $this->client->phone;
        $this->status = $this->client->status->value;

        $this->modalOpen = true;
    }

    public function update()
    {
        $this->validate();

        $this->client->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
        ]);

        $this->modalOpen = false;
        $this->dispatch('client-updated');
        $this->dispatch('refresh-clients');
        flash()->success('Client mis à jour avec succès.');
    }
};
?>

<flux:modal wire:model="modalOpen" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Modifier le Client</flux:heading>
            <flux:subheading>Mettez à jour les informations du client.</flux:subheading>
        </div>

        <form wire:submit="update" class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="first_name" label="Prénom" />
                <flux:input wire:model="last_name" label="Nom" />
            </div>

            <flux:input wire:model="email" label="Email (Optionnel)" type="email" />
            <flux:input wire:model="phone" label="Téléphone" />

            <flux:select wire:model="status" label="Statut">
                <flux:select.option value="active">Actif</flux:select.option>
                <flux:select.option value="lead">Prospect</flux:select.option>
                <flux:select.option value="past">Ancien</flux:select.option>
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer les modifications</flux:button>
            </div>
        </form>
    </div>
</flux:modal>