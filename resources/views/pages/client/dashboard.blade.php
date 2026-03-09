<?php

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Client;
use App\Models\RentPayment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
    #[Layout('layouts.client', ['title' => 'Mon Espace Client'])]
    class extends Component {

    #[Computed]
    public function client(): ?Client
    {
        return Client::where('user_id', auth()->id())->first();
    }

    #[Computed]
    public function activeLease(): ?Lease
    {
        return $this->client?->leases()
            ->where('status', 'active')
            ->with(['unit.property'])
            ->first();
    }

    #[Computed]
    public function recentPayments()
    {
        if (!$this->activeLease)
            return collect();

        return $this->activeLease->payments()
            ->orderByDesc('paid_at')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function maintenanceRequests()
    {
        $unit = $this->activeLease?->unit;
        if (!$unit)
            return collect();

        return MaintenanceRequest::where('unit_id', $unit->id)
            ->orderByDesc('created_at')
            ->take(4)
            ->get();
    }

    #[Computed]
    public function hasPaidCurrentMonth()
    {
        if (!$this->activeLease) {
            return false;
        }

        $currentDate = now();
        return $this->activeLease->payments()
            ->whereYear('paid_at', $currentDate->year)
            ->whereMonth('paid_at', $currentDate->month)
            ->where('status', 'completed')
            ->exists();
    }
};
?>

<div class="min-h-screen bg-zinc-50 pb-12">
    <!-- Hero Section -->
    <div class="relative bg-zinc-900 border-b border-zinc-200 pb-32 pt-16 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div
                class="absolute -top-1/2 -right-1/4 w-full h-full bg-linear-to-bl from-indigo-500/20 to-transparent blur-3xl rounded-full">
            </div>
            <div
                class="absolute bottom-0 left-0 w-1/2 h-1/2 bg-linear-to-tr from-emerald-500/20 to-transparent blur-3xl rounded-full">
            </div>
        </div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold text-white tracking-tight">Bonjour,
                        {{ $this->client?->first_name }} 👋
                    </h1>
                    <p class="text-zinc-400 font-medium mt-2 text-lg">Bienvenue sur votre espace client personnel.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <flux:button variant="subtle"
                        class="text-white hover:bg-zinc-800 border-zinc-700 bg-zinc-800/50 backdrop-blur-sm"
                        href="{{ route('logout') }}" icon="arrow-right-start-on-rectangle">
                        Déconnexion
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 space-y-6">

        @if($this->activeLease)

            <!-- Bento Row 1: Status & Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Status Card -->
                <div
                    class="md:col-span-2 bg-white rounded-3xl shadow-xl shadow-zinc-200/50 border border-zinc-100 p-8 flex flex-col sm:flex-row gap-6 items-center justify-between ring-1 ring-zinc-900/5 transition-transform hover:-translate-y-1">
                    <div class="flex items-center gap-6 w-full">
                        <div
                            class="size-20 rounded-2xl {{ $this->hasPaidCurrentMonth ? 'bg-emerald-50 text-emerald-600' : 'bg-orange-50 text-orange-600' }} flex items-center justify-center shrink-0 shadow-inner">
                            @if($this->hasPaidCurrentMonth)
                                <flux:icon.check-badge class="size-10" />
                            @else
                                <flux:icon.exclamation-circle class="size-10" />
                            @endif
                        </div>
                        <div>
                            <flux:badge size="sm" inset="top bottom" class="mb-2"
                                :color="$this->hasPaidCurrentMonth ? 'emerald' : 'orange'">
                                {{ $this->hasPaidCurrentMonth ? 'Paiement à jour' : 'Paiement en attente' }}
                            </flux:badge>
                            <h2 class="text-2xl font-bold text-zinc-900 tracking-tight">
                                {{ number_format($this->activeLease->rent_amount, 0, ',', ' ') }} XOF
                            </h2>
                            <p class="text-zinc-500 font-medium mt-1">Montant mensuel • {{ now()->translatedFormat('F Y') }}
                            </p>
                        </div>
                    </div>

                    <div class="w-full md:w-auto shrink-0 mt-4 sm:mt-0">
                        @if($this->hasPaidCurrentMonth)
                            <div class="text-center px-6 py-4 rounded-2xl bg-zinc-50 border border-zinc-100">
                                <p class="text-sm font-semibold text-emerald-600">Tout est en ordre !</p>
                                <p class="text-xs text-zinc-500 mt-1">Merci pour votre ponctualité.</p>
                            </div>
                        @else
                            <flux:button variant="filled"
                                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-500/30 rounded-full px-8 py-2 md:py-3 transition-all hover:scale-105"
                                href="{{ route('client.pay') }}" icon="credit-card">
                                Payer maintenant
                            </flux:button>
                        @endif
                    </div>
                </div>

                <!-- Quick Action: Maintenance -->
                <div class="bg-linear-to-br from-indigo-500 to-indigo-600 rounded-3xl shadow-xl shadow-indigo-500/20 p-8 flex flex-col justify-between text-white relative overflow-hidden group cursor-pointer ring-1 ring-white/20 transition-transform hover:-translate-y-1"
                    x-on:click="Flux.modal('client-create-maintenance').show()">
                    <div
                        class="absolute top-0 right-0 -mr-8 -mt-8 opacity-20 transform group-hover:scale-110 transition-transform duration-500">
                        <flux:icon.wrench-screwdriver class="size-48" />
                    </div>
                    <div class="relative z-10">
                        <div
                            class="size-12 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center mb-6 shadow-sm border border-white/10">
                            <flux:icon.lifebuoy class="size-6 text-white" />
                        </div>
                        <h3 class="text-xl font-bold tracking-tight">Un souci ?</h3>
                        <p class="text-indigo-100 text-sm mt-1">Signaler un problème technique (panne, fuite...)</p>
                    </div>
                </div>

            </div>

            <!-- Bento Row 2: Property Info & History -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Property Details -->
                <div
                    class="bg-white rounded-3xl shadow-lg shadow-zinc-200/50 border border-zinc-100 p-8 flex flex-col relative overflow-hidden group">
                    <div
                        class="absolute top-0 right-0 p-6 opacity-5 backdrop-blur-3xl transform group-hover:rotate-12 transition-transform duration-700">
                        <flux:icon.home-modern class="w-64 h-64" />
                    </div>
                    <div class="relative z-10">
                        <h3 class="font-bold text-zinc-900 text-lg flex items-center gap-2 mb-6">
                            <flux:icon.building-office class="size-5 text-zinc-400" /> Mon Bien
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Propriété</p>
                                <p class="text-zinc-900 font-medium">{{ $this->activeLease->unit?->property?->name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Unité</p>
                                <p class="text-zinc-900 font-medium">{{ $this->activeLease->unit?->name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Adresse</p>
                                <p class="text-zinc-600 text-sm flex items-start gap-1 mt-1">
                                    <flux:icon.map-pin class="size-4 shrink-0 mt-0.5" />
                                    {{ $this->activeLease->unit?->property?->address }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-zinc-100">
                            <div
                                class="bg-red-50 rounded-2xl p-4 border border-red-100 group/urgent overflow-hidden relative">
                                <div
                                    class="absolute -right-4 -bottom-4 opacity-10 transform group-hover/urgent:scale-110 transition-transform">
                                    <flux:icon.phone class="size-24 text-red-600" />
                                </div>
                                <h4 class="text-red-700 font-bold text-sm flex items-center gap-2 mb-2">
                                    <flux:icon.exclamation-triangle class="size-4 animate-pulse" />
                                    Urgence 24h/24
                                </h4>
                                <p class="text-red-600/80 text-xs mb-3 leading-relaxed">Pour toute urgence vitale ou
                                    sinistre grave.</p>
                                <a href="tel:+22500000000"
                                    class="flex items-center justify-between bg-white px-4 py-2 rounded-xl shadow-sm border border-red-200 text-red-700 font-bold hover:bg-red-50 transition-colors relative z-10">
                                    <span>Appeler l'astreinte</span>
                                    <flux:icon.phone class="size-4" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last Payments + Maintenance Tickets -->
                <div class="lg:col-span-2 space-y-6 flex flex-col">

                    <!-- Payments -->
                    <div
                        class="bg-white rounded-3xl shadow-lg shadow-zinc-200/50 border border-zinc-100 flex-1 overflow-hidden flex flex-col">
                        <div class="px-6 py-5 border-b border-zinc-100 flex items-center justify-between bg-zinc-50/50">
                            <h3 class="font-bold text-zinc-900 flex items-center gap-2">
                                <flux:icon.banknotes class="size-5 text-emerald-500" /> Historique des Paiements
                            </h3>
                            <flux:button variant="ghost" size="xs" class="text-zinc-500"
                                href="{{ route('client.payments') }}">Voir tout</flux:button>
                        </div>
                        <div class="p-2 flex-1 relative flex flex-col justify-center">
                            @if($this->recentPayments->count() > 0)
                                <div class="divide-y divide-zinc-50">
                                    @foreach($this->recentPayments as $payment)
                                        <div
                                            class="flex items-center justify-between p-4 hover:bg-zinc-50/80 transition-colors rounded-2xl">
                                            <div class="flex items-center gap-4">
                                                <div
                                                    class="size-10 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                                                    <flux:icon.check class="size-5 text-emerald-600" />
                                                </div>
                                                <div>
                                                    <p class="font-bold text-zinc-900">
                                                        {{ number_format($payment->amount, 0, ',', ' ') }} XOF
                                                    </p>
                                                    <p class="text-xs font-medium text-zinc-500">
                                                        {{ $payment->paid_at?->translatedFormat('d F Y') }}
                                                    </p>
                                                </div>
                                            </div>
                                            <flux:button size="sm" icon="document-text"
                                                class="rounded-xl border-zinc-200 text-zinc-600 hover:text-zinc-900">Quittance
                                            </flux:button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <flux:icon.inbox class="size-10 text-zinc-300 mx-auto" />
                                    <p class="text-zinc-500 text-sm mt-2">Aucun historique de paiement récent.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Maintenance Tickets -->
                    <div
                        class="bg-white rounded-3xl shadow-lg shadow-zinc-200/50 border border-zinc-100 overflow-hidden shrink-0">
                        <div class="px-6 py-4 flex items-center justify-between">
                            <h3 class="font-bold text-zinc-900 flex items-center gap-2 text-sm">
                                <flux:icon.clipboard-document-check class="size-4 text-orange-500" /> Requêtes
                                d'intervention
                            </h3>
                        </div>

                        <div class="px-6 pb-4">
                            @if($this->maintenanceRequests->count() > 0)
                                <div class="flex gap-4 overflow-x-auto pb-2 snap-x">
                                    @foreach($this->maintenanceRequests as $request)
                                        <div
                                            class="min-w-50 bg-zinc-50 border border-zinc-200/75 rounded-2xl p-4 snap-start relative overflow-hidden group hover:border-orange-200 transition-colors">
                                            <div class="flex justify-between items-start mb-2">
                                                <flux:badge size="sm" :color="$request->status?->color()"
                                                    class="scale-90 origin-top-left">{{ $request->status?->label() }}</flux:badge>
                                                <span
                                                    class="text-2xs text-zinc-400 font-medium">{{ $request->created_at->diffForHumans(null, true, true) }}</span>
                                            </div>
                                            <p class="font-medium text-zinc-900 text-sm truncate pr-2"
                                                title="{{ $request->title }}">{{ $request->title }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div
                                    class="flex items-center justify-center p-4 bg-zinc-50 rounded-2xl border border-dashed border-zinc-200">
                                    <p class="text-zinc-500 text-sm flex items-center gap-2">
                                        <flux:icon.check-circle class="size-4 text-emerald-500" /> Tout fonctionne parfaitement.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

        @else
            <!-- Empty State -->
            <div class="bg-white rounded-3xl shadow-xl border border-zinc-100 p-16 text-center max-w-2xl mx-auto mt-12">
                <div class="size-24 rounded-full bg-indigo-50 flex items-center justify-center mx-auto mb-6 relative">
                    <div class="absolute inset-0 bg-indigo-100 rounded-full animate-ping opacity-20"></div>
                    <flux:icon.home class="size-12 text-indigo-500 relative z-10" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 tracking-tight">Aucun bien affecté</h2>
                <p class="text-zinc-500 mt-2 text-lg">Votre espace client est actuellement vide ou en attente
                    d'affectation par votre gestionnaire.</p>

                <div class="mt-8">
                    <flux:button variant="filled" class="bg-zinc-900 text-white rounded-xl" href="mailto:support@pms.com">
                        Contacter le support</flux:button>
                </div>
            </div>
        @endif
    </div>

    <livewire:pages::client.modals.create-maintenance />
</div>