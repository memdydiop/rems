<?php
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Renter;

new
    #[Layout('layouts.guest', ['title' => 'Configuration Initiale'])]
    class extends Component {

    public int $currentStep = 1;
    public int $totalSteps = 4;

    // Step 1: Property
    public string $propertyName = '';
    public string $propertyAddress = '';
    public string $propertyType = 'residential';

    // Step 2: Unit
    public string $unitName = '';
    public string $unitRent = '';

    // Step 3: Renter (optional)
    public string $renterFirstName = '';
    public string $renterLastName = '';
    public string $renterEmail = '';
    public string $renterPhone = '';

    public ?Property $createdProperty = null;
    public ?Unit $createdUnit = null;

    public function nextStep()
    {
        $this->validateCurrentStep();

        if ($this->currentStep === 1) {
            $this->createProperty();
        } elseif ($this->currentStep === 2) {
            $this->createUnit();
        } elseif ($this->currentStep === 3) {
            if ($this->renterFirstName && $this->renterLastName) {
                $this->createRenter();
            }
        }

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function skip()
    {
        $this->currentStep++;
    }

    protected function validateCurrentStep()
    {
        $rules = match ($this->currentStep) {
            1 => ['propertyName' => 'required|min:2', 'propertyAddress' => 'required'],
            2 => ['unitName' => 'required', 'unitRent' => 'required|numeric|min:0'],
            3 => [],
            default => [],
        };

        if (!empty($rules)) {
            $this->validate($rules);
        }
    }

    protected function createProperty()
    {
        $this->createdProperty = Property::create([
            'name' => $this->propertyName,
            'address' => $this->propertyAddress,
            'type' => $this->propertyType,
        ]);
    }

    protected function createUnit()
    {
        if ($this->createdProperty) {
            $this->createdUnit = Unit::create([
                'property_id' => $this->createdProperty->id,
                'name' => $this->unitName,
                'rent_amount' => (float) $this->unitRent,
                'status' => 'available',
            ]);
        }
    }

    protected function createRenter()
    {
        Renter::create([
            'first_name' => $this->renterFirstName,
            'last_name' => $this->renterLastName,
            'email' => $this->renterEmail,
            'phone' => $this->renterPhone,
        ]);
    }

    public function finish()
    {
        return redirect()->route('dashboard');
    }
};
?>

<div class="min-h-screen bg-zinc-50 flex flex-col items-center justify-center p-4 relative overflow-hidden">
    <!-- Ambient Background -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[20%] -left-[10%] w-[70%] h-[70%] rounded-full bg-blue-500/10 blur-3xl"></div>
        <div class="absolute top-[40%] -right-[10%] w-[60%] h-[60%] rounded-full bg-indigo-500/10 blur-3xl"></div>
    </div>

    <div class="w-full max-w-lg relative z-10">
        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900 mb-2">Bienvenue sur {{ config('app.name') }}</h1>
            <p class="text-zinc-500">Configurons votre espace en quelques étapes.</p>
        </div>

        <!-- Segmented Progress Bar -->
        <div class="flex gap-2 mb-8 px-2">
            @for($i = 1; $i <= $totalSteps; $i++)
                <div
                    class="h-1.5 flex-1 rounded-full transition-all duration-500 {{ $i <= $currentStep ? 'bg-zinc-900' : 'bg-zinc-200' }}">
                </div>
            @endfor
        </div>

        <!-- Card -->
        <div class="bg-white backdrop-blur-xl border border-zinc-200 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-8">
                <!-- Transitions Wrapper -->
                <div x-data="{ step: @entangle('currentStep') }" class="relative min-h-[300px]">

                    <!-- Step 1: Property -->
                    <div x-show="step === 1" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-4" class="absolute inset-0">
                        <div class="text-center mb-8">
                            <div
                                class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-blue-50 text-blue-600 mb-4 ring-1 ring-blue-100">
                                <flux:icon name="building-office-2" class="w-6 h-6" />
                            </div>
                            <h2 class="text-xl font-semibold text-zinc-900">Votre première propriété</h2>
                        </div>

                        <div class="space-y-5">
                            <flux:input label="Nom de la propriété" wire:model="propertyName"
                                placeholder="Ex: Résidence Les Jardins" />
                            <flux:input label="Adresse" wire:model="propertyAddress"
                                placeholder="Ex: 123 Rue Principale" />
                            <flux:select label="Type" wire:model="propertyType">
                                <flux:select.option value="residential">Résidentiel</flux:select.option>
                                <flux:select.option value="commercial">Commercial</flux:select.option>
                                <flux:select.option value="mixed">Mixte</flux:select.option>
                            </flux:select>
                        </div>
                    </div>

                    <!-- Step 2: Unit -->
                    <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-4" class="absolute inset-0">
                        <div class="text-center mb-8">
                            <div
                                class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 mb-4 ring-1 ring-emerald-100">
                                <flux:icon name="home" class="w-6 h-6" />
                            </div>
                            <h2 class="text-xl font-semibold text-zinc-900">Ajoutez une unité</h2>
                        </div>

                        <div class="space-y-5">
                            <flux:input label="Nom de l'unité" wire:model="unitName" placeholder="Ex: Appartement A1" />
                            <flux:input type="number" label="Loyer mensuel" wire:model="unitRent" placeholder="0"
                                currency="FCFA" />
                        </div>
                    </div>

                    <!-- Step 3: Renter -->
                    <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-4" class="absolute inset-0">
                        <div class="text-center mb-8">
                            <div
                                class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-violet-50 text-violet-600 mb-4 ring-1 ring-violet-100">
                                <flux:icon name="user-plus" class="w-6 h-6" />
                            </div>
                            <h2 class="text-xl font-semibold text-zinc-900">Ajoutez un locataire</h2>
                            <p class="text-sm text-zinc-500 mt-1">Optionnel, vous pourrez le faire plus tard.</p>
                        </div>

                        <div class="space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <flux:input label="Prénom" wire:model="renterFirstName" placeholder="Jean" />
                                <flux:input label="Nom" wire:model="renterLastName" placeholder="Dupont" />
                            </div>
                            <flux:input type="email" label="Email" wire:model="renterEmail"
                                placeholder="jean@exemple.com" />
                            <flux:input label="Téléphone" wire:model="renterPhone" placeholder="+225..." />
                        </div>
                    </div>

                    <!-- Step 4: Completion -->
                    <div x-show="step === 4" x-cloak x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        class="absolute inset-0 flex flex-col items-center justify-center text-center h-full">
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-50 text-green-600 mb-6 ring-4 ring-green-50">
                            <flux:icon name="check" class="w-8 h-8" />
                        </div>
                        <h2 class="text-2xl font-bold text-zinc-900 mb-2">Tout est prêt !</h2>
                        <p class="text-zinc-500 max-w-xs mx-auto mb-8">Votre espace a été configuré avec succès. Vous
                            pouvez maintenant gérer vos biens.</p>

                        <div class="w-full bg-zinc-50 rounded-lg p-4 text-left border border-zinc-100">
                            <h3 class="font-medium text-zinc-900 mb-3 text-sm uppercase tracking-wider">Récapitulatif
                            </h3>
                            <ul class="space-y-2 text-sm text-zinc-600">
                                <li class="flex items-center gap-2">
                                    <flux:icon name="building-office" class="w-4 h-4 text-zinc-400" />
                                    {{ $propertyName }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <flux:icon name="home" class="w-4 h-4 text-zinc-400" /> {{ $unitName }}
                                </li>
                                @if($renterFirstName)
                                    <li class="flex items-center gap-2">
                                        <flux:icon name="user" class="w-4 h-4 text-zinc-400" /> {{ $renterFirstName }}
                                        {{ $renterLastName }}
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="bg-zinc-50 px-8 py-5 border-t border-zinc-100 flex items-center justify-between">
                @if($currentStep > 1 && $currentStep < 4)
                    <flux:button variant="ghost" wire:click="previousStep">Retour</flux:button>
                @else
                    <div></div>
                @endif

                @if($currentStep < 4)
                    <div class="flex gap-3">
                        @if($currentStep === 3)
                            <flux:button variant="ghost" wire:click="skip">Passer</flux:button>
                        @endif
                        <flux:button variant="filled" wire:click="nextStep">
                            {{ $currentStep === 3 ? 'Terminer' : 'Continuer' }}
                        </flux:button>
                    </div>
                @else
                    <flux:button variant="filled" wire:click="finish" class="w-full justify-center">
                        Accéder au tableau de bord
                    </flux:button>
                @endif
            </div>
        </div>

        <!-- Skip Link -->
        @if($currentStep < 4)
            <div class="text-center mt-6">
                <button wire:click="finish" class="text-sm text-zinc-400 hover:text-zinc-600 transition-colors">
                    Passer la configuration pour l'instant
                </button>
            </div>
        @endif
    </div>
</div>