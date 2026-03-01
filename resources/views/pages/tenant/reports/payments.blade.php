<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use App\Models\RentPayment;
use App\Models\Renter;
use App\Models\Property;
use Carbon\Carbon;

new
    #[Layout('layouts.app', ['title' => 'Historique des Paiements'])]
    class extends Component {

    #[Url]
    public string $status = '';

    #[Url]
    public string $year = '';

    #[Url]
    public string $month = '';

    #[Url]
    public string $search = '';

    public function mount()
    {
        $this->year = $this->year ?: now()->year;
    }

    #[Computed]
    public function payments()
    {
        return RentPayment::query()
            ->with(['lease.renter', 'lease.unit.property'])
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->year, fn($q) => $q->whereYear('payment_date', $this->year))
            ->when($this->month, fn($q) => $q->whereMonth('payment_date', $this->month))
            ->when($this->search, fn($q) => $q->whereHas(
                'lease.renter',
                fn($r) =>
                $r->where('first_name', 'ilike', "%{$this->search}%")
                    ->orWhere('last_name', 'ilike', "%{$this->search}%")
            ))
            ->orderByDesc('payment_date')
            ->paginate(20);
    }

    #[Computed]
    public function stats()
    {
        $baseQuery = RentPayment::query()
            ->when($this->year, fn($q) => $q->whereYear('payment_date', $this->year))
            ->when($this->month, fn($q) => $q->whereMonth('payment_date', $this->month));

        return [
            'total' => (clone $baseQuery)->sum('amount'),
            'paid' => (clone $baseQuery)->where('status', 'completed')->sum('amount'),
            'pending' => (clone $baseQuery)->where('status', 'pending')->sum('amount'),
            'overdue' => (clone $baseQuery)->where('status', 'overdue')->sum('amount'),
            'count' => (clone $baseQuery)->count(),
        ];
    }

    #[Computed]
    public function monthlyTrend()
    {
        $year = $this->year ?: now()->year;
        $data = collect();

        for ($month = 1; $month <= 12; $month++) {
            $amount = RentPayment::whereYear('payment_date', $year)
                ->whereMonth('payment_date', $month)
                ->where('status', 'completed')
                ->sum('amount');

            $data->push([
                'month' => Carbon::createFromDate($year, $month, 1)->translatedFormat('M'),
                'amount' => $amount,
            ]);
        }

        return $data;
    }

    #[Computed]
    public function years()
    {
        $startYear = RentPayment::min('payment_date')
            ? Carbon::parse(RentPayment::min('payment_date'))->year
            : now()->year;
        return range(now()->year, $startYear);
    }

    public function resetFilters()
    {
        $this->reset(['status', 'month', 'search']);
        $this->year = now()->year;
    }
};
?>

<div>
    <x-layouts::content heading="Historique des Paiements" subheading="Suivi détaillé de tous les paiements de loyer.">

        <x-slot name="actions">
            <flux:button icon="funnel" variant="ghost" wire:click="resetFilters">
                Réinitialiser
            </flux:button>
            <flux:button icon="printer" variant="ghost" onclick="window.print()">
                Imprimer
            </flux:button>
        </x-slot>

        <!-- Filters -->
        <x-flux::card class="mb-6">
            <div class="p-4 flex flex-wrap gap-4">
                <flux:input icon="magnifying-glass" wire:model.live.debounce="search"
                    placeholder="Rechercher par locataire..." class="w-48" />

                <flux:select wire:model.live="year" class="w-32">
                    @foreach($this->years as $y)
                        <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="month" placeholder="Tous les mois" class="w-36">
                    <flux:select.option value="">Tous</flux:select.option>
                    @foreach(range(1, 12) as $m)
                        <flux:select.option value="{{ $m }}">
                            {{ Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="status" placeholder="Tous statuts" class="w-36">
                    <flux:select.option value="">Tous</flux:select.option>
                    <flux:select.option value="completed">Payé</flux:select.option>
                    <flux:select.option value="pending">En attente</flux:select.option>
                    <flux:select.option value="overdue">En retard</flux:select.option>
                </flux:select>
            </div>
        </x-flux::card>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <x-flux::card class="bg-zinc-50">
                <div class="p-4 text-center">
                    <p class="text-2xl font-bold text-zinc-900">
                        {{ \Illuminate\Support\Number::currency($this->stats['total'], 'XOF') }}
                    </p>
                    <p class="text-sm text-zinc-600">Total</p>
                </div>
            </x-flux::card>

            <x-flux::card class="bg-emerald-50 border-emerald-200">
                <div class="p-4 text-center">
                    <p class="text-2xl font-bold text-emerald-700">
                        {{ \Illuminate\Support\Number::currency($this->stats['paid'], 'XOF') }}
                    </p>
                    <p class="text-sm text-emerald-600">Payé</p>
                </div>
            </x-flux::card>

            <x-flux::card class="bg-amber-50 border-amber-200">
                <div class="p-4 text-center">
                    <p class="text-2xl font-bold text-amber-700">
                        {{ \Illuminate\Support\Number::currency($this->stats['pending'], 'XOF') }}
                    </p>
                    <p class="text-sm text-amber-600">En attente</p>
                </div>
            </x-flux::card>

            <x-flux::card class="bg-red-50 border-red-200">
                <div class="p-4 text-center">
                    <p class="text-2xl font-bold text-red-700">
                        {{ \Illuminate\Support\Number::currency($this->stats['overdue'], 'XOF') }}
                    </p>
                    <p class="text-sm text-red-600">En retard</p>
                </div>
            </x-flux::card>
        </div>

        <!-- Monthly Trend Chart -->
        <x-flux::card class="mb-6">
            <x-flux::card.header title="Tendance Mensuelle {{ $this->year }}" />
            <div class="p-4 h-64" x-data="{
                init() {
                    const options = {
                        series: [{
                            name: 'Paiements',
                            data: {{ Js::from($this->monthlyTrend->pluck('amount')->toArray()) }}
                        }],
                        chart: { type: 'area', height: 220, toolbar: { show: false } },
                        xaxis: { categories: {{ Js::from($this->monthlyTrend->pluck('month')->toArray()) }} },
                        colors: ['#10b981'],
                        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.1 } },
                        stroke: { curve: 'smooth', width: 2 },
                        grid: { borderColor: '#f4f4f5' },
                        dataLabels: { enabled: false }
                    };
                    new ApexCharts(this.$el, options).render();
                }
            }"></div>
        </x-flux::card>

        <!-- Payments Table -->
        <x-flux::card>
            <x-flux::card.header title="Détail des Paiements ({{ $this->stats['count'] }})" />
            <x-flux::table>
                <x-flux::table.columns>
                    <x-flux::table.column>Date</x-flux::table.column>
                    <x-flux::table.column>Locataire</x-flux::table.column>
                    <x-flux::table.column>Propriété</x-flux::table.column>
                    <x-flux::table.column>Montant</x-flux::table.column>
                    <x-flux::table.column>Méthode</x-flux::table.column>
                    <x-flux::table.column>Statut</x-flux::table.column>
                </x-flux::table.columns>
                <x-flux::table.rows>
                    @forelse($this->payments as $payment)
                        <x-flux::table.row>
                            <x-flux::table.cell>{{ $payment->payment_date->format('d/m/Y') }}</x-flux::table.cell>
                            <x-flux::table.cell class="font-medium">
                                {{ $payment->lease->renter->first_name }} {{ $payment->lease->renter->last_name }}
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <div>
                                    <p class="text-sm">{{ $payment->lease->unit->property->name }}</p>
                                    <p class="text-xs text-zinc-500">{{ $payment->lease->unit->name }}</p>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell class="font-semibold text-emerald-600">
                                {{ \Illuminate\Support\Number::currency($payment->amount, 'XOF') }}
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:badge size="sm" color="zinc">{{ ucfirst($payment->payment_method ?? 'Espèces') }}
                                </flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                @php
                                    $color = match ($payment->status) {
                                        'completed' => 'green',
                                        'pending' => 'yellow',
                                        'overdue' => 'red',
                                        default => 'zinc'
                                    };
                                    $label = match ($payment->status) {
                                        'completed' => 'Payé',
                                        'pending' => 'En attente',
                                        'overdue' => 'En retard',
                                        default => $payment->status
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$color">{{ $label }}</flux:badge>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @empty
                        <x-flux::table.row>
                            <x-flux::table.cell colspan="6" class="text-center text-zinc-400 py-8">
                                Aucun paiement trouvé
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>

            @if($this->payments->hasPages())
                <div class="p-4 border-t border-zinc-100">
                    {{ $this->payments->links() }}
                </div>
            @endif
        </x-flux::card>

    </x-layouts::content>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>