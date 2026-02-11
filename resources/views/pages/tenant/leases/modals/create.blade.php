<?php

use App\Models\Lease;
use App\Models\Unit;
use App\Models\Renter;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[On('open-modal')]
    public function open($name)
    {
        $target = is_array($name) ? ($name['name'] ?? null) : $name;
        $params = is_array($name) ? $name : [];

        if ($target === 'create-lease') {
            $this->reset();

            if (isset($params['unit_id'])) {
                $this->unit_id = $params['unit_id'];
                $this->updatedUnitId($this->unit_id);
            }

            $this->js("Flux.modal('create-lease').show()");
        }
    }
    // Selection
    #[Validate('required|exists:units,id')]
    public $unit_id = '';

    #[Validate('required|exists:renters,id')]
    public $renter_id = '';

    // Terms
    #[Validate('required|date')]
    public $start_date = '';

    #[Validate('nullable|date|after:start_date')]
    public $end_date = '';

    #[Validate('required|numeric|min:0')]
    public $rent_amount = '';

    #[Validate('required|numeric|min:0')]
    public $deposit_amount = '';

    public function with()
    {
        return [
            'units' => Unit::whereDoesntHave('leases', function ($query) {
                $query->where('status', 'active');
            })->when($this->unit_id, fn($q) => $q->orWhere('id', $this->unit_id)) // Keep current unit if editing/pre-filled
                ->with('property')
                ->get(),
            'renters' => Renter::all(),
        ];
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

    public function store()
    {
        $this->validate();

        $unit = Unit::with('property')->find($this->unit_id);

        if ($unit->property->status === \App\Enums\PropertyStatus::Maintenance) {
            $this->addError('unit_id', 'Impossible de créer un bail : la propriété est en maintenance.');
            return;
        }

        Lease::create([
            'unit_id' => $this->unit_id,
            'renter_id' => $this->renter_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date ?: null,
            'rent_amount' => $this->rent_amount,
            'deposit_amount' => $this->deposit_amount,
            'status' => 'active',
        ]);

        $unit->update(['status' => 'occupied']);

        $this->dispatch('lease-created');
        $this->js("Flux.toast('Bail créé avec succès.')");
        $this->js("Flux.modal('create-lease').close()");

        $this->reset();
    }
};
?>

<flux:modal name="create-lease" class="min-w-[500px]">
    <form wire:submit="store" class="space-y-6">
        <div>
            <flux:heading size="lg">Nouveau Bail</flux:heading>
            <flux:subheading>Créer un nouveau contrat de location.</flux:subheading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:select wire:model.live="unit_id" label="Unité" placeholder="Sélectionner une unité...">
                @foreach ($units as $unit)
                    <flux:select.option value="{{ $unit->id }}">
                        {{ $unit->property->name }} - {{ $unit->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="renter_id" label="Locataire" placeholder="Sélectionner un locataire...">
                @foreach ($renters as $renter)
                    <flux:select.option value="{{ $renter->id }}">
                        {{ $renter->first_name }} {{ $renter->last_name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:input wire:model="start_date" type="date" label="Date de début" />
            <flux:input wire:model="end_date" type="date" label="Date de fin" description="Optionnel" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:input wire:model="rent_amount" type="number" step="0.01" label="Loyer"
                prefix="{{ config('app.currency', '$') }}" />
            <flux:input wire:model="deposit_amount" type="number" step="0.01" label="Dépôt"
                prefix="{{ config('app.currency', '$') }}" />
        </div>

        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Créer le Bail</flux:button>
        </div>
    </form>
</flux:modal>