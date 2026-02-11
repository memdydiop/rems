<?php

use App\Models\Renter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new
    #[Layout('layouts.renter', ['title' => 'Mes Paiements'])]
    class extends Component {
    use WithPagination;

    #[Computed]
    public function payments()
    {
        $renter = Renter::where('user_id', auth()->id())->first();

        if (!$renter)
            return null;

        return \App\Models\RentPayment::whereHas('lease', function ($query) use ($renter) {
            $query->where('renter_id', $renter->id);
        })
            ->orderByDesc('paid_at')
            ->paginate(10);
    }
};
?>

<div class="min-h-screen bg-zinc-50">
    <!-- Header -->
    <div class="bg-white border-b border-zinc-200">
        <div class="max-w-4xl mx-auto px-4 py-6">
            <div class="flex items-center gap-4">
                <flux:button variant="ghost" icon="arrow-left" href="{{ route('renter.dashboard') }}" />
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900">Historique des Paiements</h1>
                    <p class="text-zinc-500">Consultez tous vos paiements de loyer</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <x-flux::card>
            @if(!$this->payments || $this->payments->isEmpty())
                <div class="p-12 text-center">
                    <div class="size-16 rounded-full bg-zinc-100 flex items-center justify-center mx-auto mb-4">
                        <flux:icon.banknotes class="size-8 text-zinc-400" />
                    </div>
                    <h3 class="font-semibold text-zinc-900 mb-1">Aucun paiement</h3>
                    <p class="text-sm text-zinc-500">Vous n'avez pas encore effectué de paiement.</p>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Date</flux:table.column>
                        <flux:table.column>Montant</flux:table.column>
                        <flux:table.column>Méthode</flux:table.column>
                        <flux:table.column>Statut</flux:table.column>
                        <flux:table.column align="right">Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->payments as $payment)
                            <flux:table.row :key="$payment->id">
                                <flux:table.cell>{{ $payment->paid_at->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell class="font-medium">{{ number_format($payment->amount, 0, ',', ' ') }} XOF
                                </flux:table.cell>
                                <flux:table.cell>{{ ucfirst($payment->method ?? 'Espèces') }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$payment->status->color()">
                                        {{ $payment->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="right">
                                    <flux:button size="xs" variant="ghost" icon="document-arrow-down"
                                        href="{{ route('tenant.pdf.payment', $payment) }}">
                                        Reçu
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="mt-4">
                    {{ $this->payments->links() }}
                </div>
            @endif
        </x-flux::card>
    </div>
</div>