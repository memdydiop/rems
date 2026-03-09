<?php

use App\Models\Unit;
use App\Models\Client;
use App\Models\Sale;
use App\Enums\UnitStatus;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component {
    public ?Unit $unit = null;

    #[Validate('required|exists:clients,id')]
    public $client_id = '';

    #[Validate('required|numeric|min:0')]
    public $sale_price = null;

    #[Validate('required|date')]
    public $sold_at = '';

    public $modalOpen = false;

    public function mount()
    {
        $this->sold_at = now()->format('Y-m-d');
    }

    #[On('open-modal')]
    public function open($name, $unit_id = null)
    {
        if ($name === 'record-sale') {
            $this->reset(['unit', 'client_id', 'sale_price']);

            if ($unit_id) {
                $this->unit = Unit::with('property')->findOrFail($unit_id);
                $this->sale_price = $this->unit->sale_price;
            }

            $this->modalOpen = true;
        }
    }

    public function save()
    {
        $this->validate();

        Sale::create([
            'unit_id' => $this->unit->id,
            'client_id' => $this->client_id,
            'sale_price' => $this->sale_price,
            'sold_at' => $this->sold_at,
        ]);

        $this->unit->update(['status' => UnitStatus::Sold]);

        Flux::toast('Vente enregistrée avec succès.');
        $this->modalOpen = false;

        $this->dispatch('unit-updated');
        return redirect()->route('tenant.units.show', $this->unit);
    }
};
?>

<flux:modal wire:model="modalOpen" class="md:w-md">
    <form wire:submit="save" class="space-y-6">
        <div>
            <h2 class="text-lg font-bold text-zinc-900">Enregistrer une vente</h2>
            <p class="text-sm text-zinc-500">Documentez la transaction pour ce bien.</p>
        </div>

        @if($unit)
            <div class="p-3 bg-zinc-50 rounded-lg border border-zinc-100 mb-4">
                <div class="text-xs text-zinc-500 uppercase font-bold tracking-wider mb-1">Unité</div>
                <div class="text-sm font-medium text-zinc-900">{{ $unit->name }} - {{ $unit->property->name }}</div>
            </div>
        @endif

        <flux:select wire:model="client_id" label="Acquéreur" placeholder="Sélectionner le client...">
            @foreach (Client::orderBy('last_name')->get() as $client)
                <flux:select.option value="{{ $client->id }}">{{ $client->full_name }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="sale_price" type="number" step="0.01" label="Prix de vente final"
                prefix="{{ config('app.currency', 'FCFA') }}" />

            <flux:input wire:model="sold_at" type="date" label="Date de la vente" />
        </div>

        <div class="flex justify-end gap-2 pt-4">
            <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Annuler</flux:button>
            <flux:button type="submit" variant="primary">Confirmer la vente</flux:button>
        </div>
    </form>
</flux:modal>