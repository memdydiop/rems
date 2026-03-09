<?php

use App\Models\Lease;
use App\Models\RentPayment;
use App\Enums\PaymentStatus;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Flux\Flux;

new class extends Component {
    public ?Lease $lease = null;

    #[Validate('required|numeric|min:0')]
    public $amount = '';

    #[Validate('required|date')]
    public $paid_at = '';

    #[Validate('required|date')]
    public $period_start = '';

    #[Validate('required|date|after_or_equal:period_start')]
    public $period_end = '';

    #[Validate('required|string')]
    public $method = 'cash'; // cash, bank_transfer, mobile_money, cheque

    #[Validate('nullable|string')]
    public $notes = '';

    #[On('record-payment')]
    public function openModal($lease_id)
    {
        $this->lease = Lease::with('client', 'unit.property')->findOrFail($lease_id);

        $this->amount = $this->lease->rent_amount + $this->lease->charges_amount;
        $this->paid_at = now()->format('Y-m-d');
        $this->period_start = now()->startOfMonth()->format('Y-m-d');
        $this->period_end = now()->endOfMonth()->format('Y-m-d');
        $this->calculateAmount();
        $this->method = 'cash';
        $this->notes = '';

        Flux::modal('record-payment')->show();
    }

    public function updatedPeriodStart()
    {
        $this->calculateAmount();
    }
    public function updatedPeriodEnd()
    {
        $this->calculateAmount();
    }

    public function calculateAmount()
    {
        if (!$this->lease || !$this->period_start || !$this->period_end)
            return;

        $start = \Carbon\Carbon::parse($this->period_start)->startOfMonth();
        $end = \Carbon\Carbon::parse($this->period_end)->startOfMonth();

        $months = $start->diffInMonths($end) + 1;
        if ($months < 1)
            $months = 1;

        $this->amount = ($this->lease->rent_amount + $this->lease->charges_amount) * $months;
    }

    public function store()
    {
        $this->validate();

        if (!$this->lease)
            return;

        $pStart = \Carbon\Carbon::parse($this->period_start)->startOfMonth();
        $pEnd = \Carbon\Carbon::parse($this->period_end)->endOfMonth();

        $payment = $this->lease->payments()->create([
            'amount' => $this->amount,
            'paid_at' => $this->paid_at,
            'period_start' => $pStart->format('Y-m-d'),
            'period_end' => $pEnd->format('Y-m-d'),
            'method' => $this->method,
            'status' => PaymentStatus::Completed,
            'notes' => $this->notes ?: null,
        ]);

        $this->dispatch('payment-recorded');
        $this->js("Flux.toast('Paiement enregistré avec succès.')");
        $this->js("Flux.modal('record-payment').close()");
    }
};
?>

<flux:modal name="record-payment" class="min-w-100">
    <form wire:submit="store" class="space-y-6">
        <div>
            <flux:heading size="lg">Enregistrer un Paiement</flux:heading>
            <flux:subheading>Saisissez les détails du règlement de loyer.</flux:subheading>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="amount" type="number" step="0.01" label="Montant (FCFA)" placeholder="0" />
            <flux:input wire:model="paid_at" type="date" label="Date de paiement" />
        </div>

        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <flux:label>Période couverte</flux:label>
                @php
                    $previewStart = \Carbon\Carbon::parse($period_start)->startOfMonth();
                    $previewEnd = \Carbon\Carbon::parse($period_end)->startOfMonth();
                    $mCount = $previewStart->diffInMonths($previewEnd) + 1;
                @endphp
                <flux:badge size="sm" color="zinc" variant="subtle">
                    {{ $mCount > 0 ? $mCount : 1 }} mois
                </flux:badge>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model.live="period_start" type="date" label="Date de début" />
                <flux:input wire:model.live="period_end" type="date" label="Date de fin" />
            </div>
        </div>

        <div>
            <flux:select wire:model="method" label="Méthode de paiement">
                <flux:select.option value="cash">Espèces</flux:select.option>
                <flux:select.option value="bank_transfer">Virement bancaire</flux:select.option>
                <flux:select.option value="mobile_money">Mobile Money</flux:select.option>
                <flux:select.option value="cheque">Chèque</flux:select.option>
            </flux:select>
        </div>

        <flux:textarea wire:model="notes" label="Notes (Optionnel)" rows="2"
            placeholder="Référence de transaction, etc..." />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Annuler</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Enregistrer le paiement</flux:button>
        </div>
    </form>
</flux:modal>