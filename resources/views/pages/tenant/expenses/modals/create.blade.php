<?php

use App\Models\Expense;
use App\Models\Property;
use App\Models\Vendor;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;
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

    #[Validate('nullable|exists:units,id')]
    public $unit_id = '';

    #[Validate('nullable|exists:vendors,id')]
    public $vendor_id = '';

    #[Validate('required|string')]
    public $status = 'paid'; // paid, pending

    public $is_recurring = false;

    #[Validate('nullable|string')]
    public $frequency = ''; // monthly, quarterly, yearly

    #[Validate('nullable|date')]
    public $next_due_date = '';

    #[Validate('nullable|file|max:10240')]
    public $receipt = null;

    public $modalOpen = false;

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
    }

    #[On('create-expense')]
    public function open($property_id = null, $unit_id = null)
    {
        $this->reset(['description', 'amount', 'category', 'status', 'is_recurring', 'frequency', 'next_due_date', 'receipt']);
        $this->date = now()->format('Y-m-d');
        $this->property_id = $property_id;
        $this->unit_id = $unit_id;
        $this->modalOpen = true;
    }

    public function create()
    {
        $this->validate();

        $expense = Expense::create([
            'description' => $this->description,
            'amount' => $this->amount,
            'date' => $this->date,
            'category' => $this->category,
            'property_id' => $this->property_id ?: null,
            'unit_id' => $this->unit_id ?: null,
            'vendor_id' => $this->vendor_id ?: null,
            'status' => $this->status,
            'is_recurring' => $this->is_recurring,
            'frequency' => $this->is_recurring ? $this->frequency : null,
            'next_due_date' => $this->is_recurring ? $this->next_due_date : null,
        ]);

        if ($this->receipt) {
            $expense->addMedia($this->receipt)->toMediaCollection('receipts');
        }

        $this->modalOpen = false;
        $this->dispatch('expense-created');
        flash()->success('Dépense enregistrée avec succès.');
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
            <flux:heading size="lg">Nouvelle Dépense</flux:heading>
            <flux:subheading>Enregistrez une nouvelle dépense pour une propriété.</flux:subheading>
        </div>

        <form wire:submit="create" class="space-y-6">
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

            <flux:select wire:model="unit_id" label="Unité (Optionnel)" placeholder="Rechercher une unité...">
                <flux:select.option value="">Aucune</flux:select.option>
                @foreach($properties->find($property_id)?->units ?? [] as $unit)
                    <flux:select.option value="{{ $unit->id }}">{{ $unit->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="vendor_id" label="Fournisseur (Optionnel)"
                placeholder="Rechercher un fournisseur...">
                <flux:select.option value="">Aucun</flux:select.option>
                @foreach($vendors as $vendor)
                    <flux:select.option value="{{ $vendor->id }}">{{ $vendor->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="status" label="Statut">
                    <flux:select.option value="paid">Payé</flux:select.option>
                    <flux:select.option value="pending">En attente</flux:select.option>
                </flux:select>

                <flux:input wire:model="receipt" type="file" label="Justificatif (PDF/Image)" />
            </div>

            <div class="space-y-3 p-4 bg-zinc-50 rounded-xl border border-zinc-200">
                <flux:checkbox wire:model.live="is_recurring" label="Dépense récurrente" />

                @if($is_recurring)
                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <flux:select wire:model="frequency" label="Fréquence">
                            <flux:select.option value="monthly">Mensuel</flux:select.option>
                            <flux:select.option value="quarterly">Trimestriel</flux:select.option>
                            <flux:select.option value="yearly">Annuel</flux:select.option>
                        </flux:select>
                        <flux:input wire:model="next_due_date" type="date" label="Prochaine échéance" />
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </div>
</flux:modal>