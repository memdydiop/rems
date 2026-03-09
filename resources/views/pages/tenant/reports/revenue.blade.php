<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new
    #[Layout('layouts.app', ['title' => 'Rapport des Revenus'])]
    class extends Component {

    #[Url]
    public string $period = 'month';

    #[Url]
    public ?string $startDate = null;

    #[Url]
    public ?string $endDate = null;

    public function mount()
    {
        if (!$this->startDate) {
            $this->startDate = now()->startOfMonth()->format('Y-m-d');
        }
        if (!$this->endDate) {
            $this->endDate = now()->endOfMonth()->format('Y-m-d');
        }
    }

    public function setPeriod(string $period)
    {
        $this->period = $period;

        match ($period) {
            'month' => $this->setDates(now()->startOfMonth(), now()->endOfMonth()),
            'quarter' => $this->setDates(now()->startOfQuarter(), now()->endOfQuarter()),
            'year' => $this->setDates(now()->startOfYear(), now()->endOfYear()),
            'custom' => null,
            default => null,
        };
    }

    private function setDates($start, $end)
    {
        $this->startDate = $start->format('Y-m-d');
        $this->endDate = $end->format('Y-m-d');
    }

    #[Computed]
    public function revenueData()
    {
        $start = \Carbon\Carbon::parse($this->startDate);
        $end = \Carbon\Carbon::parse($this->endDate);

        // Get payments in date range
        $payments = \App\Models\RentPayment::whereBetween('paid_at', [$start, $end])
            ->where('status', \App\Enums\PaymentStatus::Completed)
            ->get();

        $totalCollected = $payments->sum('amount');

        // Get expenses in date range
        $expenses = \App\Models\Expense::whereBetween('date', [$start, $end])
            ->where('status', 'paid')
            ->get();

        $totalExpenses = $expenses->sum('amount');

        // Expected revenue from active leases
        $activeLeases = \App\Models\Lease::where('status', 'active')->get();
        $monthlyExpected = $activeLeases->sum('rent_amount');
        $months = max(1, $start->diffInMonths($end) + 1);
        $totalExpected = $monthlyExpected * $months;

        // Collection rate
        $collectionRate = $totalExpected > 0 ? round(($totalCollected / $totalExpected) * 100) : 0;

        return [
            'collected' => (float) $totalCollected,
            'expected' => (float) $totalExpected,
            'expenses' => (float) $totalExpenses,
            'net' => (float) ($totalCollected - $totalExpenses),
            'pending' => max(0, $totalExpected - $totalCollected),
            'rate' => $collectionRate,
            'paymentsCount' => $payments->count(),
        ];
    }

    #[Computed]
    public function monthlyBreakdown()
    {
        $start = \Carbon\Carbon::parse($this->startDate);
        $end = \Carbon\Carbon::parse($this->endDate);

        $months = [];
        $current = $start->copy()->startOfMonth();

        while ($current <= $end) {
            $monthStart = $current->copy();
            $monthEnd = $current->copy()->endOfMonth();

            $collected = \App\Models\RentPayment::whereBetween('paid_at', [$monthStart, $monthEnd])
                ->where('status', \App\Enums\PaymentStatus::Completed)
                ->sum('amount');

            $monthExpenses = \App\Models\Expense::whereBetween('date', [$monthStart, $monthEnd])
                ->where('status', 'paid')
                ->sum('amount');

            $months[] = [
                'month' => $current->translatedFormat('F Y'),
                'collected' => (float) $collected,
                'expenses' => (float) $monthExpenses,
                'net' => (float) ($collected - $monthExpenses),
            ];

            $current->addMonth();
        }

        return $months;
    }

    #[Computed]
    public function chartData()
    {
        return [
            'collected' => collect($this->monthlyBreakdown)->pluck('collected')->values()->toArray(),
            'expenses' => collect($this->monthlyBreakdown)->pluck('expenses')->values()->toArray(),
        ];
    }

    #[Computed]
    public function chartLabels()
    {
        return collect($this->monthlyBreakdown)->pluck('month')->values()->toArray();
    }
};
?>

<div>
    <x-layouts::content heading="Rapport des Revenus" subheading="Analysez vos revenus locatifs par période.">

        <x-slot name="actions">
            <flux:button icon="printer" variant="ghost" onclick="window.print()">
                Imprimer
            </flux:button>
        </x-slot>

        <!-- Period Filters -->
        <div class="flex flex-wrap items-center gap-4 mb-6 print:hidden">
            <div class="flex bg-zinc-100 p-1 rounded-lg">
                <button wire:click="setPeriod('month')"
                    class="px-4 py-2 text-sm font-medium rounded-md {{ $period === 'month' ? 'bg-white shadow-sm text-zinc-900' : 'text-zinc-500 hover:text-zinc-700' }}">
                    Ce Mois
                </button>
                <button wire:click="setPeriod('quarter')"
                    class="px-4 py-2 text-sm font-medium rounded-md {{ $period === 'quarter' ? 'bg-white shadow-sm text-zinc-900' : 'text-zinc-500 hover:text-zinc-700' }}">
                    Ce Trimestre
                </button>
                <button wire:click="setPeriod('year')"
                    class="px-4 py-2 text-sm font-medium rounded-md {{ $period === 'year' ? 'bg-white shadow-sm text-zinc-900' : 'text-zinc-500 hover:text-zinc-700' }}">
                    Cette Année
                </button>
                <button wire:click="setPeriod('custom')"
                    class="px-4 py-2 text-sm font-medium rounded-md {{ $period === 'custom' ? 'bg-white shadow-sm text-zinc-900' : 'text-zinc-500 hover:text-zinc-700' }}">
                    Personnalisé
                </button>
            </div>

            @if($period === 'custom')
                <div class="flex items-center gap-2">
                    <flux:input type="date" wire:model.live="startDate" label="Du" />
                    <flux:input type="date" wire:model.live="endDate" label="Au" />
                </div>
            @endif
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <x-flux::card class="bg-emerald-50 border-emerald-200">
                <div class="p-4 text-center">
                    <p class="text-2xl font-bold text-emerald-700">
                        {{ \Illuminate\Support\Number::currency($this->revenueData['collected'], 'XOF') }}
                    </p>
                    <p class="text-xs text-emerald-600">Revenus Collectés</p>
                </div>
            </x-flux::card>

            <x-flux::card class="bg-rose-50 border-rose-200">
                <div class="p-4 text-center">
                    <p class="text-2xl font-bold text-rose-700">
                        {{ \Illuminate\Support\Number::currency($this->revenueData['expenses'], 'XOF') }}
                    </p>
                    <p class="text-xs text-rose-600">Dépenses Payées</p>
                </div>
            </x-flux::card>

            <x-flux::card class="bg-indigo-50 border-indigo-200">
                <div class="p-4 text-center">
                    <p class="text-2xl font-bold text-indigo-700">
                        {{ \Illuminate\Support\Number::currency($this->revenueData['net'], 'XOF') }}
                    </p>
                    <p class="text-xs text-indigo-600 font-semibold">Bénefice Net</p>
                </div>
            </x-flux::card>

            <x-flux::card class="bg-blue-50 border-blue-200">
                <div class="p-4 text-center">
                    <p class="text-2xl font-bold text-blue-700">
                        {{ \Illuminate\Support\Number::currency($this->revenueData['expected'], 'XOF') }}
                    </p>
                    <p class="text-xs text-blue-600 font-medium">Attendus (Baux)</p>
                </div>
            </x-flux::card>

            <x-flux::card class="bg-amber-50 border-amber-200">
                <div class="p-4 text-center">
                    <p class="text-2xl font-bold text-amber-700">{{ $this->revenueData['rate'] }}%</p>
                    <p class="text-xs text-amber-600">Taux de Collecte</p>
                </div>
            </x-flux::card>
        </div>

        <!-- Chart -->

        <!-- Chart -->
        <x-flux::card class="mb-6">
            <x-flux::card.header title="Évolution des Revenus" />
            <div class="p-4 h-80" x-data="{
                init() {
                    const options = {
                        series: [
                            { name: 'Revenus', data: {{ json_encode($this->chartData['collected']) }} },
                            { name: 'Dépenses', data: {{ json_encode($this->chartData['expenses']) }} }
                        ],
                        chart: { type: 'bar', height: 300, toolbar: { show: false }, stacked: false },
                        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                        dataLabels: { enabled: false },
                        xaxis: { 
                            categories: {{ Js::from($this->chartLabels) }},
                            labels: { style: { colors: '#71717a' } }
                        },
                        yaxis: { 
                            labels: { 
                                formatter: (val) => new Intl.NumberFormat('fr-FR').format(val) + ' F',
                                style: { colors: '#71717a' }
                            } 
                        },
                        colors: ['#10b981', '#ef4444'],
                        grid: { borderColor: '#f4f4f5' }
                    };
                    new ApexCharts(this.$el, options).render();
                }
            }"></div>
        </x-flux::card>

        <!-- Monthly Breakdown Table -->
        <x-flux::card>
            <x-flux::card.header title="Détail Mensuel" />
            <x-flux::table>
                <x-flux::table.columns>
                    <x-flux::table.column>Mois</x-flux::table.column>
                    <x-flux::table.column>Collecté</x-flux::table.column>
                    <x-flux::table.column>Dépenses</x-flux::table.column>
                    <x-flux::table.column>Net</x-flux::table.column>
                </x-flux::table.columns>
                <x-flux::table.rows>
                    @foreach($this->monthlyBreakdown as $month)
                        <x-flux::table.row>
                            <x-flux::table.cell>{{ $month['month'] }}</x-flux::table.cell>
                            <x-flux::table.cell class="font-semibold text-emerald-600">
                                {{ \Illuminate\Support\Number::currency($month['collected'], 'XOF') }}
                            </x-flux::table.cell>
                            <x-flux::table.cell class="text-red-500">
                                {{ \Illuminate\Support\Number::currency($month['expenses'], 'XOF') }}
                            </x-flux::table.cell>
                            <x-flux::table.cell
                                class="font-bold {{ $month['net'] >= 0 ? 'text-indigo-600' : 'text-red-600' }}">
                                {{ \Illuminate\Support\Number::currency($month['net'], 'XOF') }}
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforeach
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>

    </x-layouts::content>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>