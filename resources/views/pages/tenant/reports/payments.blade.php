<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use App\Models\RentPayment;
use App\Models\Client;
use App\Models\Property;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentsExport;

new #[Layout('layouts.app', ['title' => 'Historique des Paiements'])] class extends Component {

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
            ->with(['lease.client', 'lease.unit.property'])
            ->when($this->year, fn($q) => $q->whereYear('paid_at', $this->year))
            ->when($this->month, fn($q) => $q->whereMonth('paid_at', $this->month))
            ->when($this->search, fn($q) => $q->whereHas(
                'lease.client',
                fn($r) =>
                $r->where('first_name', 'ilike', "%{$this->search}%")
                    ->orWhere('last_name', 'ilike', "%{$this->search}%")
            ))
            ->orderByDesc('paid_at')
            ->paginate(20);
    }

    #[Computed]
    public function stats()
    {
        $baseQuery = RentPayment::query()
            ->when($this->year, fn($q) => $q->whereYear('paid_at', $this->year))
            ->when($this->month, fn($q) => $q->whereMonth('paid_at', $this->month));

        return [
            'total' => (clone $baseQuery)->sum('amount'),
            'count' => (clone $baseQuery)->count(),
        ];
    }

    #[Computed]
    public function monthlyTrend()
    {
        $year = $this->year ?: now()->year;
        $data = collect();

        for ($month = 1; $month <= 12; $month++) {
            $amount = RentPayment::whereYear('paid_at', $year)
                ->whereMonth('paid_at', $month)
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
        $startYear = RentPayment::min('paid_at')
            ? Carbon::parse(RentPayment::min('paid_at'))->year
            : now()->year;
        return range(now()->year, $startYear);
    }

    public function resetFilters()
    {
        $this->reset(['month', 'search']);
        $this->year = now()->year;
    }
    public function exportPayments()
    {
        return Excel::download(
            new PaymentsExport($this->year, $this->month),
            'paiements_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
};
?>

<div>
    <x-layouts::content heading="Historique des Paiements" subheading="Suivi détaillé de tous les paiements de loyer.">

        <x-slot name="actions">
            <flux:button icon="funnel" variant="ghost" wire:click="resetFilters">
                Réinitialiser
            </flux:button>
            <flux:button icon="arrow-down-tray" variant="ghost" wire:click="exportPayments">
                Exporter
            </flux:button>
            <flux:button icon="printer" variant="ghost" onclick="window.print()">
                Imprimer
            </flux:button>
        </x-slot>

        <!-- Filters -->
        <x-flux::card class="mb-6">
            <div class="p-4 flex flex-wrap gap-4">
                <flux:input icon="magnifying-glass" wire:model.live.debounce="search"
                    placeholder="Rechercher par client..." class="w-48" />

                <flux:select wire:model.live="year" class="w-32">
                    @foreach($this->years as $y)
                        <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="month" placeholder="Tous les mois" class="w-36">
                    <flux:select.option value="">Tous</flux:select.option>
                    @foreach(range(1, 12) as $m)
                        <flux:select.option value="{{ $m }}">
                            {{ Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </x-flux::card>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <x-flux::card class="bg-zinc-50 border-zinc-200">
                <div class="p-4 text-center">
                    <p class="text-2xl font-bold text-zinc-700">
                        {{ \Illuminate\Support\Number::currency($this->stats['total'], 'XOF') }}
                    </p>
                    <p class="text-sm text-zinc-600">Total Encaissé</p>
                </div>
            </x-flux::card>

            <x-flux::card class="bg-emerald-50 border-emerald-200">
                <div class="p-4 text-center">
                    <p class="text-2xl font-bold text-emerald-700">
                        {{ $this->stats['count'] }}
                    </p>
                    <p class="text-sm text-emerald-600">Paiements Enregistrés</p>
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
                    <x-flux::table.column>Période</x-flux::table.column>
                    <x-flux::table.column>Client</x-flux::table.column>
                    <x-flux::table.column>Propriété</x-flux::table.column>
                    <x-flux::table.column>Montant</x-flux::table.column>
                    <x-flux::table.column>Méthode</x-flux::table.column>
                </x-flux::table.columns>
                <x-flux::table.rows>
                    @forelse($this->payments as $payment)
                        <x-flux::table.row>
                            <x-flux::table.cell>{{ $payment->paid_at->format('d/m/Y') }}</x-flux::table.cell>
                            <x-flux::table.cell class="text-xs text-zinc-600">
                                <div class="flex items-center gap-2">
                                    @if($payment->period_start && $payment->period_end)
                                        @if($payment->period_start->isSameMonth($payment->period_end))
                                            {{ $payment->period_start->translatedFormat('F Y') }}
                                        @else
                                            <span class="whitespace-nowrap italic text-zinc-500">
                                                {{ $payment->period_start->translatedFormat('M Y') }}
                                                → {{ $payment->period_end->translatedFormat('M Y') }}
                                            </span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                    @if($payment->months_count > 1)
                                        <flux:badge size="2xs" color="zinc" variant="outline"
                                            class="text-[9px] px-1 py-0 h-4 min-h-0">
                                            {{ $payment->months_count }} mois
                                        </flux:badge>
                                    @endif
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell class="font-medium">
                                {{ $payment->lease->client->first_name }} {{ $payment->lease->client->last_name }}
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <div>
                                    <p class="text-sm font-medium">{{ $payment->lease->unit->property->name }}</p>
                                    <p class="text-xs text-zinc-500">{{ $payment->lease->unit->name }}</p>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell class="font-semibold text-emerald-600">
                                {{ \Illuminate\Support\Number::currency($payment->amount, 'XOF') }}
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:badge size="sm" color="zinc">{{ ucfirst($payment->method ?? 'Espèces') }}
                                </flux:badge>
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