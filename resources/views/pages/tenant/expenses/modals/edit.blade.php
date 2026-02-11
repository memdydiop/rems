<?php

use App\Models\Expense;
use App\Models\Property;
use App\Models\Vendor;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public Expense $expense;

    #[Validate('required|string|max:255')]
    public $description = '';

    #[Validate('required|numeric|min:0')]
    public $amount = '';

    #[Validate('required|date')]
    public $date = '';

    #[Validate('required|string')]
    public $category = 'maintenance';

    #[Validate('nullable|exists:properties,id')]
    public $property_id = '';

    #[Validate('nullable|exists:vendors,id')]
    public $vendor_id = '';

    public $modalOpen = false;

    #[On('edit-expense')]
    public function open($expense)
    {
        $this->expense = Expense::findOrFail($expense);
        $this->description = $this->expense->description;
        $this->amount = $this->expense->amount;
        $this->date = $this->expense->date->format('Y-m-d');
        $this->category = $this->expense->category;
        $this->property_id = $this->expense->property_id;
        $this->vendor_id = $this->expense->vendor_id;

        $this->modalOpen = true;
    }

    public function update()
    {
        $this->validate();

        $this->expense->update([
            'description' => $this->description,
            'amount' => $this->amount,
            'date' => $this->date,
            'category' => $this->category,
            'property_id' => $this->property_id ?: null,
            'vendor_id' => $this->vendor_id ?: null,
        ]);

        $this->modalOpen = false;
        $this->dispatch('expense-created'); // Refresh list
        flash()->success('Dépense mise à jour avec succès.');
    }

    public function with()
    {
        return [
            'properties' => Property::all(),
            'vendors' => Vendor::all(),
        ];
    }
};
?>

<flux:modal wire:model="modalOpen" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Modifier la dépense</flux:heading>
            <flux:subheading>Mettez à jour les détails de la dépense.</flux:subheading>
        </div>

        <form wire:submit="update" class="space-y-6">
            <flux:input wire:model="description" label="Description" placeholder="ex: Réparation fuite d'eau" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="amount" label="Montant" type="number" step="0.01" icon="currency-dollar" />
                <flux:input wire:model="date" label="Date" type="date" />
            </div>

            <flux:select wire:model="category" label="Catégorie" placeholder="Choisir une catégorie...">
                <flux:select.option value="maintenance">Maintenance</flux:select.option>
                <flux:select.option value="utilities">Utilitaires</flux:select.option>
                <flux:select.option value="insurance">Assurance</flux:select.option>
                <flux:select.option value="tax">Taxes</flux:select.option>
                <flux:select.option value="other">Autre</flux:select.option>
            </flux:select>

            <flux:select wire:model="property_id" label="Propriété (Optionnel)"
                placeholder="Rechercher une propriété...">
                <flux:select.option value="">Aucune</flux:select.option>
                @foreach($properties as $property)
                    <flux:select.option value="{{ $property->id }}">{{ $property->title }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="vendor_id" label="Fournisseur (Optionnel)"
                placeholder="Rechercher un fournisseur...">
                <flux:select.option value="">Aucun</flux:select.option>
                @foreach($vendors as $vendor)
                    <flux:select.option value="{{ $vendor->id }}">{{ $vendor->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer les modifications</flux:button>
            </div>
        </form>
    </div>
</flux:modal>