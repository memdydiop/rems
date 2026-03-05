<?php

use App\Models\Lease;
use App\Models\Unit;
use App\Models\Renter;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public ?Lease $lease = null;

    #[On('open-modal')]
    public function open($name, $lease_id = null, $lease = null)
    {
        if ($name !== 'edit-lease') {
            return;
        }

        $id = $lease_id ?? $lease;
        if (!$id)
            return;

        $this->lease = Lease::with('unit.property', 'renter')->findOrFail($id);

        $this->unit_id = $this->lease->unit_id;
        $this->renter_id = $this->lease->renter_id;
        $this->start_date = $this->lease->start_date->format('Y-m-d');
        $this->end_date = $this->lease->end_date?->format('Y-m-d') ?? '';
        $this->rent_amount = $this->lease->rent_amount;
        $this->charges_amount = $this->lease->charges_amount;
        $this->deposit_amount = $this->lease->deposit_amount;
        $this->advance_amount = $this->lease->advance_amount ?? 0;
        $this->lease_type = $this->lease->lease_type ?? '';
        $this->notes = $this->lease->notes ?? '';

        $this->js("Flux.modal('edit-lease').show()");
    }

    // Form fields
    #[Validate('required|exists:units,id')]
    public $unit_id = '';

    #[Validate('required|exists:renters,id')]
    public $renter_id = '';

    #[Validate('required|date')]
    public $start_date = '';

    #[Validate('nullable|date|after:start_date')]
    public $end_date = '';

    #[Validate('required|numeric|min:0')]
    public $rent_amount = '';

    public int $deposit_multiplier = 1;

    #[Validate('required|numeric|min:0')]
    public $deposit_amount = '';

    #[Validate('nullable|numeric|min:0')]
    public $advance_amount = 0;

    #[Validate('nullable|string')]
    public $lease_type = '';

    #[Validate('required|numeric|min:0')]
    public $charges_amount = 0;

    #[Validate('nullable|string')]
    public $notes = '';

    public function setDepositMultiplier()
    {
        if ($this->rent_amount) {
            $this->deposit_multiplier = $this->deposit_multiplier >= 3 ? 1 : $this->deposit_multiplier + 1;
            $this->deposit_amount = (float) $this->rent_amount * $this->deposit_multiplier;
        }
    }

    public function with()
    {
        return [];
    }

    public function update()
    {
        $this->validate();

        if (!$this->lease)
            return;

        $this->lease->update([
            'unit_id' => $this->unit_id,
            'renter_id' => $this->renter_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date ?: null,
            'rent_amount' => $this->rent_amount,
            'charges_amount' => $this->charges_amount,
            'deposit_amount' => $this->deposit_amount,
            'advance_amount' => $this->advance_amount,
            'lease_type' => $this->lease_type ?: null,
            'notes' => $this->notes ?: null,
        ]);

        $this->dispatch('lease-updated');
        $this->js("Flux.toast('Bail modifié avec succès.')");
        $this->js("Flux.modal('edit-lease').close()");
    }
};
?>

<flux:modal name="edit-lease" class="min-w-125">
    <form wire:submit="update" class="space-y-6">
        <div class="mb-6">
            <flux:heading size="lg">Modifier le Bail</flux:heading>
            <flux:subheading>
                @if($lease)
                    {{ $lease->unit->property->name }} — {{ $lease->unit->name }}
                @else
                    Mettre à jour les détails du contrat de location.
                @endif
            </flux:subheading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
            <div>
                <flux:label>Locataire</flux:label>
                <div
                    class="mt-1 flex items-center gap-3 px-3 py-2 bg-zinc-50 border border-zinc-200 rounded-lg text-zinc-900 shadow-sm">
                    <flux:avatar size="xs" :name="$lease?->renter->full_name" />
                    <span class="font-medium text-sm">{{ $lease?->renter->full_name }}</span>
                </div>
            </div>

            <flux:select wire:model="lease_type" label="Type de bail" placeholder="Sélectionner...">
                <flux:select.option value="vide">Location vide</flux:select.option>
                <flux:select.option value="meuble">Location meublée</flux:select.option>
                <flux:select.option value="commercial">Bail commercial</flux:select.option>
                <flux:select.option value="professionnel">Bail professionnel</flux:select.option>
                <flux:select.option value="saisonnier">Location saisonnière</flux:select.option>
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:input wire:model="start_date" type="date" label="Date de début" />
            <flux:input wire:model="end_date" type="date" label="Date de fin (Optionnel)" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <flux:input wire:model="rent_amount" type="number" step="0.01" label="Loyer de base"
                prefix="{{ config('app.currency', 'XOF') }}" />

            <flux:input wire:model="charges_amount" type="number" step="0.01" label="Charges"
                prefix="{{ config('app.currency', 'XOF') }}" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <div class="space-y-2">
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <flux:input wire:model="deposit_amount" type="number" step="0.01"
                            label="Dépôt de garantie{{ $rent_amount ? ' (' . $deposit_multiplier . ' mois)' : '' }}"
                            prefix="{{ config('app.currency', 'XOF') }}" />
                    </div>

                    <button type="button" wire:click="setDepositMultiplier"
                        title="Calculer ({{ $deposit_multiplier }} mois)"
                        class="flex-none p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors h-10 w-10 flex items-center justify-center">
                        <flux:icon.plus class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <flux:input wire:model="advance_amount" type="number" step="0.01" label="Avance sur loyer"
                    prefix="{{ config('app.currency', 'XOF') }}" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <flux:textarea wire:model="notes" label="Notes" rows="2" placeholder="Informations complémentaires..." />
        </div>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Enregistrer les modifications</flux:button>
        </div>
    </form>
</flux:modal>