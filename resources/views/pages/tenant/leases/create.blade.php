<?php

use App\Models\Lease;
use App\Models\Unit;
use App\Models\Renter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.app', ['title' => 'Create Lease'])] class extends Component {
    // Step 1: Select Unit and Renter
    #[Validate('required|exists:units,id')]
    public $unit_id = '';

    #[Validate('required|exists:renters,id')]
    public $renter_id = '';

    // Step 2: Lease Terms
    #[Validate('required|date')]
    public $start_date = '';

    #[Validate('nullable|date|after:start_date')]
    public $end_date = '';

    #[Validate('required|numeric|min:0')]
    public $rent_amount = '';

    #[Validate('required|numeric|min:0')]
    public $deposit_amount = '';

    // Computed properties for dropdowns
    public function with()
    {
        return [
            'units' => Unit::whereDoesntHave('leases', function ($query) {
                $query->where('status', 'active');
            })->get(), // Only show available units
            'renters' => Renter::all(),
        ];
    }

    public function mount()
    {
        // Pre-fill if passed via query params (e.g. from Unit Show page)
        $this->unit_id = request('unit_id', '');
    }

    public function store()
    {
        $this->validate();

        Lease::create([
            'unit_id' => $this->unit_id,
            'renter_id' => $this->renter_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date ?: null,
            'rent_amount' => $this->rent_amount,
            'deposit_amount' => $this->deposit_amount,
            'status' => 'active', // Default to active for now
        ]);

        // Update Unit status
        $unit = Unit::find($this->unit_id);
        $unit->update(['status' => 'occupied']);

        $this->js("Flux.toast('Bail créé avec succès.')");
        return $this->redirect(route('tenant.properties.index'), navigate: true);
        // Ideally redirect to the Lease Show page or Unit Show page
    }
};
?>

<div>
    <x-layouts::content heading="Nouveau Bail" subheading="Créer un nouveau contrat de bail.">
        <x-flux::card class="max-w-2xl mx-auto">
            <form wire:submit="store" class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Unit Selection -->
                    <flux:select wire:model="unit_id" label="Unité" placeholder="Sélectionner une unité...">
                        @foreach ($units as $unit)
                            <flux:select.option value="{{ $unit->id }}">
                                {{ $unit->property->name }} - {{ $unit->name }} ({{ Number::currency($unit->rent_amount) }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <!-- Renter Selection -->
                    <flux:select wire:model="renter_id" label="Locataire" placeholder="Sélectionner un locataire...">
                        @foreach ($renters as $renter)
                            <flux:select.option value="{{ $renter->id }}">
                                {{ $renter->first_name }} {{ $renter->last_name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:separator />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:input wire:model="start_date" type="date" label="Date de début" />
                    <flux:input wire:model="end_date" type="date" label="Date de fin"
                        description="Laisser vide pour un contrat mensuel" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:input wire:model="rent_amount" type="number" step="0.01" label="Montant du loyer"
                        prefix="{{ config('app.currency', '$') }}" />
                    <flux:input wire:model="deposit_amount" type="number" step="0.01" label="Dépôt de garantie"
                        prefix="{{ config('app.currency', '$') }}" />
                </div>

                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">Créer le Bail</flux:button>
                </div>

            </form>
        </x-flux::card>
    </x-layouts::content>
</div>