<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\Tenant;

new
    #[Layout('layouts.app', ['title' => 'Détails du Client'])]
    class extends Component {

    public Tenant $tenant;

    public function mount(Tenant $tenant)
    {
        $this->tenant = $tenant->load(['domains', 'subscription.plan']);
    }

    public function visitTenant()
    {
        $domain = $this->tenant->domains->first();
        if ($domain) {
            return redirect()->away('http://' . $domain->domain);
        }
    }

    public function getSubscriptionStatusLabelProperty()
    {
        if (!$this->tenant->subscription) return 'Aucun';
        
        return match($this->tenant->subscription->status) {
            'active' => 'Actif',
            'past_due' => 'Impayé',
            'canceled' => 'Annulé',
            'trialing' => 'Essai Gratuit',
            default => ucfirst($this->tenant->subscription->status),
        };
    }
    
    public function getSubscriptionColorProperty()
    {
        if (!$this->tenant->subscription) return 'zinc';

        return match($this->tenant->subscription->status) {
            'active' => 'green',
            'past_due' => 'orange',
            'canceled' => 'red',
            'trialing' => 'blue',
            default => 'zinc',
        };
    }
};
?>

<div>
    <x-layouts::content heading="Détails du Client" subheading="Gérez les informations et l'abonnement de ce locataire.">

    <x-slot:actions>
        <flux:button icon="arrow-left" variant="ghost" href="{{ route('central.tenants.index') }}" wire:navigate>
            Retour
        </flux:button>
    </x-slot:actions>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Left Column: Info & Subscription -->
            <div class="xl:col-span-2 space-y-6">
                
                <!-- General Info Card -->
                <x-flux::card class="">

                <x-flux::card.header
                    title="{{ $tenant->company }}" 
                    subtitle="{{ $tenant->id }}"
                />
                <x-flux::card.body>
                    

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                        <div>
                            <flux:heading size="lg" class="text-sm font-medium text-zinc-500 block mb-1">Base de données</flux:heading>
                            <div class="flex items-center gap-2">
                                <flux:icon name="circle-stack" class="w-4 h-4 text-zinc-400" />
                                <flux:text>tenant_{{ $tenant->id }}</flux:text>
                            </div>
                        </div>
                        <div>
                            <flux:heading size="lg" class="text-sm font-medium text-zinc-500 block mb-1">Date d'inscription</flux:heading>
                            <div class="flex items-center gap-2">
                                <flux:icon name="calendar" class="w-4 h-4 text-zinc-400" />
                                <flux:text>{{ $tenant->created_at->format('d/m/Y') }}</flux:text>
                            </div>
                        </div>
                        
                        <!-- Owner Info (Merged from Properties) if available, otherwise placeholders -->
                         <div>
                            <flux:heading size="lg" class="text-sm font-medium text-zinc-500 block mb-1">Nom du Propriétaire</flux:heading>
                            <div class="flex items-center gap-2">
                                <flux:icon name="user" class="w-4 h-4 text-zinc-400" />
                                <flux:text>{{ $tenant->name ?? 'Non renseigné' }}</flux:text>
                            </div>
                        </div>
                         <div>
                            <span class="text-sm font-medium text-zinc-500 block mb-1">Email Propriétaire</span>
                            <div class="flex items-center gap-2">
                                <flux:icon name="envelope" class="w-4 h-4 text-zinc-400" />
                                <flux:text>{{ $tenant->email ?? 'Non renseigné' }}</flux:textflux>
                            </div>
                        </div>
                    </div>
                </x-flux::card.body>
                </x-flux::card>

                <!-- Subscription Card -->
                <x-flux::card class="p-0 overflow-hidden">

                <x-flux::card.header
                    title="Abonnement"
                    subtitle="Gérez l'abonnement de ce locataire."
                />
                <x-flux::card.body> 
                    <div class="border-b border-zinc-100 bg-zinc-50/50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                                <flux:icon name="credit-card" class="w-5 h-5 text-emerald-600" />
                            </div>
                            <div>
                                <x-flux::heading size="lg">Abonnement</x-flux::heading>
                                <span class="text-sm text-zinc-500">Statut et facturation</span>
                            </div>
                        </div>
                        <flux:badge color="{{ $this->subscriptionColor }}">{{ $this->subscriptionStatusLabel }}</flux:badge>
                    </div>

                    <div class="">
                        @if($tenant->subscription && $tenant->subscription->plan)
                            <div class="flex flex-col md:flex-row gap-8 items-start">
                                <div class="flex-1">
                                    <div class="flex items-baseline gap-2 mb-1">
                                        <h3 class="text-2xl font-bold text-zinc-900">{{ $tenant->subscription->plan->name }}</h3>
                                    </div>
                                    <p class="text-zinc-500 text-sm mb-4">
                                        {{ $tenant->subscription->plan->formatted_price }} / {{ $tenant->subscription->plan->interval == 'month' ? 'mois' : 'an' }}
                                    </p>
                                    
                                     @if($tenant->subscription->onTrial())
                                        <div class="bg-blue-50 text-blue-700 text-sm px-3 py-2 rounded-md border border-blue-100 inline-flex items-center gap-2">
                                            <flux:icon name="clock" class="w-4 h-4" />
                                            <span>Fin de l'essai le <strong>{{ $tenant->subscription->trial_ends_at->format('d/m/Y') }}</strong></span>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="w-full md:w-1/2 bg-zinc-50 rounded-xl p-4 border border-zinc-100">
                                    <h4 class="text-xs font-bold text-zinc-500 uppercase mb-3 tracking-wide">Inclus dans le plan</h4>
                                    <ul class="space-y-2">
                                        @foreach($tenant->subscription->plan->features as $key => $value)
                                            @continue($value === false)
                                            <li class="flex items-center justify-between text-sm">
                                                <span class="text-zinc-600 capitalize">{{ str_replace('_', ' ', $key) }}</span>
                                                @if(is_bool($value))
                                                    <flux:icon name="check" class="w-4 h-4 text-emerald-500" />
                                                @else
                                                    <span class="font-semibold text-zinc-900">{{ $value == -1 ? 'Illimité' : $value }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="inline-flex w-16 h-16 rounded-full bg-zinc-100 items-center justify-center mb-3">
                                    <flux:icon name="stop" class="w-6 h-6 text-zinc-400" />
                                </div>
                                <h3 class="text-zinc-900 font-medium">Aucun abonnement actif</h3>
                                <p class="text-zinc-500 text-sm mt-1">Ce client n'a pas encore souscrit à une offre.</p>
                            </div>
                        @endif
                    </div>
                </x-flux::card.body>
                </x-flux::card>
            </div>

            <!-- Right Column: Domains & Actions -->
            <div class="space-y-6">
                 <!-- Actions Card -->
                <x-flux::card class="bg-gradient-to-br from-indigo-500 to-violet-600 text-white border-0">

                <x-flux::card.body>
                    <div class="flex flex-col gap-4">
                        <div>
                            <h3 class="font-bold text-lg text-white">Administration</h3>
                            <p class="text-indigo-100 text-sm mt-1">Accédez au panneau d'administration de ce client.</p>
                        </div>
                        @if($tenant->domains->first())
                            <flux:button href="{{ route('central.impersonate', $tenant->id) }}" class="w-full bg-indigo-800/20 hover:bg-indigo-800/30 text-white border-0 shadow-none backdrop-blur-sm">
                                <flux:icon name="user-plus" class="w-4 h-4 mr-2" />
                                Se connecter en tant que client
                            </flux:button>
                        @endif
                    </div>
                </x-flux::card.body>
                </x-flux::card>

                <!-- Domains Card (Flux Table) -->
                <x-flux::card class="overflow-hidden">

                <x-flux::card.header icon="globe-alt" title="Domaines connectés" />

                <x-flux::card.body class="p-0!">
                    
                    <x-flux::table>
                        <x-flux::table.columns>
                            <x-flux::table.column>Domaine</x-flux::table.column>
                            <x-flux::table.column align="end">Action</x-flux::table.column>
                        </x-flux::table.columns>
                        <x-flux::table.rows>
                            @forelse($tenant->domains as $domain)
                                <x-flux::table.row>
                                    <x-flux::table.cell>
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                            <span class="font-medium text-zinc-700">{{ $domain->domain }}</span>
                                        </div>
                                    </x-flux::table.cell>
                                    <x-flux::table.cell align="end">
                                        @php
                                            $protocol = request()->secure() ? 'https' : 'http';
                                            $port = request()->getPort();
                                            $portPart = ($port !== 80 && $port !== 443) ? ":$port" : '';
                                            $url = $protocol . '://' . $domain->domain . $portPart;
                                        @endphp
                                        <flux:button icon="arrow-top-right-on-square" size="xs" variant="ghost" href="{{ $url }}" target="_blank" />
                                    </x-flux::table.cell>
                                </x-flux::table.row>
                            @empty
                                <x-flux::table.row>
                                    <x-flux::table.cell colspan="2" class="text-center text-zinc-500 italic">Aucun domaine configuré</x-flux::table.cell>
                                </x-flux::table.row>
                            @endforelse
                        </x-flux::table.rows>
                    </x-flux::table>

                </x-flux::card.body>
                </x-flux::card>

            </div>
        </div>
    </x-layouts::content>
</div>