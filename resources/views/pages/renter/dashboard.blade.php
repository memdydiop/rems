<?php

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Renter;
use App\Models\RentPayment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
    #[Layout('layouts.renter', ['title' => 'Mon Espace Locataire'])]
    class extends Component {

    #[Computed]
    public function renter(): ?Renter
    {
        return Renter::where('user_id', auth()->id())->first();
    }

    #[Computed]
    public function activeLease(): ?Lease
    {
        return $this->renter?->leases()
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
            ->take(5)
            ->get();
    }
};
?>

<div class="min-h-screen bg-zinc-50 pb-12">
    <!-- Hero Section -->
    <div class="bg-white border-b border-zinc-200 pb-32 pt-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-zinc-500 font-medium mb-1">Espace Locataire</p>
                    <h1 class="text-3xl md:text-4xl font-bold text-zinc-900">Bonjour, {{ $this->renter?->first_name }}
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    <flux:button variant="filled" class="bg-emerald-600 hover:bg-emerald-700 text-white"
                        href="{{ route('renter.pay') }}" icon="credit-card">
                        Payer mon loyer
                    </flux:button>
                    <flux:button variant="subtle" class="hover:bg-zinc-100 border-zinc-200" href="{{ route('logout') }}"
                        icon="arrow-right-start-on-rectangle">
                        Déconnexion
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content (Overlapping Hero) -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-24 space-y-6">

        @if($this->activeLease)
            <!-- Lease Card (Hero) -->
            <div
                class="bg-white rounded-2xl shadow-xl border border-zinc-100 p-6 md:p-8 flex flex-col md:flex-row gap-8 items-center justify-between">
                <div class="flex items-center gap-6 w-full">
                    <div class="size-20 rounded-2xl bg-indigo-50 flex items-center justify-center shrink-0">
                        <flux:icon.home class="size-10 text-indigo-600" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-zinc-900">{{ $this->activeLease->unit?->property?->name }}</h2>
                        <p class="text-lg text-zinc-600">{{ $this->activeLease->unit?->name }}</p>
                        <p class="text-zinc-400 mt-1 flex items-center gap-1">
                            <flux:icon.map-pin class="size-4" />
                            {{ $this->activeLease->unit?->property?->address }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex flex-row md:flex-col gap-4 w-full md:w-auto border-t md:border-t-0 md:border-l border-zinc-100 pt-6 md:pt-0 md:pl-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Loyer Mensuel</p>
                        <p class="text-3xl font-bold text-zinc-900">
                            {{ number_format($this->activeLease->rent_amount, 0, ',', ' ') }} <span
                                class="text-sm font-normal text-zinc-500">XOF</span>
                        </p>
                    </div>
                    <div>
                        <flux:badge color="green" size="sm" inset="top bottom">Bail Actif</flux:badge>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Payments -->
                <x-flux::card class="border-0 shadow-lg ring-1 ring-zinc-200/50">
                    <x-flux::card.header>
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-50 rounded-lg text-green-600">
                                <flux:icon.credit-card class="size-5" />
                            </div>
                            <x-flux::card.title>Derniers Paiements</x-flux::card.title>
                        </div>
                    </x-flux::card.header>

                    <div class="mt-4 space-y-4">
                        @forelse($this->recentPayments as $payment)
                            <div
                                class="flex items-center justify-between p-3 rounded-lg hover:bg-zinc-50 transition-colors border border-transparent hover:border-zinc-100">
                                <div>
                                    <p class="font-bold text-zinc-900">{{ number_format($payment->amount, 0, ',', ' ') }} XOF
                                    </p>
                                    <p class="text-xs text-zinc-500">{{ $payment->paid_at?->format('d/m/Y') }}</p>
                                </div>
                                <flux:button size="xs" icon="arrow-down-tray" variant="ghost">Reçu</flux:button>
                            </div>
                        @empty
                            <p class="text-zinc-500 text-center py-4">Aucun paiement récent.</p>
                        @endforelse
                    </div>
                    <div class="mt-6 pt-4 border-t border-zinc-100 text-center">
                        <flux:button variant="ghost" class="w-full text-zinc-500" href="{{ route('renter.payments') }}">Voir
                            tout l'historique</flux:button>
                    </div>
                </x-flux::card>

                <!-- Maintenance -->
                <x-flux::card class="border-0 shadow-lg ring-1 ring-zinc-200/50">
                    <x-flux::card.header class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-orange-50 rounded-lg text-orange-600">
                                <flux:icon.wrench-screwdriver class="size-5" />
                            </div>
                            <x-flux::card.title>Maintenance</x-flux::card.title>
                        </div>
                        <flux:button size="sm" variant="filled" class="bg-zinc-900 text-white hover:bg-zinc-800" icon="plus"
                            x-on:click="Flux.modal('create-maintenance').show()">
                            Nouveau
                        </flux:button>
                    </x-flux::card.header>

                    <div class="mt-4 space-y-4">
                        @forelse($this->maintenanceRequests as $request)
                            <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-100 bg-zinc-50/50">
                                <div>
                                    <p class="font-medium text-zinc-900">{{ $request->title }}</p>
                                    <p class="text-xs text-zinc-500">{{ $request->created_at->diffForHumans() }}</p>
                                </div>
                                <flux:badge size="sm" :color="$request->status?->color()">{{ $request->status?->label() }}
                                </flux:badge>
                            </div>
                        @empty
                            <div class="text-center py-6">
                                <flux:icon.check-circle class="size-8 text-zinc-300 mx-auto mb-2" />
                                <p class="text-zinc-500">Aucun signalement en cours. Tout va bien !</p>
                            </div>
                        @endforelse
                    </div>
                </x-flux::card>
            </div>

        @else
            <!-- Empty State -->
            <div class="bg-white rounded-2xl shadow-xl border border-zinc-100 p-12 text-center">
                <div class="size-20 rounded-full bg-zinc-100 flex items-center justify-center mx-auto mb-6">
                    <flux:icon.home class="size-10 text-zinc-400" />
                </div>
                <h2 class="text-xl font-bold text-zinc-900">Aucun bail actif</h2>
                <p class="text-zinc-500 mt-2">Votre dossier est vide pour le moment.</p>
            </div>
        @endif
    </div>

    <livewire:pages::renter.modals.create-maintenance />
</div>