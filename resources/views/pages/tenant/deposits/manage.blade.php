<?php

use App\Enums\DepositStatus;
use App\Enums\DepositType;
use App\Models\Deposit;
use App\Models\Lease;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Flux\Flux;

new class extends Component {
    public ?Lease $lease = null;
    public ?Deposit $deposit = null;

    // Create form
    #[Validate('required|string')]
    public $type = 'security';

    #[Validate('required|numeric|min:0')]
    public $amount = '';

    #[Validate('nullable|date')]
    public $paid_at = '';

    #[Validate('nullable|string')]
    public $notes = '';

    // Return form
    public $return_amount = '';
    public $return_deductions = '';
    public $return_notes = '';

    public $mode = 'create'; // create, return

    #[On('manage-deposit')]
    public function openModal($lease_id, $mode = 'create', $deposit_id = null)
    {
        $this->lease = Lease::with('deposits')->findOrFail($lease_id);
        $this->mode = $mode;

        if ($mode === 'return' && $deposit_id) {
            $this->deposit = Deposit::findOrFail($deposit_id);
            $this->return_amount = $this->deposit->remaining_amount;
            $this->return_deductions = '';
            $this->return_notes = '';
        } else {
            $this->reset(['type', 'amount', 'paid_at', 'notes']);
            $this->paid_at = now()->format('Y-m-d');
        }

        Flux::modal('manage-deposit')->show();
    }

    public function createDeposit()
    {
        $this->validate([
            'type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $this->lease->deposits()->create([
            'type' => $this->type,
            'amount' => $this->amount,
            'paid_at' => $this->paid_at ?: now(),
            'status' => $this->paid_at ? DepositStatus::Held : DepositStatus::Pending,
            'notes' => $this->notes ?: null,
        ]);

        Flux::toast('Caution enregistrée avec succès.');
        Flux::modal('manage-deposit')->close();
        $this->dispatch('deposit-updated');
    }

    public function processReturn()
    {
        $this->validate([
            'return_amount' => 'required|numeric|min:0|max:' . $this->deposit->remaining_amount,
        ], [
            'return_amount.max' => 'Le montant ne peut pas dépasser ' . number_format($this->deposit->remaining_amount, 0, ',', ' ') . ' FCFA.',
        ]);

        $newReturnedTotal = (float) $this->deposit->returned_amount + (float) $this->return_amount;
        $isFullReturn = $newReturnedTotal >= (float) $this->deposit->amount;

        $this->deposit->update([
            'returned_amount' => $newReturnedTotal,
            'returned_at' => now(),
            'status' => $isFullReturn ? DepositStatus::Returned : DepositStatus::PartialReturn,
            'deductions' => $this->return_deductions ?: $this->deposit->deductions,
            'notes' => $this->return_notes ? ($this->deposit->notes . "\n" . $this->return_notes) : $this->deposit->notes,
        ]);

        Flux::toast('Remboursement enregistré avec succès.');
        Flux::modal('manage-deposit')->close();
        $this->dispatch('deposit-updated');
    }
};
?>

<flux:modal name="manage-deposit" class="min-w-100">
    @if($mode === 'create')
        <form wire:submit="createDeposit" class="space-y-6">
            <div>
                <h2 class="text-lg font-medium text-gray-900">Enregistrer une caution</h2>
                <p class="text-sm text-gray-500">Enregistrer une caution ou avance pour le bail.</p>
            </div>

            <flux:select wire:model="type" label="Type">
                @foreach(DepositType::cases() as $depositType)
                    <flux:select.option value="{{ $depositType->value }}">{{ $depositType->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="amount" type="number" step="1" label="Montant (FCFA)" placeholder="0" />
                <flux:input wire:model="paid_at" type="date" label="Date de versement" />
            </div>

            <flux:textarea wire:model="notes" label="Notes (Optionnel)" rows="2" placeholder="Détails supplémentaires..." />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Annuler</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    @elseif($mode === 'return' && $deposit)
        <form wire:submit="processReturn" class="space-y-6">
            <div>
                <h2 class="text-lg font-medium text-gray-900">Rembourser la caution</h2>
                <p class="text-sm text-gray-500">
                    {{ $deposit->type->label() }} — {{ number_format($deposit->amount, 0, ',', ' ') }} FCFA
                    @if($deposit->returned_amount > 0)
                        (déjà remboursé : {{ number_format($deposit->returned_amount, 0, ',', ' ') }} FCFA)
                    @endif
                </p>
            </div>

            <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                <div class="text-sm text-blue-700 font-medium">Montant restant à rembourser</div>
                <div class="text-2xl font-bold text-blue-900">
                    {{ number_format($deposit->remaining_amount, 0, ',', ' ') }} FCFA
                </div>
            </div>

            <flux:input wire:model="return_amount" type="number" step="1" label="Montant à rembourser (FCFA)"
                placeholder="0" />

            <flux:textarea wire:model="return_deductions" label="Retenues / Déductions (Optionnel)" rows="2"
                placeholder="Ex: Réparation carrelage cuisine..." />

            <flux:textarea wire:model="return_notes" label="Notes (Optionnel)" rows="2" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Annuler</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Confirmer le remboursement</flux:button>
            </div>
        </form>
    @endif
</flux:modal>