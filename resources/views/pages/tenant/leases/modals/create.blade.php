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
    #[On('open-modal')]
    public function open($name, $unit_id = null)
    {
        if ($name !== 'create-lease') {
            return;
        }

        $this->reset();

        if ($unit_id) {
            $this->unit_id = $unit_id;
            $this->is_fixed_unit = true;
            $this->updatedUnitId($this->unit_id);
        }

        $this->js("Flux.modal('create-lease').show()");
    }

    public int $deposit_multiplier = 1;
    public bool $is_fixed_unit = false;
    // Selection
    #[Validate('required|exists:units,id')]
    public $unit_id = '';

    #[Validate('required|exists:clients,id')]
    public $client_id = '';

    // Terms
    #[Validate('required|date')]
    public $start_date = '';

    #[Validate('nullable|date|after:start_date')]
    public $end_date = '';

    #[Validate('required|numeric|min:0')]
    public $rent_amount = '';

    #[Validate('required|numeric|min:0')]
    public $deposit_amount = '';

    #[Validate('nullable|numeric|min:0')]
    public $advance_amount = 0;

    #[Validate('nullable|string')]
    public $lease_type = '';

    #[Validate('required|numeric|min:0')]
    public $charges_amount = 0;

    #[Validate(['documents.*' => 'nullable|file|max:10240'])] // 10MB max per file
    public $documents = [];

    public function with()
    {
        return [
            'units' => Unit::whereDoesntHave('leases', function ($query) {
                $query->where('status', 'active');
            })->when($this->unit_id, fn($q) => $q->orWhere('id', $this->unit_id)) // Keep current unit if editing/pre-filled
                ->with('property')
                ->get(),
            'clients' => Client::all(),
        ];
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

    public function updatedUnitId($value)
    {
        if ($value) {
            $unit = Unit::find($value);
            if ($unit) {
                $this->rent_amount = $unit->rent_amount;
                $this->deposit_amount = $unit->rent_amount; // Default deposit to 1 month rent
            }
        }
    }

    public function setDepositMultiplier()
    {
        if ($this->rent_amount) {
            // Cycle the multiplier 1 -> 2 -> 3 -> 1
            $this->deposit_multiplier = $this->deposit_multiplier >= 3 ? 1 : $this->deposit_multiplier + 1;

            // Cast to float to ensure correct numeric multiplication
            $this->deposit_amount = (float) $this->rent_amount * $this->deposit_multiplier;
        }
    }

    #[On('client-created')]
    public function refreshClients()
    {
        // La méthode vide suffit pour déclencher un re-render et rafraîchir la liste via with()
    }

    public function store()
    {
        $this->validate();

        $unit = Unit::with('property')->find($this->unit_id);

        if ($unit->property->status === \App\Enums\PropertyStatus::Maintenance) {
            $this->addError('unit_id', 'Impossible de créer un bail : la propriété est en maintenance.');
            return;
        }

        if ($unit->status === \App\Enums\UnitStatus::Occupied || $unit->leases()->where('status', 'active')->exists()) {
            $this->addError('unit_id', 'Cette unité a déjà un bail actif. Veuillez sélectionner une autre unité.');
            return;
        }

        $lease = Lease::create([
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
            'status' => 'active',
        ]);

        // Auto-create deposit records based on initial amounts
        if ($this->deposit_amount > 0) {
            $lease->deposits()->create([
                'type' => \App\Enums\DepositType::Security,
                'amount' => $this->deposit_amount,
                'paid_at' => now(),
                'status' => \App\Enums\DepositStatus::Held,
                'notes' => 'Caution initiale du bail',
            ]);
        }

        if ($this->advance_amount > 0) {
            $lease->deposits()->create([
                'type' => \App\Enums\DepositType::Advance,
                'amount' => $this->advance_amount,
                'paid_at' => now(),
                'status' => \App\Enums\DepositStatus::Held,
                'notes' => 'Avance initiale du bail',
            ]);
        }

        if (!empty($this->documents)) {
            foreach ($this->documents as $document) {
                $lease->addMedia($document)->toMediaCollection('documents');
            }
        }

        $this->dispatch('lease-created');
        $this->js("Flux.toast('Bail créé avec succès.')");
        $this->js("Flux.modal('create-lease').close()");
    }
};
?>

<flux:modal name="create-lease" class="min-w-125">
    <form wire:submit="store" class="space-y-6">
        <div class="mb-6">
            <flux:heading size="lg">Nouveau Bail</flux:heading>
            @if($is_fixed_unit)
                @php
                    $fixedUnit = $units->firstWhere('id', $unit_id);
                @endphp
                <flux:subheading>
                    Pour l'unité : <strong
                        class="text-zinc-900">{{ $fixedUnit ? $fixedUnit->property->name . ' - ' . $fixedUnit->name : '' }}</strong>
                </flux:subheading>
            @else
                <flux:subheading>Créer un nouveau contrat de location.</flux:subheading>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if(!$is_fixed_unit)
                <flux:select wire:model.live="unit_id" label="Unité" placeholder="Sélectionner une unité...">
                    @foreach ($units as $unit)
                        <flux:select.option value="{{ $unit->id }}">
                            {{ $unit->property->name }} - {{ $unit->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <div class="{{ $is_fixed_unit ? 'md:col-span-1' : '' }} space-y-2">
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <flux:select wire:model="client_id" label="Client" placeholder="Sélectionner un client...">
                            @foreach ($clients as $client)
                                <flux:select.option value="{{ $client->id }}">
                                    {{ $client->first_name }} {{ $client->last_name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <flux:button variant="ghost" wire:click="$dispatch('create-client')" icon="plus"
                        tooltip="Nouveau client" />
                </div>
            </div>

            <div class="{{ $is_fixed_unit ? 'md:col-span-1' : '' }}">
                <flux:select wire:model="lease_type" label="Type de bail" placeholder="Sélectionner...">
                    @foreach(\App\Enums\LeaseType::cases() as $type)
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
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
                        class="flex-none p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors size-9 flex items-center justify-center">
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

            <div class="md:col-span-2 space-y-2">
                <flux:input wire:model="documents" type="file" multiple
                    label="Documents annexes (Contrat signé, pièces d'identité...)" />
                <div wire:loading wire:target="documents" class="text-xs text-indigo-600">Téléchargement en cours...
                </div>
            </div>

            <div class="md:col-span-2">
                <flux:textarea wire:model="notes" label="Notes" rows="2"
                    placeholder="Informations complémentaires..." />
            </div>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Créer le Bail</flux:button>
        </div>
    </form>
</flux:modal>