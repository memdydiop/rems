<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;

new
    #[Layout('layouts.app', ['title' => 'Rapports'])]
    class extends Component {

    #[Computed]
    public function reportCards()
    {
        return [
            [
                'title' => 'Rapport des Revenus',
                'description' => 'Analyse des revenus par période avec graphiques et export.',
                'icon' => 'currency-dollar',
                'color' => 'emerald',
                'href' => route('tenant.reports.revenue'),
            ],
            [
                'title' => 'Performance des Propriétés',
                'description' => 'Comparaison des performances entre vos propriétés.',
                'icon' => 'building-office-2',
                'color' => 'blue',
                'href' => route('tenant.reports.properties'),
            ],
            [
                'title' => 'Rapport d\'Occupation',
                'description' => 'Taux d\'occupation et tendances par unité.',
                'icon' => 'chart-bar',
                'color' => 'amber',
                'href' => route('tenant.reports.occupancy'),
            ],
            [
                'title' => 'Historique des Paiements',
                'description' => 'Détail de tous les paiements reçus.',
                'icon' => 'banknotes',
                'color' => 'violet',
                'href' => route('tenant.reports.payments'),
            ],
        ];
    }
};
?>

<div>
    <x-layouts::content heading="Rapports & Analytics"
        subheading="Analysez vos données et générez des rapports détaillés.">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($this->reportCards as $report)
                <a href="{{ $report['href'] }}"
                    class="group block p-6 bg-white border border-zinc-200 rounded-xl hover:shadow-lg hover:border-{{ $report['color'] }}-300 transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div
                            class="p-3 rounded-lg bg-{{ $report['color'] }}-50 text-{{ $report['color'] }}-600 group-hover:bg-{{ $report['color'] }}-100 transition-colors">
                            <flux:icon :name="$report['icon']" class="w-6 h-6" />
                        </div>
                        <div class="flex-1">
                            <h3
                                class="text-lg font-semibold text-zinc-900 group-hover:text-{{ $report['color'] }}-600 transition-colors">
                                {{ $report['title'] }}
                            </h3>
                            <p class="text-sm text-zinc-500 mt-1">
                                {{ $report['description'] }}
                            </p>
                        </div>
                        <flux:icon name="chevron-right"
                            class="w-5 h-5 text-zinc-300 group-hover:text-{{ $report['color'] }}-500 group-hover:translate-x-1 transition-all" />
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Quick Stats Summary -->
        <div class="mt-8">
            <x-flux::card>
                <x-flux::card.header title="Résumé Rapide" subtitle="Statistiques clés de votre portefeuille immobilier." />
                <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-zinc-900">
                            {{ \Illuminate\Support\Number::currency(\App\Models\Lease::where('status', 'active')->sum('rent_amount'), 'USD') }}
                        </p>
                        <p class="text-sm text-zinc-500">Revenus Mensuels</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-zinc-900">{{ \App\Models\Property::count() }}</p>
                        <p class="text-sm text-zinc-500">Propriétés</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-zinc-900">{{ \App\Models\Unit::count() }}</p>
                        <p class="text-sm text-zinc-500">Unités</p>
                    </div>
                    <div class="text-center">
                        @php
                            $total = \App\Models\Unit::count();
                            $occupied = \App\Models\Unit::where('status', 'occupied')->count();
                            $rate = $total > 0 ? round(($occupied / $total) * 100) : 0;
                        @endphp
                        <p class="text-3xl font-bold text-zinc-900">{{ $rate }}%</p>
                        <p class="text-sm text-zinc-500">Taux d'Occupation</p>
                    </div>
                </div>
            </x-flux::card>
        </div>

    </x-layouts::content>
</div>