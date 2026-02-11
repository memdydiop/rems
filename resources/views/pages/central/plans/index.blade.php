<?php

use App\Models\Plan;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\Computed;

new #[Layout('layouts.app', ['title' => 'Plans'])] class extends Component {

    // Form Properties
    public $name = '';
    public $description = '';
    public $amount = 0;
    public $trial_period_days = 0;
    public $currency = 'XOF'; // Default to FCFA
    public $interval = 'monthly';
    public $paystack_code = '';

    // Feature Management
    public $features = [];
    public $featureKey = '';
    public $featureValue = '';

    public $billingCycle = 'monthly';
    public ?Plan $editing = null;

    #[Computed]
    public function filteredPlans()
    {
        $interval = $this->billingCycle === 'monthly' ? 'monthly' : 'annually';
        return Plan::where('interval', $interval)
            ->where('name', '!=', 'Developer') // Hide dev plan if exists
            ->orderBy('amount')
            ->get();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'trial_period_days' => 'required|integer|min:0',
            'currency' => 'required|string|size:3',
            'interval' => 'required|in:monthly,yearly',
            'paystack_code' => 'nullable|string|max:255',
            'features' => 'array',
        ];
    }

    public function openCreate()
    {
        $this->reset();
        $this->features = [];
        $this->currency = 'XOF';
        $this->interval = 'monthly';
        $this->trial_period_days = 0;
        $this->js("Flux.modal('plan-modal').show()");
    }

    public function edit(Plan $plan)
    {
        $this->editing = $plan;
        $this->name = $plan->name;
        $this->description = $plan->description;
        $this->amount = $plan->amount / 100;
        $this->trial_period_days = $plan->trial_period_days;
        $this->currency = $plan->currency;
        $this->interval = $plan->interval;
        $this->paystack_code = $plan->paystack_code;
        $this->features = $plan->features ?? [];

        $this->js("Flux.modal('plan-modal').show()");
    }

    public function addFeature()
    {
        $this->validate([
            'featureKey' => 'required|string|max:50',
            'featureValue' => 'nullable|string|max:50',
        ]);

        $key = \Illuminate\Support\Str::slug($this->featureKey, '_');
        $value = $this->featureValue === '' ? true : (is_numeric($this->featureValue) ? (int) $this->featureValue : $this->featureValue);

        $this->features[$key] = $value;

        $this->reset('featureKey', 'featureValue');
    }

    public function removeFeature($key)
    {
        unset($this->features[$key]);
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'amount' => $this->amount * 100, // Convert to cents
            'trial_period_days' => $this->trial_period_days,
            'currency' => strtoupper($this->currency),
            'interval' => $this->interval,
            'paystack_code' => $this->paystack_code ?: null,
            'features' => $this->features,
        ];

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            // Auto-generate code if missing
            if (empty($data['paystack_code'])) {
                $data['paystack_code'] = 'PLN_' . strtoupper(\Illuminate\Support\Str::random(10));
            }
            Plan::create($data);
        }

        $this->js("Flux.modal('plan-modal').close()");
        $this->reset();
    }

    public function delete(Plan $plan)
    {
        $plan->delete();
    }
};
?>

<div>
    <x-layouts::content heading="Mes Forfaits" subheading="Gérez vos abonnements et tarifications.">
        <x-slot:actions>
            <flux:modal.trigger name="plan-modal">
                <flux:button icon="plus" variant="primary" wire:click="openCreate">Nouveau Forfait</flux:button>
            </flux:modal.trigger>
        </x-slot:actions>

        <!-- Toggle Interval -->
        <div class="flex justify-center mb-8">
            <div class="bg-zinc-100 p-1 rounded-lg inline-flex relative">
                <div class="w-1/2 h-full absolute top-0 left-0 bg-white rounded-md shadow-sm transition-all duration-300 ease-out"
                    style="transform: translateX({{ $billingCycle === 'monthly' ? '0%' : '100%' }});"></div>

                <button wire:click="$set('billingCycle', 'monthly')"
                    class="relative z-10 px-6 py-1.5 text-sm font-medium transition-colors duration-300 {{ $billingCycle === 'monthly' ? 'text-zinc-900' : 'text-zinc-500 hover:text-zinc-700' }}">
                    Mensuel
                </button>
                <button wire:click="$set('billingCycle', 'yearly')"
                    class="relative z-10 px-6 py-1.5 text-sm font-medium transition-colors duration-300 {{ $billingCycle === 'yearly' ? 'text-zinc-900' : 'text-zinc-500 hover:text-zinc-700' }}">
                    Annuel
                </button>
            </div>
        </div>

        <!-- Plans Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($this->filteredPlans as $plan)
                <x-flux::card class="flex flex-col !p-0 overflow-hidden hover:shadow-lg transition-shadow duration-300">

                    <!-- Card Header -->
                    <div class="p-6 bg-white border-b border-zinc-100">
                        <div class="flex justify-between items-start mb-2">
                            <flux:heading size="lg">{{ $plan->name }}</flux:heading>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" wire:click="edit({{ $plan->id }})">Modifier
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" wire:click="delete({{ $plan->id }})" variant="danger"
                                        wire:confirm="Êtes-vous sûr de vouloir supprimer ce forfait ?">Supprimer
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        <div class="mt-2 flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-zinc-900">{{ $plan->formatted_price }}</span>
                            <span class="text-sm text-zinc-500">/ {{ $plan->interval === 'monthly' ? 'mois' : 'an' }}</span>
                        </div>

                        <p class="mt-4 text-sm text-zinc-500 line-clamp-2">{{ $plan->description ?? 'Aucune description.' }}
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <flux:badge size="sm" icon="code-bracket">{{ $plan->paystack_code }}</flux:badge>
                            @if($plan->trial_period_days > 0)
                                <flux:badge size="sm" color="indigo">{{ $plan->trial_period_days }}j d'essai</flux:badge>
                            @endif
                        </div>
                    </div>

                    <!-- Features List -->
                    <div class="flex-1 bg-zinc-50/50 p-6">
                        <flux:heading size="xs" class="uppercase tracking-wider text-zinc-400 mb-4">Fonctionnalités
                        </flux:heading>

                        <ul class="space-y-3">
                            @forelse($plan->display_features as $feature)
                                <li class="flex items-start gap-3">
                                    <div
                                        class="mt-0.5 w-5 h-5 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                        <flux:icon name="check" class="size-3" />
                                    </div>
                                    <span class="text-sm text-zinc-600">{{ $feature }}</span>
                                </li>
                            @empty
                                <li class="text-sm text-zinc-400 italic">Aucune fonctionnalité définie.</li>
                            @endforelse
                        </ul>
                    </div>
                </x-flux::card>
            @endforeach
        </div>

        <!-- Create/Edit Modal -->
        <flux:modal name="plan-modal" class="md:w-[600px]">
            <form wire:submit="save" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $editing ? 'Modifier le Forfait' : 'Nouveau Forfait' }}</flux:heading>
                    <flux:subheading>Définissez les limites et la tarification.</flux:subheading>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="name" label="Nom du Forfait" placeholder="Ex: Premium" />
                    <flux:textarea wire:model="description" label="Description" placeholder="Description courte..."
                        rows="2" />

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="amount" label="Prix" type="number" step="100" min="0">
                            <x-slot:start>
                                <span class="text-sm text-zinc-500 pl-2">{{ $currency }}</span>
                            </x-slot:start>
                        </flux:input>

                        <flux:select wire:model="interval" label="Facturation">
                            <flux:select.option value="monthly">Mensuel</flux:select.option>
                            <flux:select.option value="yearly">Annuel</flux:select.option>
                        </flux:select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="trial_period_days" label="Jours d'essai" type="number" min="0" />
                        <flux:input wire:model="paystack_code" label="Code Paystack" placeholder="PLN_..."
                            hint="Optionnel si auto-généré" />
                    </div>

                    <!-- Feature Management -->
                    <div class="border-t border-zinc-100 pt-4">
                        <flux:label>Fonctionnalités & Limites</flux:label>
                        <flux:subheading class="mb-3">Ajoutez des fonctionnalités (boolean) ou des limites numériques.
                        </flux:subheading>

                        <div class="flex gap-2 mb-4">
                            <div class="w-1/2">
                                <flux:input wire:model="featureKey" placeholder="Ex: max_users" />
                            </div>
                            <div class="w-1/3">
                                <flux:input wire:model="featureValue" placeholder="Valeur (vide = Oui)" />
                            </div>
                            <flux:button wire:click.prevent="addFeature" icon="plus" variant="primary" square
                                class="shrink-0" />
                        </div>

                        <div class="bg-zinc-50 rounded-lg border border-zinc-200 divide-y divide-zinc-200">
                            @forelse($features as $key => $value)
                                <div class="flex items-center justify-between p-2 px-3">
                                    <div class="flex items-center gap-2">
                                        <code
                                            class="text-xs text-indigo-600 bg-indigo-50 px-1 py-0.5 rounded">{{ $key }}</code>
                                        <span class="text-sm text-zinc-600">:</span>
                                        <span class="text-sm font-medium text-zinc-900">
                                            {{ $value === true ? 'Oui' : $value }}
                                        </span>
                                    </div>
                                    <flux:button variant="ghost" size="xs" icon="trash"
                                        class="text-red-500 hover:text-red-600 hover:bg-red-50"
                                        wire:click="removeFeature('{{ $key }}')" />
                                </div>
                            @empty
                                <div class="p-3 text-center text-sm text-zinc-400 italic">
                                    Aucune fonctionnalité ajoutée.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Annuler</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Enregistrer</flux:button>
                </div>
            </form>
        </flux:modal>
    </x-layouts::content>
</div>