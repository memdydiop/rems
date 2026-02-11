<?php

use App\Models\Renter;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;

new class extends Component {
    public Renter $renter;

    #[Validate('required|string|max:255')]
    public $first_name = '';

    #[Validate('required|string|max:255')]
    public $last_name = '';

    #[Validate('required|email|max:255')] // Unique check needs ignore current id, tricky in strict attributes without rules() method
    public $email = '';

    #[Validate('nullable|string|max:20')]
    public $phone = '';

    #[Validate('required|in:active,lead,past')]
    public $status = 'active';

    public $modalOpen = false;

    public function rules()
    {
        return [
            'email' => 'required|email|max:255|unique:renters,email,' . $this->renter->id,
        ];
    }

    #[On('edit-renter')]
    public function open($renter)
    {
        $this->renter = Renter::findOrFail($renter);
        $this->first_name = $this->renter->first_name;
        $this->last_name = $this->renter->last_name;
        $this->email = $this->renter->email;
        $this->phone = $this->renter->phone;
        $this->status = $this->renter->status->value;

        $this->modalOpen = true;
    }

    public function update()
    {
        $this->validate();

        $this->renter->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
        ]);

        $this->modalOpen = false;
        $this->dispatch('renter-updated'); // Optional, mainly for table refresh
        $this->dispatch('refresh-renters'); // Standardize on one
        flash()->success('Locataire mis à jour avec succès.');
    }
};
?>

<flux:modal wire:model="modalOpen" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Modifier le Locataire</flux:heading>
            <flux:subheading>Mettez à jour les informations du locataire.</flux:subheading>
        </div>

        <form wire:submit="update" class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="first_name" label="Prénom" />
                <flux:input wire:model="last_name" label="Nom" />
            </div>

            <flux:input wire:model="email" label="Email" type="email" />
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