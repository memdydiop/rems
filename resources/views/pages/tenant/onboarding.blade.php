<?php
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\{Property, Unit, Renter};
use App\Enums\{PropertyType};

new #[Layout('layouts.guest', ['title' => 'Configuration Initiale'])] class extends Component {

    public int $currentStep = 0;
    public int $totalSteps = 4;

    // Step 1: Property
    public string $propertyName = '';
    public string $propertyAddress = '';
    public string $propertyType = PropertyType::ResidentialBuilding->value;

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
    public ?Renter $createdRenter = null;

    public function nextStep()
    {
        if ($this->currentStep === 0) {
            // No validation or data creation for the welcome step
        } else {
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
        }

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 0) {
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
            0 => [], // No validation for the welcome step
            1 => ['propertyName' => 'required|min:2', 'propertyAddress' => 'required'],
            2 => ['unitName' => 'required', 'unitRent' => 'required|numeric|min:0'],
            3 => [
                'renterFirstName' => 'nullable|min:2',
                'renterLastName' => 'required_with:renterFirstName',
                'renterEmail' => 'nullable|email',
            ],
            default => [],
        };

        if (!empty($rules)) {
            $this->validate($rules);
        }
    }

    protected function createProperty()
    {
        if ($this->createdProperty) {
            $this->createdProperty->update([
                'name' => $this->propertyName,
                'address' => $this->propertyAddress,
                'type' => $this->propertyType,
            ]);
        } else {
            $this->createdProperty = Property::create([
                'name' => $this->propertyName,
                'address' => $this->propertyAddress,
                'type' => $this->propertyType,
            ]);
        }
    }

    protected function createUnit()
    {
        if (!$this->createdProperty)
            return;

        if ($this->createdUnit) {
            $this->createdUnit->update([
                'name' => $this->unitName,
                'rent_amount' => (float) $this->unitRent,
            ]);
        } else {
            $this->createdUnit = Unit::create([
                'property_id' => $this->createdProperty->id,
                'name' => $this->unitName,
                'rent_amount' => (float) $this->unitRent,
            ]);
        }
    }

    protected function createRenter()
    {
        if ($this->createdRenter) {
            $this->createdRenter->update([
                'first_name' => $this->renterFirstName,
                'last_name' => $this->renterLastName,
                'email' => $this->renterEmail,
                'phone' => $this->renterPhone,
            ]);
        } else {
            $this->createdRenter = Renter::create([
                'first_name' => $this->renterFirstName,
                'last_name' => $this->renterLastName,
                'email' => $this->renterEmail,
                'phone' => $this->renterPhone,
            ]);
        }

        if ($this->createdUnit && $this->createdRenter) {
            // Check if active lease already exists
            $existingLease = \App\Models\Lease::where('unit_id', $this->createdUnit->id)
                ->where('renter_id', $this->createdRenter->id)
                ->where('status', 'active')
                ->first();

            if (!$existingLease) {
                \App\Models\Lease::create([
                    'unit_id' => $this->createdUnit->id,
                    'renter_id' => $this->createdRenter->id,
                    'start_date' => now(),
                    'rent_amount' => $this->createdUnit->rent_amount,
                    'status' => 'active',
                ]);
            }
        }
    }

    public function finish()
    {
        $user = auth()->user();
        $user->has_completed_onboarding = true;
        $user->save();

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
        <div class="text-center mb-10" x-show="$wire.currentStep > 0" x-cloak>
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900 mb-2">Configuration de l'espace</h1>
            <p class="text-zinc-500">Créons votre première propriété pour commencer à générer des loyers.</p>
        </div>

        <!-- Segmented Progress Bar -->
        <div class="flex gap-2 mb-8 px-2" x-show="$wire.currentStep > 0" x-cloak>
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
                <div x-data="{ step: @entangle('currentStep') }" class="relative min-h-75">

                    <!-- Step 0: Welcome -->
                    <div x-show="step === 0" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-4"
                        class="absolute inset-0 flex flex-col items-center justify-center text-center h-full">
                        <div class="mb-6">
                            <x-app-logo-icon class="h-16 w-auto" />
                        </div>
                        <h2 class="text-2xl font-bold text-zinc-900 mb-4">Bienvenue sur PMS ! 👋</h2>
                        <p class="text-zinc-500 max-w-sm mx-auto mb-6">
                            Votre agence est créée. Pour profiter pleinement de l'outil, prenons 2 minutes pour
                            configurer votre premier bien locatif.
                        </p>
                        <ul class="text-left text-sm text-zinc-600 space-y-3 mb-8">
                            <li class="flex items-center gap-3">
                                <div class="bg-indigo-100 p-1.5 rounded-full text-indigo-600">
                                    <flux:icon name="building-office" class="size-4" />
                                </div>
                                Créer une propriété
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="bg-emerald-100 p-1.5 rounded-full text-emerald-600">
                                    <flux:icon name="home" class="size-4" />
                                </div>
                                Ajouter un locataire
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="bg-amber-100 p-1.5 rounded-full text-amber-600">
                                    <flux:icon name="document-text" class="size-4" />
                                </div>
                                Générer un bail automatiquement
                            </li>
                        </ul>
                    </div>

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
                            <flux:select label="Type de propriété" wire:model="propertyType">
                                @foreach(\App\Enums\PropertyType::cases() as $type)
                                    <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                                @endforeach
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

                        <div
                            class="w-full bg-zinc-50 rounded-xl p-6 text-left border border-zinc-200 shadow-sm transition-all hover:shadow-md">
                            <h3
                                class="font-semibold text-zinc-900 mb-4 text-xs uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1 h-3 bg-indigo-500 rounded-full"></span>
                                Récapitulatif de votre configuration
                            </h3>

                            <div class="space-y-4">
                                <!-- Property Info -->
                                <div
                                    class="flex items-start gap-4 p-3 bg-white rounded-lg border border-zinc-100 ring-1 ring-zinc-50">
                                    <div class="mt-1 bg-blue-50 p-2 rounded-lg text-blue-600">
                                        <flux:icon name="building-office-2" class="size-4" />
                                    </div>
                                    <div>
                                        <div class="text-2xs font-bold text-zinc-400 uppercase tracking-tighter mb-0.5">
                                            Propriété</div>
                                        <div class="text-sm font-medium text-zinc-900">{{ $propertyName }}</div>
                                        <div class="text-xs text-zinc-500">{{ $propertyAddress }}</div>
                                    </div>
                                </div>

                                <!-- Unit Info -->
                                <div
                                    class="flex items-start gap-4 p-3 bg-white rounded-lg border border-zinc-100 ring-1 ring-zinc-50">
                                    <div class="mt-1 bg-emerald-50 p-2 rounded-lg text-emerald-600">
                                        <flux:icon name="home" class="size-4" />
                                    </div>
                                    <div>
                                        <div class="text-2xs font-bold text-zinc-400 uppercase tracking-tighter mb-0.5">
                                            Unité</div>
                                        <div class="text-sm font-medium text-zinc-900">{{ $unitName }}</div>
                                        <div class="text-xs text-zinc-500">
                                            {{ number_format((float) $unitRent, 0, ',', ' ') }} FCFA / mois</div>
                                    </div>
                                </div>

                                <!-- Renter Info (Optional) -->
                                @if($renterFirstName)
                                    <div
                                        class="flex items-start gap-4 p-3 bg-white rounded-lg border border-zinc-100 ring-1 ring-zinc-50">
                                        <div class="mt-1 bg-violet-50 p-2 rounded-lg text-violet-600">
                                            <flux:icon name="user" class="size-4" />
                                        </div>
                                        <div>
                                            <div class="text-2xs font-bold text-zinc-400 uppercase tracking-tighter mb-0.5">
                                                Premier Locataire</div>
                                            <div class="text-sm font-medium text-zinc-900">{{ $renterFirstName }}
                                                {{ $renterLastName }}</div>
                                            <div class="text-xs text-zinc-500">{{ $renterEmail ?: 'Pas d\'email' }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="bg-zinc-50 px-8 py-5 mt-6 border-t border-zinc-100 flex items-center justify-between">
                @if($currentStep > 0 && $currentStep < 4)
                    <flux:button variant="ghost" wire:click="previousStep">Retour</flux:button>
                @else
                    <div></div>
                @endif

                @if($currentStep < 4)
                    <div class="flex gap-3">
                        @if($currentStep === 3)
                            <flux:button variant="ghost" wire:click="skip" wire:loading.attr="disabled" wire:target="skip">
                                Passer cette étape
                            </flux:button>
                        @endif
                        <flux:button variant="filled" wire:click="nextStep" wire:loading.attr="disabled"
                            wire:target="nextStep">
                            <span wire:loading.remove wire:target="nextStep">
                                @if($currentStep === 0)
                                    Commencer
                                @elseif($currentStep === 3)
                                    Terminer
                                @else
                                    Continuer
                                @endif
                            </span>
                            <span wire:loading wire:target="nextStep">
                                <flux:icon name="cog-6-tooth" class="animate-spin size-4" />
                            </span>
                        </flux:button>
                    </div>
                @else
                    <flux:button variant="filled" wire:click="finish" class="w-full justify-center"
                        wire:loading.attr="disabled" wire:target="finish">
                        <span wire:loading.remove wire:target="finish">Accéder au tableau de bord</span>
                        <span wire:loading wire:target="finish">
                            <flux:icon name="cog-6-tooth" class="animate-spin size-4" />
                        </span>
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