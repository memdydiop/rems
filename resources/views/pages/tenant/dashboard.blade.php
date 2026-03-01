<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Traits\WithDataTable;
use Livewire\WithPagination;
use App\Enums\PropertyStatus;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\RentPayment;
use App\Enums\PaymentStatus;
use App\Models\Expense;

new
    #[Layout('layouts.app', ['title' => 'Tenant'])]
    class extends Component {
    use WithPagination, WithDataTable;

    public $status = 'all';

    #[Computed]
    public function recentLeases()
    {
        return Lease::with(['renter', 'unit.property'])
            ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
            ->latest('start_date')
            ->paginate(5);
    }

    #[Computed]
    public function stats()
    {
        $totalUnits = Unit::count();
        $occupiedUnits = Unit::where('status', 'occupied')->count();
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

        // Calculate total revenue from completed payments
        $revenue = RentPayment::where('status', PaymentStatus::Completed)->sum('amount');
        $expenses = Expense::sum('amount');

        return [
            'properties' => \App\Models\Property::count(),
            'units' => $totalUnits,
            'occupied' => $occupiedUnits,
            'occupancy' => $occupancyRate,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_income' => $revenue - $expenses,
        ];
    }

    #[Computed]
    public function financeChart()
    {
        // Get monthly revenue
        $payments = \App\Models\RentPayment::where('status', \App\Enums\PaymentStatus::Completed)
            ->whereYear('paid_at', date('Y'))
            ->selectRaw('extract(month from paid_at) as month, sum(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Get monthly expenses
        $expenses = \App\Models\Expense::whereYear('date', date('Y'))
            ->selectRaw('extract(month from date) as month, sum(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $revenueData = [];
        $expenseData = [];
        $categories = [];

        // Fill in all 12 months (localized)
        for ($i = 1; $i <= 12; $i++) {
            $date = \Carbon\Carbon::create(date('Y'), $i, 1);
            $categories[] = ucfirst($date->translatedFormat('M'));
            $revenueData[] = $payments[$i] ?? 0;
            $expenseData[] = $expenses[$i] ?? 0;
        }

        return [
            'categories' => $categories,
            'revenue' => $revenueData,
            'expenses' => $expenseData,
        ];
    }

    #[Computed]
    public function recentActivity()
    {
        return \Spatie\Activitylog\Models\Activity::latest()
            ->take(5)
            ->get()
            ->map(function ($activity) {
                return [
                    'description' => $activity->description,
                    'causer' => $activity->causer ? $activity->causer->name : 'Système',
                    'subject_type' => class_basename($activity->subject_type),
                    'created_at' => $activity->created_at->diffForHumans(),
                    'icon' => match (class_basename($activity->subject_type)) {
                        'User', 'Renter' => 'user',
                        'Property', 'Unit' => 'home',
                        'Lease' => 'document-text',
                        'RentPayment' => 'banknotes',
                        'MaintenanceRequest' => 'wrench-screwdriver',
                        default => 'bell',
                },
                    'color' => match (class_basename($activity->subject_type)) {
                        'User', 'Renter' => 'indigo',
                        'Property', 'Unit' => 'cyan',
                        'Lease' => 'amber',
                        'RentPayment' => 'emerald',
                        'MaintenanceRequest' => 'rose',
                        default => 'zinc',
                },
                ];
            });
    }

    #[Computed]
    public function upcomingExpirations()
    {
        return \App\Models\Lease::with(['renter', 'unit.property'])
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addDays(30))
            ->orderBy('end_date')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function overdueLeases()
    {
        return \App\Models\Lease::with(['renter', 'unit.property'])
            ->overdue()
            ->get()
            ->map(function ($lease) {
                $lease->days_overdue = now()->diffInDays(now()->startOfMonth()->addDays(5));
                return $lease;
            });
    }

    #[Computed]
    public function rentCollectionStats()
    {
        $activeLeases = \App\Models\Lease::where('status', 'active')->count();
        $thisMonth = now()->format('Y-m');

        // Count leases that have a payment this month
        $paidCount = \App\Models\Lease::where('status', 'active')
            ->whereHas('payments', function ($q) use ($thisMonth) {
                $q->whereRaw("to_char(paid_at, 'YYYY-MM') = ?", [$thisMonth]);
            })
            ->count();

        $unpaidCount = $activeLeases - $paidCount;
        $collectionRate = $activeLeases > 0 ? round(($paidCount / $activeLeases) * 100) : 0;

        return [
            'total' => $activeLeases,
            'paid' => $paidCount,
            'unpaid' => $unpaidCount,
            'rate' => $collectionRate,
        ];
    }

    #[Computed]
    public function maintenanceStats()
    {
        // Pluck returns Enum objects as keys if casted, so we need to map them to values
        $requests = \App\Models\MaintenanceRequest::selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->get()
            ->mapWithKeys(fn($item) => [$item->priority->value => $item->count])
            ->toArray();

        return [
            'high' => $requests['high'] ?? 0,
            'medium' => $requests['medium'] ?? 0,
            'low' => $requests['low'] ?? 0,
            'total' => array_sum($requests),
        ];
    }

    #[Computed]
    public function expensesByCategory()
    {
        $expenses = Expense::selectRaw('category, sum(amount) as total')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $expenses->pluck('category')->map(fn($c) => ucfirst($c))->toArray(),
            'data' => $expenses->pluck('total')->map(fn($v) => (float) $v)->toArray(),
        ];
    }

    #[Computed]
    public function occupancyTrend()
    {
        $data = [];
        $categories = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $categories[] = ucfirst($date->translatedFormat('M'));

            $totalUnits = Unit::where('created_at', '<=', $date->endOfMonth())->count();
            $occupiedUnits = Lease::where('status', 'active')
                ->where('start_date', '<=', $date->endOfMonth())
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $date->startOfMonth());
                })
                ->count();

            $data[] = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;
        }

        return [
            'categories' => $categories,
            'data' => $data,
        ];
    }
};
?>

<div>
    <x-layouts::content :heading="'Bonjour, ' . auth()->user()->name . '!'"
        subheading="Voici ce qui se passe dans votre espace de travail aujourd'hui.">

        <x-slot:actions>
            @php
                $subscription = tenancy()->tenant->subscription;
            @endphp

            <div class="flex items-center gap-3">
                <livewire:components.onboarding-flash step="dashboard_add_property" title="🚀 Ajoutez votre Propriété"
                    description="C'est ici que tout commence ! Ajoutez votre première propriété pour y associer des unités et des locataires."
                    align="bottom">
                    <flux:button href="{{ route('tenant.properties.index') }}" icon="home" variant="filled" size="sm">
                        Propriété
                    </flux:button>
                </livewire:components.onboarding-flash>
                <flux:button href="{{ route('tenant.leases.index') }}" icon="document-plus" variant="filled" size="sm">
                    Bail</flux:button>
                <flux:button href="{{ route('tenant.maintenance.index') }}" icon="wrench" variant="filled" size="sm">
                    Ticket</flux:button>
                <flux:button href="{{ route('tenant.expenses.index') }}" icon="credit-card" variant="filled" size="sm">
                    Dépense</flux:button>
            </div>
        </x-slot:actions>

        <!-- Metric Cards with Soft UI -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Financial Overview -->
            <x-dashboard-card title="Résultat Net" :value="number_format($this->stats['net_income'], 0, ',', ' ') . ' FCFA'" icon="banknotes" color="indigo">
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-zinc-400 block">Revenus</span>
                        <span
                            class="font-bold text-emerald-600">+{{ number_format($this->stats['revenue'], 0, '', ' ') }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-400 block">Dépenses</span>
                        <span
                            class="font-bold text-rose-600">-{{ number_format($this->stats['expenses'], 0, '', ' ') }}</span>
                    </div>
                </div>
            </x-dashboard-card>

            <!-- Total Properties -->
            <x-dashboard-card title="Total Propriétés" :value="$this->stats['properties']" icon="home" color="cyan"
                :trend="['value' => '+5.2%', 'label' => 'vs le mois dernier', 'positive' => true]" />

            <!-- Units -->
            <x-dashboard-card title="Unités" :value="$this->stats['units']" icon="key" color="emerald">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-zinc-400">Disponibles:
                        {{ $this->stats['units'] - $this->stats['occupied'] }}</span>
                    <span
                        class="text-xs font-bold text-emerald-600 bg-emerald-100/50 px-2 py-0.5 rounded text-center">{{ $this->stats['occupied'] }}
                        Occ.</span>
                </div>
            </x-dashboard-card>

            <!-- Occupancy Rate -->
            <x-dashboard-card title="Taux d'Occupation" :value="$this->stats['occupancy'] . '%'" icon="chart-pie"
                color="orange" :trend="['value' => '+2.4%', 'label' => 'vs le mois dernier', 'positive' => true]" />
        </div>

        <!-- Main Chart and Activity Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Revenue Chart -->
            <x-flux::card class="lg:col-span-2">
                <x-flux::card.header icon="presentation-chart-line" title="Aperçu Financier"
                    subtitle="Comparaison Revenus vs Dépenses" />

                <x-flux::card.body>
                    <div class="h-80 w-full" x-data="{
                        init() {
                             const chartData = @js($this->financeChart);
                             const options = {
                                series: [{
                                    name: 'Revenus',
                                    data: chartData.revenue
                                }, {
                                    name: 'Dépenses',
                                    data: chartData.expenses
                                }],
                                chart: { 
                                    type: 'area', 
                                    height: 320, 
                                    fontFamily: 'Instrument Sans, sans-serif',
                                    toolbar: { show: false }, 
                                    zoom: { enabled: false } 
                                },
                                dataLabels: { enabled: false },
                                stroke: { curve: 'smooth', width: 3, colors: ['#6366f1', '#f43f5e'] },
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
                                        formatter: (val) => new Intl.NumberFormat('fr-FR').format(val),
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
                                            [
                                                { offset: 0, color: '#818cf8', opacity: 0.3 },
                                                { offset: 100, color: '#818cf8', opacity: 0 }
                                            ],
                                            [
                                                { offset: 0, color: '#f43f5e', opacity: 0.3 },
                                                { offset: 100, color: '#f43f5e', opacity: 0 }
                                            ]
                                        ]
                                    }
                                },
                                markers: { size: 0, hover: { size: 6, colors: ['#6366f1', '#f43f5e'], strokeColors: '#fff', strokeWidth: 3 } },
                                tooltip: { 
                                    theme: 'light', 
                                    y: { formatter: (val) => new Intl.NumberFormat('fr-FR').format(val) + ' FCFA' },
                                    style: { fontSize: '12px' },
                                    marker: { show: true },
                                    x: { show: false }
                                },
                                colors: ['#6366f1', '#f43f5e']
                            };
                            const chart = new ApexCharts(this.$el, options);
                            chart.render();
                        }
                    }"></div>
                </x-flux::card.body>
            </x-flux::card>

            <!-- Live Activity Feed -->
            <x-flux::card>
                <x-flux::card.header icon="bolt" title="Activité en Direct"
                    subtitle="Activités récentes sur votre espace" />

                <x-flux::card.body>
                    <div class="space-y-0 flex-1 overflow-y-auto">
                        @forelse($this->recentActivity as $activity)
                            <div class="relative pl-6 pb-6 last:pb-0 group">
                                <!-- Timeline Line -->
                                <div class="absolute left-2.25 top-2 bottom-0 w-0.5 bg-zinc-100 group-last:hidden"></div>

                                <!-- Timeline Dot -->
                                @php
                                    $dotColor = match ($activity['color']) {
                                        'indigo' => 'bg-indigo-500',
                                        'cyan' => 'bg-cyan-500',
                                        'emerald' => 'bg-emerald-500',
                                        'amber' => 'bg-amber-500',
                                        'rose' => 'bg-rose-500',
                                        default => 'bg-zinc-500'
                                    };
                                    $ringColor = match ($activity['color']) {
                                        'indigo' => 'bg-indigo-100',
                                        'cyan' => 'bg-cyan-100',
                                        'emerald' => 'bg-emerald-100',
                                        'amber' => 'bg-amber-100',
                                        'rose' => 'bg-rose-100',
                                        default => 'bg-zinc-100'
                                    };
                                @endphp
                                <div
                                    class="absolute left-0 top-1.5 w-5 h-5 rounded-full border-4 border-white {{ $ringColor }} flex items-center justify-center shadow-sm">
                                    <div class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></div>
                                </div>

                                <div class="flex flex-col">
                                    <p
                                        class="text-sm font-medium text-zinc-800 group-hover:text-indigo-600 transition-colors">
                                        {{ $activity['description'] }}
                                    </p>
                                    <p class="text-[11px] text-zinc-400 mt-0.5 font-medium">
                                        {{ $activity['created_at'] }} • par {{ $activity['causer'] }}
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

        <!-- Dashboard Widgets Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Rent Collection Status -->
            <x-flux::card>
                <x-flux::card.header icon="banknotes" title="Collecte des Loyers" subtitle="ce mois" />
                <div class="p-4 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-3xl font-bold text-zinc-900">{{ $this->rentCollectionStats['rate'] }}%</span>
                        <span class="text-sm text-zinc-500">ce mois</span>
                    </div>
                    <div class="w-full bg-zinc-100 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full"
                            style="width: {{ $this->rentCollectionStats['rate'] }}%"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div class="text-center p-2 bg-emerald-50 rounded-lg">
                            <p class="text-lg font-semibold text-emerald-600">{{ $this->rentCollectionStats['paid'] }}
                            </p>
                            <p class="text-xs text-emerald-600">Payés</p>
                        </div>
                        <div class="text-center p-2 bg-rose-50 rounded-lg">
                            <p class="text-lg font-semibold text-rose-600">{{ $this->rentCollectionStats['unpaid'] }}
                            </p>
                            <p class="text-xs text-rose-600">En attente</p>
                        </div>
                    </div>
                </div>
            </x-flux::card>

            <!-- Overdue Payments (DoorLoop Style) -->
            <x-flux::card>
                <x-flux::card.header icon="exclamation-circle" title="Retards de Paiement" subtitle="ce mois" />

                <x-flux::card.body>

                    @forelse($this->overdueLeases as $lease)
                        <div
                            class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-zinc-100' : '' }}">
                            <div class="flex items-center gap-2">
                                <flux:avatar src="https://i.pravatar.cc/150?u={{ $lease->renter->email }}" size="xs" />
                                <div>
                                    <p class="text-sm font-medium text-zinc-900">{{ $lease->renter->first_name }}
                                        {{ $lease->renter->last_name }}
                                    </p>
                                    <p class="text-xs text-zinc-500">{{ $lease->unit->property->name }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span
                                    class="block text-xs font-bold text-rose-600">{{ number_format($lease->rent_amount, 0, ',', ' ') }}
                                    FCFA</span>
                                <span class="text-[10px] text-rose-500 bg-rose-50 px-1.5 py-0.5 rounded">
                                    +{{ $lease->days_overdue }} jours
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-zinc-400">
                            <flux:icon name="check-circle" class="w-8 h-8 mx-auto mb-2 text-emerald-400" />
                            <p class="text-sm font-medium text-zinc-600">Tous les loyers sont à jour !</p>
                            <p class="text-xs text-zinc-400">Aucun retard détecté ce mois-ci.</p>
                        </div>
                    @endforelse

                </x-flux::card.body>
            </x-flux::card>

            <!-- Maintenance by Priority -->
            <x-flux::card>
                <x-flux::card.header>
                    <div class="flex items-center gap-2">
                        <flux:icon name="wrench-screwdriver" class="text-blue-500 w-5 h-5" />
                        <x-flux::card.title>Demandes de Maintenance</x-flux::card.title>
                    </div>
                </x-flux::card.header>
                <div class="p-4 space-y-3">
                    <div class="flex items-center justify-between p-3 bg-rose-50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-rose-500 rounded-full"></span>
                            <span class="text-sm font-medium text-rose-700">Haute priorité</span>
                        </div>
                        <span class="text-lg font-bold text-rose-700">{{ $this->maintenanceStats['high'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-amber-50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                            <span class="text-sm font-medium text-amber-700">Moyenne priorité</span>
                        </div>
                        <span class="text-lg font-bold text-amber-700">{{ $this->maintenanceStats['medium'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                            <span class="text-sm font-medium text-emerald-700">Basse priorité</span>
                        </div>
                        <span class="text-lg font-bold text-emerald-700">{{ $this->maintenanceStats['low'] }}</span>
                    </div>
                    <div class="pt-2 text-center">
                        <span class="text-sm text-zinc-500">Total:
                            <strong>{{ $this->maintenanceStats['total'] }}</strong> demandes</span>
                    </div>
                </div>
            </x-flux::card>
        </div>

        <!-- Analytics Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Expenses by Category (Donut) -->
            <x-flux::card>
                <x-flux::card.header icon="chart-pie" title="Dépenses par Catégorie"
                    subtitle="Répartition de l'année en cours" />

                <x-flux::card.body>
                    @if(count($this->expensesByCategory['data']) > 0)
                        <div class="h-72 w-full" x-data="{
                                init() {
                                    const data = @js($this->expensesByCategory);
                                    const colors = ['#6366f1', '#f43f5e', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16'];
                                    const chart = new ApexCharts(this.$el, {
                                        series: data.data,
                                        chart: { type: 'donut', height: 280, fontFamily: 'Instrument Sans, sans-serif' },
                                        labels: data.labels,
                                        colors: colors.slice(0, data.labels.length),
                                        plotOptions: {
                                            pie: {
                                                donut: {
                                                    size: '65%',
                                                    labels: {
                                                        show: true,
                                                        total: {
                                                            show: true,
                                                            label: 'Total',
                                                            formatter: (w) => new Intl.NumberFormat('fr-FR').format(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) + ' XOF'
                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        legend: { position: 'bottom', fontSize: '12px', fontWeight: 500 },
                                        dataLabels: { enabled: false },
                                        stroke: { width: 2, colors: ['#fff'] },
                                        tooltip: {
                                            y: { formatter: (val) => new Intl.NumberFormat('fr-FR').format(val) + ' XOF' }
                                        }
                                    });
                                    chart.render();
                                }
                            }"></div>
                    @else
                        <div class="flex flex-col items-center justify-center h-40 text-center">
                            <flux:icon name="chart-pie" class="text-zinc-200 w-10 h-10 mb-2" />
                            <p class="text-sm text-zinc-500">Aucune dépense enregistrée.</p>
                        </div>
                    @endif
                </x-flux::card.body>
            </x-flux::card>

            <!-- Occupancy Trend (Line) -->
            <x-flux::card>
                <x-flux::card.header icon="chart-bar" title="Taux d'Occupation" subtitle="Évolution sur 12 mois" />

                <x-flux::card.body>
                    <div class="h-72 w-full" x-data="{
                        init() {
                            const data = @js($this->occupancyTrend);
                            const chart = new ApexCharts(this.$el, {
                                series: [{ name: 'Occupation (%)', data: data.data }],
                                chart: {
                                    type: 'area',
                                    height: 280,
                                    fontFamily: 'Instrument Sans, sans-serif',
                                    toolbar: { show: false },
                                    zoom: { enabled: false }
                                },
                                dataLabels: { enabled: false },
                                stroke: { curve: 'smooth', width: 3, colors: ['#10b981'] },
                                xaxis: {
                                    categories: data.categories,
                                    axisBorder: { show: false },
                                    axisTicks: { show: false },
                                    labels: { style: { colors: '#94a3b8', fontSize: '12px', fontWeight: 500 } }
                                },
                                yaxis: {
                                    min: 0, max: 100,
                                    labels: {
                                        formatter: (val) => val + '%',
                                        style: { colors: '#94a3b8', fontSize: '12px', fontWeight: 500 }
                                    }
                                },
                                grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
                                fill: {
                                    type: 'gradient',
                                    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 95, 100] }
                                },
                                markers: { size: 0, hover: { size: 6, colors: ['#10b981'], strokeColors: '#fff', strokeWidth: 3 } },
                                tooltip: {
                                    theme: 'light',
                                    y: { formatter: (val) => val + '%' },
                                    marker: { show: true }
                                },
                                colors: ['#10b981']
                            });
                            chart.render();
                        }
                    }"></div>
                </x-flux::card.body>
            </x-flux::card>
        </div>

        <!-- Recent Leases -->
        <x-flux::card class="overflow-hidden">
            <x-flux::card.header icon="document-text" title="Baux Récents"
                subtitle="Derniers contrats de location générés">
                <x-slot:cardActions>
                    <flux:link href="{{ route('tenant.leases.index') }}" variant="ghost" color="primary"
                        class="text-xs">Voir Tout</flux:link>
                </x-slot:cardActions>
            </x-flux::card.header>

            <x-flux::table :paginate="$this->recentLeases" search linesPerPage>
                <x-slot:selectable>
                    <flux:select wire:model.live="status" size="sm" class="w-full md:w-40">
                        <flux:select.option value="all">Tous statut</flux:select.option>
                        @foreach(PropertyStatus::cases() as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </x-slot:selectable>

                <x-flux::table.columns>
                    <x-flux::table.column>Locataire</x-flux::table.column>
                    <x-flux::table.column>Propriété - Unité</x-flux::table.column>
                    <x-flux::table.column>Loyer</x-flux::table.column>
                    <x-flux::table.column>Statut</x-flux::table.column>
                    <x-flux::table.column>Début</x-flux::table.column>
                </x-flux::table.columns>

                <x-flux::table.rows>
                    @forelse ($this->recentLeases as $lease)
                        <x-flux::table.row :key="$lease->id">
                            <x-flux::table.cell>
                                <div class="flex items-center gap-3">
                                    <x-flux::avatar src="https://i.pravatar.cc/150?u={{ $lease->renter->email }}" size="xs"
                                        class="ring-2 ring-white shadow-sm" />
                                    <span class="font-medium text-zinc-900">{{ $lease->renter->first_name }}
                                        {{ $lease->renter->last_name }}</span>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm font-medium text-zinc-900">{{ $lease->unit->property->name }}</span>
                                    <span class="text-xs text-zinc-500">{{ $lease->unit->name }}</span>
                                </div>
                            </x-flux::table.cell>
                            <x-flux::table.cell variant="strong">
                                {{ number_format($lease->rent_amount, 0, ',', ' ') . ' FCFA' }}
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                <flux:badge size="sm" :color="$lease->status->color()" inset="top bottom">
                                    {{ $lease->status->label() }}
                                </flux:badge>
                            </x-flux::table.cell>
                            <x-flux::table.cell>
                                {{ $lease->start_date->format('d M Y') }}
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @empty
                        <x-flux::table.row>
                            <x-flux::table.cell colspan="5">
                                <div class="flex flex-col items-center justify-center p-6 text-center">
                                    <div class="bg-zinc-50 p-3 rounded-full mb-3">
                                        <flux:icon name="document-text" class="text-zinc-300 w-6 h-6" />
                                    </div>
                                    <p class="text-sm font-medium text-zinc-900">Aucun bail récent</p>
                                    <p class="text-xs text-zinc-500 mt-1">Les nouveaux contrats apparaîtront ici.</p>
                                </div>
                            </x-flux::table.cell>
                        </x-flux::table.row>
                    @endforelse
                </x-flux::table.rows>
            </x-flux::table>
        </x-flux::card>

    </x-layouts::content>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>