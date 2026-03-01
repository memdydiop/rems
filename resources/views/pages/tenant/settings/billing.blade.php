<?php

use App\Models\Plan;
use App\Services\PaystackService;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app', ['title' => 'Billing'])] class extends Component {
    public function subscribe(Plan $plan, PaystackService $paystack)
    {
        $tenant = tenancy()->tenant;

        // Check for Free Plan
        if ($plan->amount == 0) {
            $tenant->update(['plan_id' => $plan->id]);

            \App\Models\Subscription::updateOrInsert(
                ['tenant_id' => $tenant->id],
                [
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->js("Flux.toast('Passé au forfait Développeur.')");
            return redirect()->route('dashboard');
        }

        // Initialize Transaction with Paystack
        $callbackUrl = route('tenant.billing.callback');

        $response = $paystack->initializeTransaction(
            email: auth()->user()->email,
            amount: $plan->amount,
            metadata: [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'custom_fields' => [
                    ['display_name' => 'Entreprise', 'variable_name' => 'company', 'value' => $tenant->company],
                ],
            ],
            callbackUrl: $callbackUrl,
            currency: $plan->currency
        );

        if ($response['status'] ?? false) {
            return redirect($response['data']['authorization_url']);
        }

        $this->js("Flux.toast('L\'initialisation du paiement a échoué : " . ($response['message'] ?? 'Erreur inconnue') . "', 'danger')");
    }

    public $billingCycle = 'monthly';

    public function with()
    {
        $currentSubscription = tenancy()->tenant->subscription;
        $interval = $this->billingCycle === 'monthly' ? 'monthly' : 'annually';

        // Calculate Usage Stats
        $stats = [];
        if ($currentSubscription && $currentSubscription->plan) {
            $tenant = tenancy()->tenant;

            // Properties
            $propertyLimit = $tenant->getFeatureLimit('max_properties');
            $propertyCount = \App\Models\Property::count();
            $stats['properties'] = [
                'label' => 'Propriétés',
                'used' => $propertyCount,
                'limit' => $propertyLimit, // null means unlimited
                'percentage' => $propertyLimit ? min(100, ($propertyCount / $propertyLimit) * 100) : 0,
            ];

            // Members (Users)
            $userLimit = $tenant->getFeatureLimit('max_users');
            $userCount = \App\Models\User::whereDoesntHave('roles', fn($q) => $q->where('name', 'ghost'))->count();

            $stats['users'] = [
                'label' => 'Membres d\'équipe',
                'used' => $userCount,
                'limit' => $userLimit,
                'percentage' => $userLimit ? min(100, ($userCount / $userLimit) * 100) : 0,
            ];
        }

        return [
            'plans' => Plan::where('interval', $interval)
                ->where('name', '!=', 'Developer')
                ->orderBy('amount')
                ->get(),
            'currentSubscription' => $currentSubscription,
            'usageStats' => $stats,
        ];
    }
};
?>

<div>
    <x-layouts::content heading="Facturation" subheading="Gérez votre abonnement.">

        @if($currentSubscription && $currentSubscription->status === 'active')
            <x-flux::card class="mb-6">
                <x-flux::card.header title="Abonnement Actuel" />
                <div class="p-4">
                    <p>Vous êtes abonné au forfait <strong>{{ $currentSubscription->plan->name }}</strong>.</p>
                    <p class="text-sm text-zinc-500">Expire le :
                        {{ $currentSubscription->ends_at?->format('d/m/Y') ?? 'Jamais' }}
                    </p>
                    <flux:badge color="green">Actif</flux:badge>
                </div>

                <!-- Usage Stats -->
                <div class="border-t border-zinc-100 bg-zinc-50/50 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @foreach($usageStats as $id => $stat)
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex items-center justify-center size-10 rounded-lg bg-white border border-zinc-200 shadow-sm shrink-0">
                                    <flux:icon :name="$id === 'properties' ? 'home-modern' : 'users'"
                                        class="size-5 text-zinc-500" />
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-end mb-2">
                                        <span class="text-sm font-medium text-zinc-700">{{ $stat['label'] }}</span>
                                        <span class="text-xs text-zinc-500">
                                            {{ $stat['used'] }} / {{ $stat['limit'] === null ? '∞' : $stat['limit'] }}
                                        </span>
                                    </div>
                                    <div class="h-2 w-full bg-zinc-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-600 rounded-full transition-all duration-500"
                                            style="width: {{ $stat['limit'] === null ? max(5, ($stat['used'] / 100)) : $stat['percentage'] }}%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-flux::card>
        @endif

        <x-flux::card>
            <x-flux::card.header>
                <div class="flex items-center justify-between">
                    <x-flux::card.title>Forfaits Disponibles</x-flux::card.title>

                    <!-- Billing Toggle -->
                    <div class="flex items-center gap-3">
                        <span
                            class="text-sm font-medium {{ $billingCycle === 'monthly' ? 'text-zinc-900' : 'text-zinc-400' }}">Mensuel</span>
                        <button
                            wire:click="$set('billingCycle', '{{ $billingCycle === 'monthly' ? 'yearly' : 'monthly' }}')"
                            class="relative w-12 h-6 rounded-full transition-colors focus:outline-hidden ring-2 ring-offset-2 ring-offset-zinc-50 focus:ring-blue-600
                             {{ $billingCycle === 'yearly' ? 'bg-blue-600' : 'bg-zinc-200' }}">
                            <span class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform shadow-sm
                                 {{ $billingCycle === 'yearly' ? 'translate-x-6' : 'translate-x-0' }}"></span>
                        </button>
                        <span
                            class="text-sm font-medium {{ $billingCycle === 'yearly' ? 'text-zinc-900' : 'text-zinc-400' }}">
                            Annuel
                        </span>
                    </div>
                </div>
            </x-flux::card.header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                @forelse($plans as $plan)
                    @php
                        $isCurrent = $currentSubscription && $currentSubscription->plan_id === $plan->id;
                    @endphp
                    @php
                        $isPremium = ($plan->paystack_code === 'PLN_business' || $plan->paystack_code === 'PLN_business_yearly');
                        $isCurrent = $currentSubscription && $currentSubscription->plan_id === $plan->id;

                        $baseClass = $isPremium ? 'relative bg-white border-2 border-[rgb(1,98,232)] shadow-[0_0_40px_rgba(1,98,232,0.15)] lg:scale-105 z-10' : 'bg-white border border-zinc-200 hover:border-[rgb(1,98,232)]/50 shadow-xl shadow-zinc-200/40 hover:-translate-y-1';
                        if ($isCurrent && !$isPremium) {
                            $baseClass .= ' ring-2 ring-emerald-500 bg-emerald-50/10';
                        }
                        $wrapperClass = "p-8 rounded-3xl flex flex-col h-full transition-all duration-300 $baseClass";
                        $titleColor = $isPremium ? 'text-[rgb(1,98,232)]' : 'text-zinc-900';

                        // Map icons
                        $iconName = match ($loop->index) {
                            0 => 'tag',
                            1 => 'hand-thumb-up',
                            2 => 'star',
                            default => 'tag'
                        };
                    @endphp

                    <div class="{{ $wrapperClass }}">
                        @if($isPremium)
                            <!-- Ribbon -->
                            <div class="absolute top-0 inset-x-0 flex justify-center -translate-y-1/2">
                                <div
                                    class="bg-[rgb(1,98,232)] text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-md flex items-center gap-1.5 uppercase tracking-wide">
                                    <flux:icon name="sparkles" class="size-3.5" />
                                    Populaire
                                </div>
                            </div>
                        @endif

                        @if($isCurrent)
                            <div class="absolute top-4 right-4">
                                <flux:badge color="green" size="sm" class="shadow-sm">Actuel</flux:badge>
                            </div>
                        @endif

                        <h6 class="font-semibold text-center text-lg {{ $titleColor }} mb-4 uppercase tracking-wider">
                            {{ str_replace(['(Yearly)', '(Free)', '(Annuel)'], '', $plan->name) }}
                        </h6>

                        <div class="py-6 flex items-center justify-center gap-6">
                            <div class="p-3 bg-[rgba(1,98,232,0.1)] rounded-full text-[rgb(1,98,232)]">
                                <flux:icon :name="$iconName" class="w-6 h-6" />
                            </div>
                            <div class="text-right">
                                <span class="text-[1.5625rem] font-semibold mb-0 text-zinc-900">
                                    {{ $plan->amount == 0 ? 'Gratuit' : Number::currency($plan->amount / 100, $plan->currency) }}
                                </span>
                                @if($plan->amount > 0)
                                    <span class="text-[#8c9097] text-xs font-semibold mb-0">
                                        / {{ $plan->interval === 'monthly' ? 'mois' : 'an' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <p class="text-[0.85rem] text-zinc-500 text-center mb-6 h-10">{{ $plan->description }}</p>

                        <div class="h-px bg-zinc-200/50 mb-6 w-full"></div>

                        <ul class="space-y-4 mb-8 flex-1 text-[0.85rem] text-left px-4">
                            @if($plan->display_features)
                                @foreach ($plan->display_features as $feature)
                                    <li class="flex items-start gap-3">
                                        <flux:icon name="check-circle" variant="solid"
                                            class="size-4 text-[rgb(1,98,232)] shrink-0 mt-0.5" />
                                        <span class="text-zinc-600">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            @else
                                <li class="text-zinc-400 italic text-center">Aucune fonctionnalité spécifique listée.</li>
                            @endif
                        </ul>

                        <div class="mt-auto pt-4">
                            @if($isCurrent)
                                <button disabled
                                    class="w-full py-3 px-4 rounded-xl font-semibold text-sm transition-all duration-200 bg-zinc-100 text-zinc-400 cursor-not-allowed">
                                    Forfait actuel
                                </button>
                            @else
                                @php
                                    $buttonLabel = "S'abonner";
                                    if ($currentSubscription) {
                                        $buttonLabel = $plan->amount >= $currentSubscription->plan->amount ? 'Améliorer' : 'Rétrograder';
                                    }
                                @endphp
                                <button wire:click="subscribe({{ $plan->id }})"
                                    wire:confirm="Êtes-vous sûr de vouloir changer de forfait ?"
                                    class="w-full py-3 px-4 rounded-xl font-semibold text-sm transition-all duration-200 {{ $isPremium ? 'bg-[rgb(1,98,232)] hover:bg-[#0152c2] text-white shadow-lg shadow-[rgba(1,98,232,0.3)] hover:-translate-y-0.5' : 'bg-zinc-900 hover:bg-zinc-800 text-white shadow-md hover:-translate-y-0.5' }}">
                                    {{ $buttonLabel }}
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12">
                        <div class="inline-flex items-center justify-center size-12 rounded-full bg-zinc-100 mb-4">
                            <flux:icon name="magnifying-glass" class="size-6 text-zinc-400" />
                        </div>
                        <h3 class="text-lg font-medium text-zinc-900">Aucun forfait trouvé</h3>
                        <p class="text-zinc-500">Aucun forfait disponible pour ce cycle de facturation.</p>
                    </div>
                @endforelse
            </div>
        </x-flux::card>
    </x-layouts::content>
</div>