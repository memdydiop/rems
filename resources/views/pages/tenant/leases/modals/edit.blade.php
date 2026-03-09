<?php

use App\Models\Lease;
use App\Models\Unit;
use App\Models\Client;
use App\Enums\LeaseType;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;
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

        $this->lease = Lease::with('unit.property', 'client')->findOrFail($id);

        $this->unit_id = $this->lease->unit_id;
        $this->client_id = $this->lease->client_id;
        $this->start_date = $this->lease->start_date->format('Y-m-d');
        $this->end_date = $this->lease->end_date?->format('Y-m-d') ?? '';
        $this->rent_amount = $this->lease->rent_amount;
        $this->charges_amount = $this->lease->charges_amount;
        $this->deposit_amount = $this->lease->deposit_amount;
        $this->advance_amount = $this->lease->advance_amount ?? 0;
        $this->lease_type = $this->lease->lease_type?->value ?? '';
        $this->notes = $this->lease->notes ?? '';

        $this->js("Flux.modal('edit-lease').show()");
    }

    public function updatedStartDate($value)
    {
        if ($value) {
            $date = \Illuminate\Support\Carbon::parse($value);
            if ($date->day !== 1) {
                $this->start_date = $date->startOfMonth()->format('Y-m-d');
                $this->js("Flux.toast({ variant: 'warning', text: 'La date de début a été ajustée au 1er du mois.' })");
            }
        }
    }

    public function updatedEndDate($value)
    {
        if ($value) {
            $date = \Illuminate\Support\Carbon::parse($value);
            if ($date->day !== $date->daysInMonth) {
                $this->end_date = $date->endOfMonth()->format('Y-m-d');
                $this->js("Flux.toast({ variant: 'warning', text: 'La date de fin a été ajustée au dernier jour du mois.' })");
            }
        }
    }

    // Form fields
    #[Validate('required|exists:units,id')]
    public $unit_id = '';

    #[Validate('required|exists:clients,id')]
    public $client_id = '';

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

    #[Validate(['documents.*' => 'nullable|file|max:10240'])] // 10MB max per file
    public $documents = [];

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

        $oldRent = (float) $this->lease->rent_amount;
        $newRent = (float) $this->rent_amount;

        if ($oldRent !== $newRent) {
            $this->lease->adjustments()->create([
                'old_amount' => $oldRent,
                'new_amount' => $newRent,
                'effective_date' => now(),
                'reason' => 'Ajustement manuel via modification du bail',
            ]);
        }

        $this->lease->update([
            'unit_id' => $this->unit_id,
            'client_id' => $this->client_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date ?: null,
            'rent_amount' => $this->rent_amount,
            'charges_amount' => $this->charges_amount,
            'deposit_amount' => $this->deposit_amount,
            'advance_amount' => $this->advance_amount,
            'lease_type' => $this->lease_type ?: null,
            'notes' => $this->notes ?: null,
        ]);

        if (!empty($this->documents)) {
            foreach ($this->documents as $document) {
                $this->lease->addMedia($document)->toMediaCollection('documents');
            }
        }

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
                <flux:label>Client</flux:label>
                <div
                    class="mt-1 flex items-center gap-3 px-3 py-2 bg-zinc-50 border border-zinc-200 rounded-lg text-zinc-900 shadow-sm">
                    <flux:avatar size="xs" :name="$lease?->client->full_name" />
                    <span class="font-medium text-sm">{{ $lease?->client->full_name }}</span>
                </div>
            </div>

            <flux:select wire:model="lease_type" label="Type de bail" placeholder="Sélectionner...">
                @foreach(\App\Enums\LeaseType::cases() as $type)
                    <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                @endforeach
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
            <div class="space-y-2">
                <flux:input wire:model="documents" type="file" multiple label="Ajouter des documents (Optionnel)" />
                <div wire:loading wire:target="documents" class="text-xs text-indigo-600">Téléchargement en cours...
                </div>
            </div>

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