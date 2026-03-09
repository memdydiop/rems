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

    #[Validate('nullable|exists:units,id')]
    public $unit_id = '';

    #[Validate('nullable|exists:vendors,id')]
    public $vendor_id = '';

    #[Validate('required|string')]
    public $status = 'paid';

    public $is_recurring = false;

    #[Validate('nullable|string')]
    public $frequency = '';

    #[Validate('nullable|date')]
    public $next_due_date = '';

    #[Validate('nullable|file|max:10240')]
    public $receipt = null;

    public $currentReceiptUrl = null;

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
        $this->unit_id = $this->expense->unit_id;
        $this->vendor_id = $this->expense->vendor_id;
        $this->status = $this->expense->status;
        $this->is_recurring = $this->expense->is_recurring;
        $this->frequency = $this->expense->frequency ?? '';
        $this->next_due_date = $this->expense->next_due_date ? $this->expense->next_due_date->format('Y-m-d') : '';

        $this->currentReceiptUrl = $this->expense->getFirstMediaUrl('receipts');

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
            'unit_id' => $this->unit_id ?: null,
            'vendor_id' => $this->vendor_id ?: null,
            'status' => $this->status,
            'is_recurring' => $this->is_recurring,
            'frequency' => $this->is_recurring ? $this->frequency : null,
            'next_due_date' => $this->is_recurring ? $this->next_due_date : null,
        ]);

        if ($this->receipt) {
            $this->expense->clearMediaCollection('receipts');
            $this->expense->addMedia($this->receipt)->toMediaCollection('receipts');
        }

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

            <flux:select wire:model.live="property_id" label="Propriété (Optionnel)"
                placeholder="Rechercher une propriété...">
                <flux:select.option value="">Aucune</flux:select.option>
                @foreach($properties as $property)
                    <flux:select.option value="{{ $property->id }}">{{ $property->title }}</flux:select.option>
                @endforeach
            </flux:select>

            @if($property_id)
                <flux:select wire:model="unit_id" label="Unité (Optionnel)" placeholder="Rechercher une unité...">
                    <flux:select.option value="">Aucune</flux:select.option>
                    @foreach($properties->find($property_id)?->units ?? [] as $unit)
                        <flux:select.option value="{{ $unit->id }}">{{ $unit->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:select wire:model="vendor_id" label="Fournisseur (Optionnel)"
                placeholder="Rechercher un fournisseur...">
                <flux:select.option value="">Aucun</flux:select.option>
                @foreach($vendors as $vendor)
                    <flux:select.option value="{{ $vendor->id }}">{{ $vendor->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4 flex-wrap">
                <flux:select wire:model="status" label="Statut">
                    <flux:select.option value="paid">Payé</flux:select.option>
                    <flux:select.option value="pending">En attente</flux:select.option>
                </flux:select>

                <div class="space-y-2">
                    <flux:input wire:model="receipt" type="file" label="Nouveau Justificatif" />
                    @if($currentReceiptUrl)
                        <div class="text-xs">
                            <a href="{{ $currentReceiptUrl }}" target="_blank"
                                class="text-indigo-600 hover:underline flex items-center gap-1">
                                <flux:icon.document-text class="size-3" /> Voir le justificatif actuel
                            </a>
                        </div>
                    @endif
                </div>
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
                <flux:button type="submit" variant="primary">Enregistrer les modifications</flux:button>
            </div>
        </form>
    </div>
</flux:modal>