<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\Payment;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Traits\WithDataTable;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new
    #[Layout('layouts.app', ['title' => 'Dashboard'])]
    class extends Component {
    use WithPagination;
    use WithDataTable;

    #[Computed]
    public function setupComplete()
    {
        return Plan::exists() && Tenant::exists();
    }

    #[Computed]
    public function stats()
    {
        // Calculate Revenue
        // We need to normalize amounts based on currency.
        // For MVP, assuming NGN (divide by 100) or XOF (divide by 1).
        $revenue = Payment::where('status', 'success')->get()->reduce(function ($carry, $payment) {
            $amount = $payment->amount;
            if ($payment->currency === 'NGN') {
                $amount = $amount / 100;
            }
            return $carry + $amount;
        }, 0);

        return [
            'users' => User::whereNotGhost()->count(),
            'tenants' => Tenant::count(),
            'subscriptions' => Subscription::where('status', 'active')->count(),
            'revenue' => $revenue,
        ];
    }

    #[Computed]
    public function revenueChart()
    {
        // Last 12 months revenue
        $data = [];
        $categories = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M');
            $categories[] = $monthName;

            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $monthlyRevenue = Payment::where('status', 'success')
                ->whereBetween('created_at', [$start, $end])
                ->get()
                ->reduce(function ($carry, $payment) {
                    $amount = $payment->amount;
                    if ($payment->currency === 'NGN') {
                        $amount = $amount / 100;
                    }
                    return $carry + $amount;
                }, 0);

            $data[] = $monthlyRevenue;
        }

        return [
            'categories' => $categories,
            'data' => $data
        ];
    }

    #[Computed]
    public function users()
    {
        return User::whereNotGhost()->latest()->take(5)->get();
    }

    #[Computed]
    public function tenants()
    {
        return Tenant::latest()->with('domains')->take(5)->get();
    }

    #[Computed]
    public function activities()
    {
        return Activity::latest()->take(5)->get();
    }

    #[Computed]
    public function transactions()
    {
        return Payment::latest()->take(5)->get();
    }
};
?>

<div>
    <x-layouts::content :heading="'Bonjour, ' . auth()->user()->name . '!'"
        subheading="Voici ce qui se passe sur votre espace aujourd'hui.">

        <x-slot name="actions">
            <div class="flex items-center gap-1 bg-white p-1 rounded-lg">
                <button class="px-3 py-1.5 text-xs font-semibold bg-zinc-100 rounded text-zinc-900">30
                    Jours</button>
                <button
                    class="px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50 rounded transition-colors">90
                    Jours</button>
                <button
                    class="px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50 rounded transition-colors">12
                    Mois</button>
            </div>
        </x-slot>

        <!-- Metric Cards with Soft UI -->
        <x-flux::card>
            <x-flux::card.header icon="chart-bar" title="Aperçu des performances" />

            <x-flux::card.body>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total Revenue -->
                    <x-flux::card bg="bg-indigo-100"
                        class="p-6 border-0 relative overflow-hidden h-full group hover:shadow-[0_8px_30px_-4px_rgba(99,102,241,0.1)] transition-all duration-300 rounded-[20px]">
                        <div class="flex flex-col h-full relative z-10">
                            <div class="flex justify-between items-center">
                                <div class="flex flex-col">
                                    <span class="text-zinc-500 font-medium text-sm">Revenu Total</span>
                                    <div class="text-3xl font-bold text-zinc-900 tracking-tight">
                                        ${{ number_format($this->stats['revenue']) }}</div>
                                </div>
                                <div
                                    class="bg-indigo-400/20 p-2.5 rounded backdrop-blur-sm group-hover:scale-105 transition-transform">
                                    <flux:icon name="currency-dollar" variant="solid" class="w-5 h-5 text-indigo-500" />
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-zinc-400">vs le mois dernier</span>
                                    <span
                                        class="text-xs font-bold text-emerald-600 bg-emerald-100/50 px-2 py-0.5 rounded text-center">+12.5%</span>
                                </div>
                            </div>
                        </div>
                        <!-- Pattern -->
                        <div
                            class="absolute inset-0 opacity-[0.03] pattern-globe pointer-events-none mix-blend-multiply">
                        </div>
                        <img src="{{ asset('img/widget-bg-abstract.png') }}"
                            class="absolute inset-0 w-64 pointer-events-none group-hover:rotate-12 transition-transform duration-700"
                            alt="" />
                    </x-flux::card>

                    <!-- Active Tenants -->
                    <x-flux::card bg="bg-cyan-50"
                        class="p-6 border-0 relative overflow-hidden h-full group hover:shadow-[0_8px_30px_-4px_rgba(6,182,212,0.1)] transition-all duration-300 rounded-[20px]">
                        <div class="flex flex-col h-full relative z-10">
                            <div class="flex justify-between items-center">
                                <div class="flex flex-col">
                                    <span class="text-zinc-500 font-medium text-sm">Clients Actifs</span>
                                    <div class="text-3xl font-bold text-zinc-900 tracking-tight">
                                        {{ $this->stats['tenants'] }}
                                    </div>
                                </div>
                                <div
                                    class="bg-cyan-400/20 p-2.5 rounded backdrop-blur-sm group-hover:scale-105 transition-transform">
                                    <flux:icon name="building-storefront" variant="solid"
                                        class="w-5 h-5 text-cyan-500" />
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-zinc-400">vs le mois dernier</span>
                                    <span
                                        class="text-xs font-bold text-emerald-600 bg-emerald-100/50 px-2 py-0.5 rounded text-center">+5.2%</span>
                                </div>
                            </div>
                        </div>
                        <!-- Pattern -->
                        <img src="{{ asset('img/widget-bg-abstract.png') }}"
                            class="absolute inset-0 w-64 pointer-events-none group-hover:rotate-12 transition-transform duration-700"
                            alt="" />
                    </x-flux::card>

                    <!-- Active Subscriptions -->
                    <x-flux::card bg="bg-emerald-50"
                        class="p-6 border-0 relative overflow-hidden h-full group hover:shadow-[0_8px_30px_-4px_rgba(16,185,129,0.1)] transition-all duration-300 rounded-[20px]">
                        <div class="flex flex-col h-full relative z-10">
                            <div class="flex justify-between items-center">
                                <div class="flex flex-col">
                                    <span class="text-zinc-500 font-medium text-sm">Abonnements Actifs</span>
                                    <div class="text-3xl font-bold text-zinc-900 tracking-tight">
                                        {{ $this->stats['subscriptions'] }}
                                    </div>
                                </div>
                                <div
                                    class="bg-emerald-400/20 p-2.5 rounded backdrop-blur-sm group-hover:scale-105 transition-transform">
                                    <flux:icon name="credit-card" variant="solid" class="w-5 h-5 text-emerald-500" />
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-zinc-400">vs le mois dernier</span>
                                    <span
                                        class="text-xs font-bold text-zinc-500 bg-zinc-100 px-2 py-0.5 rounded text-center">0%</span>
                                </div>
                            </div>
                        </div>
                        <!-- Pattern -->
                        <img src="{{ asset('img/widget-bg-abstract.png') }}"
                            class="absolute inset-0 w-64 pointer-events-none group-hover:rotate-12 transition-transform duration-700"
                            alt="" />
                    </x-flux::card>

                    <!-- Total Users -->
                    <x-flux::card bg="bg-orange-50"
                        class="p-6 border-0 relative overflow-hidden h-full group hover:shadow-[0_8px_30px_-4px_rgba(249,115,22,0.1)] transition-all duration-300 rounded-[20px]">
                        <div class="flex flex-col h-full relative z-10">
                            <div class="flex justify-between items-center">
                                <div class="flex flex-col">
                                    <span class="text-zinc-500 font-medium text-sm">Utilisateurs Totaux</span>
                                    <div class="text-3xl font-bold text-zinc-900 tracking-tight">
                                        {{ $this->stats['users'] }}
                                    </div>
                                </div>
                                <div
                                    class="bg-orange-400/20 p-2.5 rounded backdrop-blur-sm group-hover:scale-105 transition-transform">
                                    <flux:icon name="users" variant="solid" class="w-5 h-5 text-orange-500" />
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-zinc-400">vs le mois dernier</span>
                                    <span
                                        class="text-xs font-bold text-emerald-600 bg-emerald-100/50 px-2 py-0.5 rounded text-center">+24%</span>
                                </div>
                            </div>
                        </div>
                        <!-- Pattern -->
                        <img src="{{ asset('img/widget-bg-abstract.png') }}"
                            class="absolute inset-0 w-64 pointer-events-none group-hover:rotate-12 transition-transform duration-700"
                            alt="" />
                    </x-flux::card>
                </div>
            </x-flux::card.body>

        </x-flux::card>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Revenue Chart -->
            <x-flux::card class="lg:col-span-2">

                <x-flux::card.header icon="arrow-trending-up" title="Croissance des Revenus"
                    subtitle="Aperçu mensuel de vos revenus" />

                <x-flux::card.body>
                    <div class="h-80 w-full" x-data="{
                    init() {
                                const chartData = @js($this->revenueChart);
                                const options = {
                                series: [{
                                    name: 'Revenue',
                                    data: chartData.data
                                }],
                                chart: { 
                                    type: 'area', 
                                    height: 320, 
                                    fontFamily: 'Instrument Sans, sans-serif',
                                    toolbar: { show: false }, 
                                    zoom: { enabled: false } 
                                },
                                dataLabels: { enabled: false },
                                stroke: { curve: 'smooth', width: 3, colors: ['#6366f1'] },
                                xaxis: { 
                                    categories: chartData.categories,
                                    axisBorder: { show: false },
                                    axisTicks: { show: false },
                                    labels: { 
                                        style: { colors: '#94a3b8', fontSize: '12px', fontWeight: 500 } 
                                    },
                                    tooltip: { enabled: false }
                                },
                                yaxis: { 
                                    labels: { 
                                        formatter: (val) => '$' + (val).toFixed(0),
                                        style: { colors: '#94a3b8', fontSize: '12px', fontWeight: 500 }
                                    } 
                                },
                                grid: {
                                    borderColor: '#f1f5f9',
                                    strokeDashArray: 4,
                                    padding: { top: 0, right: 0, bottom: 0, left: 10 }
                                },
                                fill: {
                                    type: 'gradient',
                                    gradient: {
                                        shadeIntensity: 1,
                                        opacityFrom: 0.4,
                                        opacityTo: 0.05,
                                        stops: [0, 95, 100],
                                        colorStops: [
                                            { offset: 0, color: '#818cf8', opacity: 0.3 },
                                            { offset: 100, color: '#818cf8', opacity: 0 }
                                        ]
                                    }
                                },
                                markers: { size: 0, hover: { size: 6, colors: ['#6366f1'], strokeColors: '#fff', strokeWidth: 3 } },
                                tooltip: { 
                                    theme: 'light',
                                    y: { formatter: (val) => '$' + val },
                                    style: { fontSize: '12px' },
                                    marker: { show: true },
                                    x: { show: false }
                                }
                            };
                            const chart = new ApexCharts(this.$el, options);
                            chart.render();
                        }
                    }">
                    </div>
                </x-flux::card.body>
            </x-flux::card>

            <!-- Activity Feed -->
            <x-flux::card>

                <x-flux::card.header icon="bolt" title="Activité en Direct"
                    subtitle="Activités récentes sur votre plateforme" />

                <x-flux::card.body>
                    <div class="space-y-0 flex-1 overflow-y-auto">
                        @forelse($this->activities as $activity)
                            <div class="relative pl-6 pb-6 last:pb-0 group">
                                <!-- Timeline Line -->
                                <div class="absolute left-[9px] top-2 bottom-0 w-0.5 bg-zinc-100 group-last:hidden"></div>

                                <!-- Timeline Dot -->
                                <div
                                    class="absolute left-0 top-1.5 w-5 h-5 rounded-full border-4 border-white bg-indigo-100 flex items-center justify-center shadow-sm">
                                    <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                                </div>

                                <div class="flex flex-col">
                                    <p
                                        class="text-sm font-medium text-zinc-800 group-hover:text-indigo-600 transition-colors">
                                        {{ $activity->description }}
                                    </p>
                                    <p class="text-[11px] text-zinc-400 mt-0.5 font-medium">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center h-40 text-center">
                                <div class="bg-zinc-50 p-3 rounded-full mb-3">
                                    <flux:icon name="inbox" class="text-zinc-300 w-6 h-6" />
                                </div>
                                <p class="text-sm text-zinc-500">Aucune activité récente.</p>
                            </div>
                        @endforelse
                    </div>
                </x-flux::card.body>
            </x-flux::card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Tenants -->
            <x-flux::card class="overflow-hidden">

                <x-flux::card.header icon="building-office" title="Clients Récents"
                    subtitle="Clients ayant récemment rejoint la plateforme">
                    <x-slot:actions>
                        <flux:link href="{{ route('central.tenants.index') }}" variant="ghost" color="primary"
                            class="text-xs">Voir Tout</flux:link>
                    </x-slot:actions>
                </x-flux::card.header>

                <x-flux::table>
                    <x-flux::table.columns>
                        <x-flux::table.column>ID Client</x-flux::table.column>
                        <x-flux::table.column>Domaine</x-flux::table.column>
                        <x-flux::table.column>Créé le</x-flux::table.column>
                    </x-flux::table.columns>

                    <x-flux::table.rows>
                        @foreach ($this->tenants as $tenant)
                            <x-flux::table.row :key="$tenant->id">
                                <x-flux::table.cell class="font-mono text-xs text-zinc-600">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-cyan-400"></div>
                                        {{ substr($tenant->id, 0, 8) }}...
                                    </div>
                                </x-flux::table.cell>
                                <x-flux::table.cell variant="strong">
                                    {{ $tenant->domains->first()->domain ?? 'N/A' }}
                                </x-flux::table.cell>
                                <x-flux::table.cell>
                                    {{ $tenant->created_at->format('M d, Y') }}
                                </x-flux::table.cell>
                            </x-flux::table.row>
                        @endforeach
                    </x-flux::table.rows>
                </x-flux::table>
            </x-flux::card>

            <!-- Recent Users -->
            <x-flux::card class="overflow-hidden">

                <x-flux::card.header icon="users" title="Utilisateurs Récents"
                    subtitle="Utilisateurs récents sur votre plateforme">
                    <x-slot:actions>
                        <flux:link href="{{ route('central.users.index') }}" variant="ghost" color="primary"
                            class="text-xs">Voir Tout</flux:link>
                    </x-slot:actions>
                </x-flux::card.header>
                <x-flux::table>
                    <x-flux::table.columns>
                        <x-flux::table.column>Utilisateur</x-flux::table.column>
                        <x-flux::table.column>Email</x-flux::table.column>
                        <x-flux::table.column>Statut</x-flux::table.column>
                    </x-flux::table.columns>

                    <x-flux::table.rows>
                        @foreach ($this->users as $user)
                            <x-flux::table.row :key="$user->id">
                                <x-flux::table.cell>
                                    <div class="flex items-center gap-3">
                                        <x-flux::avatar src="https://i.pravatar.cc/150?u={{ $user->email }}" size="xs"
                                            class="ring-2 ring-white shadow-sm" />
                                        <span class="font-medium text-zinc-900">{{ $user->name }}</span>
                                    </div>
                                </x-flux::table.cell>
                                <x-flux::table.cell>{{ $user->email }}</x-flux::table.cell>
                                <x-flux::table.cell>
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700">Actif</span>
                                </x-flux::table.cell>
                            </x-flux::table.row>
                        @endforeach
                    </x-flux::table.rows>
                </x-flux::table>
            </x-flux::card>
        </div>

        <!-- Transaction History -->
        <x-flux::card class="overflow-hidden mb-12">

            <x-flux::card.header icon="banknotes" title="Historique des Transactions"
                subtitle="Paiements récents et facturation récurrente">

                <x-slot:actions>
                    <button class="p-2 bg-zinc-100 hover:bg-zinc-200 rounded text-zinc-400 transition-colors">
                        <flux:icon name="ellipsis-vertical" class="w-5 h-5" />
                    </button>
                </x-slot:actions>
            </x-flux::card.header>



            @if($this->transactions->count() > 0)
                <x-flux::table search linesPerPage>
                    <x-slot:selectable>
                        <flux:select wire:model.live="status" size="sm" class="w-full md:w-40">
                            <flux:select.option value="all">Tous statut</flux:select.option>
                            @foreach(\App\Enums\PropertyStatus::cases() as $status)
                                <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </x-slot:selectable>

                    <x-flux::table.columns>
                        <x-flux::table.column>Référence</x-flux::table.column>
                        <x-flux::table.column>Client</x-flux::table.column>
                        <x-flux::table.column>Montant</x-flux::table.column>
                        <x-flux::table.column>Statut</x-flux::table.column>
                        <x-flux::table.column>Date</x-flux::table.column>
                    </x-flux::table.columns>

                    <x-flux::table.rows>
                        @foreach ($this->transactions as $payment)
                            <x-flux::table.row :key="$payment->id">
                                <x-flux::table.cell
                                    class="font-mono text-xs text-zinc-500 group-hover:text-indigo-600 transition-colors">
                                    {{ $payment->reference }}
                                </x-flux::table.cell>
                                <x-flux::table.cell variant="strong">
                                    {{ $payment->email }}
                                </x-flux::table.cell>
                                <x-flux::table.cell variant="strong">
                                    {{ $payment->currency }}
                                    {{ number_format($payment->currency === 'NGN' ? $payment->amount / 100 : $payment->amount, 2) }}
                                </x-flux::table.cell>
                                <x-flux::table.cell>
                                    <span @class([
                                        'inline-flex items-center justify-between px-2.5 py-1 rounded-full text-xs font-medium capitalize',
                                        'bg-emerald-50 text-emerald-700 border border-emerald-100' => $payment->status ===
                                            'success',
                                        'bg-red-50 text-red-700 border border-red-100' => $payment->status === 'failed',
                                        'bg-zinc-100 text-zinc-700 border border-zinc-200' => !in_array(
                                            $payment->status,
                                            ['success', 'failed']
                                        ),
                                    ])>
                                        <div @class([
                                            'w-1.5 h-1.5 rounded-full',
                                            'bg-emerald-500' => $payment->status === 'success',
                                            'bg-red-500' => $payment->status === 'failed',
                                            'bg-zinc-500' => !in_array($payment->status, ['success', 'failed']),
                                        ])></div>
                                        {{ $payment->status }}
                                    </span>
                                </x-flux::table.cell>
                                <x-flux::table.cell>
                                    {{ $payment->created_at->format('M d, Y h:i A') }}
                                </x-flux::table.cell>
                            </x-flux::table.row>
                        @endforeach
                    </x-flux::table.rows>
                </x-flux::table>
            @else
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-zinc-100 mb-4">
                        <flux:icon name="banknotes" class="w-6 h-6 text-zinc-400" />
                    </div>
                    <h3 class="text-sm font-medium text-zinc-900">Aucune transaction pour le moment</h3>
                    <p class="text-xs text-zinc-500 mt-1 max-w-xs mx-auto">Les paiements des clients apparaîtront ici une
                        fois qu'ils auront souscrit à un plan.</p>
                </div>
            @endif
        </x-flux::card>

    </x-layouts::content>
</div>