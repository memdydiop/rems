<?php

use App\Models\Lead;
use App\Models\Plan;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component {
    public $email = '';
    public $password = ''; // Added password property
    public $company = '';
    public $name = '';
    public $sent = false;

    public $billingCycle = 'monthly';
    public $selectedPlan = null;

    public $subdomain = '';

    public function selectPlan($planName)
    {
        $this->selectedPlan = $planName;
    }

    public function openGenericModal()
    {
        $this->reset('selectedPlan');
    }

    public function registerTenant()
    {
        $this->validate([
            'company' => 'required|string|max:255',
            'subdomain' => 'required|string|max:50|alpha_dash|unique:tenants,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8', // Added validation
        ], [
            'subdomain.unique' => 'Ce sous-domaine est déjà pris.',
        ]);

        try {
            \App\Jobs\CreateTenantJob::dispatchSync(
                $this->company,
                $this->subdomain,
                $this->name,
                $this->email,
                $this->password, // Pass password
                $this->selectedPlan ?? 'Starter'
            );

            $centralDomain = config('tenancy.central_domains')[0] ?? 'localhost';
            $port = request()->getPort();
            $url = "http://{$this->subdomain}.{$centralDomain}" . ($port !== 80 ? ":{$port}" : "") . "/login";
            return redirect($url);
        } catch (\Exception $e) {
            $this->addError('subdomain', 'Une erreur est survenue: ' . $e->getMessage());
        }
    }

    #[Livewire\Attributes\Computed]
    public function allFeatures()
    {
        return [
            'Propriétés max',
            'Utilisateurs max',
            'Suivi manuel des loyers',
            'Portail locataire basique',
            'Demandes de maintenance',
            'Paiements en ligne',
            'Gestion des dépenses',
            'Sélection des locataires',
            'Portails propriétaires',
            'Rôles multi-utilisateurs',
            'Support prioritaire',
            'Accès API',
        ];
    }

    #[Livewire\Attributes\Computed]
    public function plans()
    {
        $interval = $this->billingCycle === 'monthly' ? 'monthly' : 'annually';

        return Plan::where('interval', $interval)
            ->whereIn('name', match ($this->billingCycle) {
                'monthly' => ['Starter', 'Croissance', 'Entreprise'],
                'yearly' => ['Starter (Annuel)', 'Croissance (Annuel)', 'Entreprise (Annuel)'],
        })
            ->orderBy('amount', 'asc')
            ->get();
    }


}; ?>

<div class="">

    <!-- Navbar -->
    <!-- Navbar -->
    <!-- Navbar (Valex Style) -->
    <header class="fixed top-0 w-full z-[100] transition-all duration-300 !bg-transparent py-4"
        x-data="{ scrolled: window.scrollY > 10 }"
        x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 10 })"
        :class="{ '!bg-white !shadow-md': scrolled }">

        <div class="container mx-auto px-6 flex justify-between items-center transition-all duration-300">
            <!-- Logo -->
            <div class="flex items-center gap-2">
                <a href="/" class="flex items-center gap-2">
                    <img :src="scrolled ? '{{ asset('img/brand-logos/toggle-logo.png') }}' : '{{ asset('img/brand-logos/toggle-white.png') }}'"
                        alt="Logo" class="h-4 transition-all duration-300">
                </a>
            </div>

            <!-- Main Menu -->
            <nav class="hidden lg:flex items-center gap-1">
                <a href="#home" class="px-4 py-2 text-[0.85rem] font-medium transition-colors flex items-center gap-1"
                    :class="scrolled ? 'text-[#536485] hover:text-[rgb(1,98,232)]' : 'text-white/90 hover:text-white'">
                    Home
                </a>
                <a href="#features"
                    class="px-4 py-2 text-[0.85rem] font-medium transition-colors flex items-center gap-1"
                    :class="scrolled ? 'text-[#536485] hover:text-[rgb(1,98,232)]' : 'text-white/90 hover:text-white'">
                    Features
                </a>
                <a href="#pricing"
                    class="px-4 py-2 text-[0.85rem] font-medium transition-colors flex items-center gap-1"
                    :class="scrolled ? 'text-[#536485] hover:text-[rgb(1,98,232)]' : 'text-white/90 hover:text-white'">
                    Pricing
                </a>
                <div class="relative group" x-data="{ open: false }">
                    <button @mouseenter="open = true" @mouseleave="open = false"
                        class="px-4 py-2 text-[0.85rem] font-medium transition-colors flex items-center gap-1"
                        :class="scrolled ? 'text-[#536485] hover:text-[rgb(1,98,232)]' : 'text-white/90 hover:text-white'">
                        More
                        <flux:icon name="chevron-down" class="w-3 h-3 opacity-75" />
                    </button>
                    <!-- Dropdown mock -->
                    <div x-show="open" @mouseenter="open = true" @mouseleave="open = false" x-transition.opacity
                        class="absolute top-full left-0 mt-0 w-48 bg-white shadow-lg rounded-sm py-2 border-t-2 border-[rgb(1,98,232)] hidden group-hover:block">
                        <a href="#"
                            class="block px-4 py-2 text-sm text-gray-600 hover:text-[rgb(1,98,232)] hover:bg-blue-50">About
                            Us</a>
                        <a href="#"
                            class="block px-4 py-2 text-sm text-gray-600 hover:text-[rgb(1,98,232)] hover:bg-blue-50">Services</a>
                        <a href="#"
                            class="block px-4 py-2 text-sm text-gray-600 hover:text-[rgb(1,98,232)] hover:bg-blue-50">Contact</a>
                    </div>
                </div>
                <a href="#contact"
                    class="px-4 py-2 text-[0.85rem] font-medium transition-colors flex items-center gap-1"
                    :class="scrolled ? 'text-[#536485] hover:text-[rgb(1,98,232)]' : 'text-white/90 hover:text-white'">
                    Contact
                </a>
            </nav>

            <!-- Auth Buttons -->
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('central.dashboard') }}"
                        class="px-5 py-2 rounded-[4px] bg-[rgb(1,98,232)] text-white text-sm font-medium hover:bg-[#0152c2] transition-colors shadow-sm shadow-blue-200">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="hidden sm:inline-flex items-center justify-center w-10 h-9 rounded-[4px] bg-[#ecf0fa] text-[#031b4e] hover:bg-blue-100 transition-colors">
                        <flux:icon name="arrow-right-start-on-rectangle" class="w-4 h-4" />
                    </a>

                    <flux:modal.trigger name="request-access">
                        <button wire:click="openGenericModal"
                            class="px-6 py-2 rounded-[4px] bg-[rgb(1,98,232)] text-white text-sm font-medium hover:bg-[#0152c2] transition-colors shadow-md shadow-blue-500/20">
                            Sign Up
                        </button>
                    </flux:modal.trigger>
                @endauth

                <!-- Mobile Menu Button -->
                <button class="lg:hidden p-2"
                    :class="scrolled ? 'text-gray-600 hover:text-[rgb(1,98,232)]' : 'text-white hover:text-blue-200'">
                    <flux:icon name="bars-3" class="w-6 h-6" />
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section (Valex Style) -->
    <div class="landing-banner relative before:absolute before:inset-0 before:bg-[rgb(1,98,232)]/70 before:content-['']"
        id="home"
        style="background: url('{{ asset('img/hero-bg.jpg') }}'); background-size: cover; background-position: center; font-family: 'Roboto', sans-serif;">
        <section class="section !pt-[6rem] pb-20 relative z-10">
            <div class="container mx-auto px-6 !pt-3 sm:!pt-[6rem]">
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xxl:col-span-2 xl:col-span-2 lg:col-span-2 md:col-span-2 col-span-1 hidden md:block">
                    </div>
                    <div class="xxl:col-span-8 xl:col-span-8 lg:col-span-8 md:col-span-8 col-span-12">
                        <div class="py-4 pb-lg-0 text-center">
                            <div class="mb-3">
                                <h5 class="font-semibold text-white/90 text-[1.25rem] op-9 mb-2">Logiciel de Gestion
                                    Locative</h5>
                            </div>
                            <p
                                class="text-[2.5rem] md:text-[3rem] font-semibold text-white mb-3 cursor-default leading-tight">
                                Simplifiez votre gestion immobilière avec <span
                                    class="text-[rgb(1,98,232)]">Propella</span>
                            </p>
                            <div class="text-[1rem] mb-8 text-white/70 max-w-2xl mx-auto">
                                Une solution tout-en-un pur automatiser vos quittances, suivre vos paiements et gérer
                                vos locataires sans effort.
                                Concentrez-vous sur l'essentiel : votre croissance.
                            </div>

                            <div class="flex flex-wrap justify-center gap-2">
                                <a href="#features"
                                    class="m-1 px-6 py-2 rounded-[6px] bg-[rgb(1,98,232)] text-white hover:bg-[#0152c2] transition-colors font-medium flex items-center gap-2 shadow-lg">
                                    Découvrir
                                    <flux:icon name="eye" class="w-4 h-4" />
                                </a>
                                <flux:modal.trigger name="request-access">
                                    <button wire:click="openGenericModal"
                                        class="m-1 px-6 py-2 rounded-[6px] bg-[rgb(0,185,255)] text-white hover:bg-[#009ac9] transition-colors font-medium flex items-center gap-2 shadow-lg">
                                        Commencer
                                        <flux:icon name="arrow-right" class="w-4 h-4" />
                                    </button>
                                </flux:modal.trigger>
                            </div>
                        </div>
                    </div>
                    <div class="xxl:col-span-2 xl:col-span-2 lg:col-span-2 md:col-span-2 col-span-1 hidden md:block">
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Social Proof -->
    <section class="py-10 border-y border-zinc-100 bg-zinc-50/50">
        <div class="container mx-auto px-6 text-center">
            <p class="text-sm font-semibold text-zinc-400 uppercase tracking-widest mb-8">Approuvé par des agences
                visionnaires</p>
            <div
                class="flex flex-wrap justify-center items-center gap-12 opacity-60 grayscale hover:grayscale-0 transition-all duration-500">
                <!-- Placeholders for logos -->
                <div class="text-2xl font-bold font-serif text-zinc-800">Acme Living</div>
                <div class="text-2xl font-bold text-zinc-800 italic">Skyline Props</div>
                <div class="text-2xl font-bold font-mono text-zinc-800">URBAN&CO</div>
                <div class="text-2xl font-bold text-zinc-800 tracking-tighter">HAVEN</div>
                <div class="text-2xl font-bold font-serif italic text-zinc-800">EstateMinds</div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section id="features" class="py-24 bg-[#ecf0fa] relative z-10" style="font-family: 'Roboto', sans-serif;">
        <div class="container mx-auto px-6">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <span class="text-blue-600 font-bold tracking-wider uppercase text-sm mb-2 block">Fonctionnalités
                    Principales</span>
                <h2 class="text-4xl font-bold text-zinc-900 mb-6">Tout pour votre Agence</h2>
                <div class="w-20 h-1 bg-blue-600 mx-auto rounded-full mb-6"></div>
                <p class="text-lg text-zinc-500">Propella gère le travail répétitif pour que vous puissiez vous
                    concentrer sur l'essentiel.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white p-6 rounded-[6px] text-center transition-all duration-300 group hover:-translate-y-1"
                    style="box-shadow: rgba(218, 222, 232, 0.4) -8px 12px 18px 0px; border: 1px solid #f0f0f0;">
                    <div
                        class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 mb-6 mx-auto group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                        <flux:icon name="building-office-2" class="w-8 h-8" />
                    </div>
                    <h3 class="text-xl font-bold text-[rgb(3,27,78)] mb-3">Multi-Tenant</h3>
                    <p class="text-zinc-500 leading-relaxed">Gérez plusieurs portefeuilles ou sous-agences depuis un
                        tableau de bord maître.</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white p-6 rounded-[6px] text-center transition-all duration-300 group hover:-translate-y-1"
                    style="box-shadow: rgba(218, 222, 232, 0.4) -8px 12px 18px 0px; border: 1px solid #f0f0f0;">
                    <div
                        class="w-16 h-16 bg-pink-100 rounded-lg flex items-center justify-center text-pink-600 mb-6 mx-auto group-hover:bg-pink-600 group-hover:text-white transition-colors duration-300">
                        <flux:icon name="banknotes" class="w-8 h-8" />
                    </div>
                    <h3 class="text-xl font-bold text-[rgb(3,27,78)] mb-3">Comptabilité Auto</h3>
                    <p class="text-zinc-500 leading-relaxed">Rapprochement automatique des loyers et génération des
                        rapports financiers.</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white p-6 rounded-[6px] text-center transition-all duration-300 group hover:-translate-y-1"
                    style="box-shadow: rgba(218, 222, 232, 0.4) -8px 12px 18px 0px; border: 1px solid #f0f0f0;">
                    <div
                        class="w-16 h-16 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600 mb-6 mx-auto group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300">
                        <flux:icon name="wrench" class="w-8 h-8" />
                    </div>
                    <h3 class="text-xl font-bold text-[rgb(3,27,78)] mb-3">Maintenance</h3>
                    <p class="text-zinc-500 leading-relaxed">Système de tickets intégré. Les locataires signalent, vous
                        assignez.</p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white p-6 rounded-[6px] text-center transition-all duration-300 group hover:-translate-y-1"
                    style="box-shadow: rgba(218, 222, 232, 0.4) -8px 12px 18px 0px; border: 1px solid #f0f0f0;">
                    <div
                        class="w-16 h-16 bg-teal-100 rounded-lg flex items-center justify-center text-teal-600 mb-6 mx-auto group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300">
                        <flux:icon name="users" class="w-8 h-8" />
                    </div>
                    <h3 class="text-xl font-bold text-[rgb(3,27,78)] mb-3">Portails Clients</h3>
                    <p class="text-zinc-500 leading-relaxed">Espaces dédiés pour vos locataires et propriétaires.</p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white p-6 rounded-[6px] text-center transition-all duration-300 group hover:-translate-y-1"
                    style="box-shadow: rgba(218, 222, 232, 0.4) -8px 12px 18px 0px; border: 1px solid #f0f0f0;">
                    <div
                        class="w-16 h-16 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 mb-6 mx-auto group-hover:bg-orange-600 group-hover:text-white transition-colors duration-300">
                        <flux:icon name="chart-bar" class="w-8 h-8" />
                    </div>
                    <h3 class="text-xl font-bold text-[rgb(3,27,78)] mb-3">Analytique</h3>
                    <p class="text-zinc-500 leading-relaxed">Tableaux de bord en temps réel. Suivez vos taux
                        d'occupation.</p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white p-6 rounded-[6px] text-center transition-all duration-300 group hover:-translate-y-1"
                    style="box-shadow: rgba(218, 222, 232, 0.4) -8px 12px 18px 0px; border: 1px solid #f0f0f0;">
                    <div
                        class="w-16 h-16 bg-cyan-100 rounded-lg flex items-center justify-center text-cyan-600 mb-6 mx-auto group-hover:bg-cyan-600 group-hover:text-white transition-colors duration-300">
                        <flux:icon name="shield-check" class="w-8 h-8" />
                    </div>
                    <h3 class="text-xl font-bold text-[rgb(3,27,78)] mb-3">Sécurité</h3>
                    <p class="text-zinc-500 leading-relaxed">Données chiffrées, sauvegardes automatiques et respect des
                        normes RGPD.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <!-- Pricing Section -->
    <section id="pricing" class="py-24 bg-white relative z-10" x-data="{ cycle: @entangle('billingCycle').live }">
        <div class="container mx-auto px-6 text-center">

            <p class="text-[0.75rem] font-semibold text-[rgb(1,98,232)] mb-1 uppercase tracking-wider">NOS TARIFS</p>
            <h3 class="font-semibold mb-2 text-2xl text-zinc-900">Choisissez le plan qui vous convient</h3>

            <div class="flex justify-center mb-5">
                <p class="text-[#8c9097] text-[0.9375rem] font-normal max-w-2xl">
                    Nos plans sont conçus pour s'adapter à chaque étape de votre croissance.
                    Commencez petit, voyez grand.
                </p>
            </div>

            <!-- Toggle -->
            <div class="flex justify-center mb-8">
                <nav class="bg-[rgba(1,98,232,0.1)] p-1 rounded-md inline-flex" aria-label="Tabs" role="tablist">
                    <button wire:click="$set('billingCycle', 'monthly')"
                        class="py-2 px-6 text-sm font-medium rounded-sm transition-all duration-200"
                        :class="cycle === 'monthly' ? 'bg-[rgb(1,98,232)] text-white shadow-sm' : 'text-[rgb(1,98,232)] hover:text-[#0152c2]'">
                        Mensuel
                    </button>
                    <button wire:click="$set('billingCycle', 'yearly')"
                        class="py-2 px-6 text-sm font-medium rounded-sm transition-all duration-200"
                        :class="cycle === 'yearly' ? 'bg-[rgb(1,98,232)] text-white shadow-sm' : 'text-[rgb(1,98,232)] hover:text-[#0152c2]'">
                        Annuel
                    </button>
                </nav>
            </div>

            <!-- Pricing Grid Container (Flat Box) -->
            <div class="max-w-7xl mx-auto border border-zinc-200 rounded-lg overflow-hidden bg-white shadow-none">
                <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-zinc-100">

                    @foreach ($this->plans as $plan)
                        @php
                            $isPremium = $loop->last; // Assuming last plan is Premium
                            // Add flex flex-col h-full to ensure full height and vertical stacking
                            $baseClass = $isPremium ? 'relative overflow-hidden bg-zinc-50/30' : 'hover:bg-zinc-50/50 transition-colors';
                            $wrapperClass = "p-8 flex flex-col h-full $baseClass";
                            $titleColor = $isPremium ? 'text-[rgb(1,98,232)]' : 'text-zinc-900';

                            // Map icons based on index or name
                            $iconName = match ($loop->index) {
                                0 => 'tag',
                                1 => 'hand-thumb-up',
                                2 => 'sparkles',
                                default => 'tag'
                            };
                        @endphp

                        <div class="{{ $wrapperClass }}">
                            @if($isPremium)
                                <!-- Ribbon -->
                                <div class="absolute top-0 right-0">
                                    <div
                                        class="bg-[rgb(1,98,232)] text-white text-[0.65rem] font-bold px-8 py-1 transform rotate-45 translate-x-8 translate-y-3 shadow-sm z-20">
                                        10% Off
                                    </div>
                                </div>
                            @endif

                            <h6 class="font-semibold text-center text-lg {{ $titleColor }} mb-4 uppercase tracking-wider">
                                {{ strtoupper($plan->name) }}
                            </h6>

                            <div class="py-6 flex items-center justify-center gap-6">
                                <div class="p-3 bg-[rgba(1,98,232,0.1)] rounded-full text-[rgb(1,98,232)]">
                                    <flux:icon :name="$iconName" class="w-6 h-6" />
                                </div>
                                <div class="text-right">
                                    <span class="text-[1.5625rem] font-semibold mb-0 text-zinc-900">
                                        {{ $plan->formatted_price }}
                                    </span>
                                    <span class="text-[#8c9097] text-xs font-semibold mb-0">
                                        / {{ $plan->interval === 'monthly' ? 'mois' : 'an' }}
                                    </span>
                                </div>
                            </div>

                            <ul class="space-y-4 mb-8 mt-6 text-[0.85rem] text-left px-4">
                                @foreach ($this->allFeatures as $featureKey)
                                    @php
                                        // Map display names to DB keys
                                        $keyMap = [
                                            'Propriétés max' => 'max_properties',
                                            'Utilisateurs max' => 'max_users',
                                        ];

                                        $dbKey = $keyMap[$featureKey] ?? $featureKey;

                                        // Check if feature exists in plan
                                        $value = $plan->features[$dbKey] ?? null;
                                        // Available if it exists AND is not strictly false
                                        $isAvailable = ($value !== null && $value !== false);
                                    @endphp

                                    <li
                                        class="flex items-center gap-3 {{ $isAvailable ? 'text-zinc-600' : 'text-zinc-400 opacity-60' }}">
                                        @if ($isAvailable)
                                            <div class="flex-shrink-0 p-0.5 rounded-full bg-blue-50 text-[rgb(1,98,232)]">
                                                <flux:icon name="check" class="w-3.5 h-3.5" />
                                            </div>
                                        @else
                                            <div class="flex-shrink-0 p-0.5 relative top-px">
                                                <flux:icon name="x-mark" class="w-4 h-4 text-zinc-300" />
                                            </div>
                                        @endif

                                        <span class="flex-1 truncate">
                                            {{ $featureKey }}
                                        </span>

                                        {{-- Limit Value --}}
                                        @if ($isAvailable && is_numeric($value))
                                            <span
                                                class="bg-blue-50 text-[rgb(1,98,232)] px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide">
                                                {{ $value == -1 ? 'Illimité' : $value }}
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>

                            <flux:modal.trigger name="request-access">
                                <button wire:click="selectPlan('{{ $plan->name }}')"
                                    class="w-full py-2.5 px-4 bg-[rgb(1,98,232)] text-white font-medium rounded-[5px] hover:bg-blue-600 transition-colors shadow-md shadow-blue-500/20 mt-auto">
                                    Essai Gratuit
                                </button>
                            </flux:modal.trigger>
                        </div>
                    @endforeach

                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-16">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                <!-- Brand -->
                <div class="col-span-1 lg:col-span-1">
                    <div class="flex items-center gap-2 mb-6">
                        <div
                            class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-blue-500/20">
                            P
                        </div>
                        <span class="text-2xl font-bold text-white">Propella</span>
                    </div>
                    <p class="text-gray-400 mb-6 leading-relaxed">
                        La solution #1 pour les gestionnaires immobiliers modernes. Simplifiez, automatisez, grandissez.
                    </p>
                    <div class="flex gap-4">
                        <a href="#"
                            class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-blue-600 hover:text-white transition-all">
                            <flux:icon name="camera" class="w-5 h-5" />
                        </a>
                        <a href="#"
                            class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-blue-600 hover:text-white transition-all">
                            <flux:icon name="paper-airplane" class="w-5 h-5" />
                        </a>
                    </div>
                </div>

                <!-- Product -->
                <div>
                    <h4 class="text-white font-bold text-lg mb-6">Produit</h4>
                    <ul class="space-y-4">
                        <li><a href="#" class="hover:text-blue-400 transition-colors">Tournée des fonctionnalités</a>
                        </li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">Tarifs</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">Portail Locataire</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">Portail Propriétaire</a></li>
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h4 class="text-white font-bold text-lg mb-6">Société</h4>
                    <ul class="space-y-4">
                        <li><a href="#" class="hover:text-blue-400 transition-colors">À propos</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">Carrières</a> <span
                                class="text-xs bg-blue-600 text-white px-2 py-0.5 rounded ml-1">Recrutement</span></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">Blog</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">Contact</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-white font-bold text-lg mb-6">Contact</h4>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-4">
                            <flux:icon name="map-pin" class="w-6 h-6 text-blue-500 mt-1" />
                            <span>123 Boulevard de l'Innovation,<br>Abidjan, Côte d'Ivoire</span>
                        </li>
                        <li class="flex items-center gap-4">
                            <flux:icon name="envelope" class="w-5 h-5 text-blue-500" />
                            <span>hello@propella.ci</span>
                        </li>
                        <li class="flex items-center gap-4">
                            <flux:icon name="phone" class="w-5 h-5 text-blue-500" />
                            <span>+225 07 07 07 07 07</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} Propella Inc. Tous droits réservés.</p>
                <div class="flex gap-8 text-sm text-gray-500">
                    <a href="#" class="hover:text-white transition-colors">Confidentialité</a>
                    <a href="#" class="hover:text-white transition-colors">CGU</a>
                    <a href="#" class="hover:text-white transition-colors">Sécurité</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Application Modal -->
    <flux:modal name="request-access" class="md:w-[400px]">
        <div class="space-y-6">
            <div>
                <h2 class="text-lg font-bold text-gray-900">
                    {{ $selectedPlan ? "Démarrer avec {$selectedPlan}" : 'Démarrer avec Propella' }}
                </h2>
                <p class="text-sm text-gray-500">Remplissez le formulaire ci-dessous pour demander une démo et commencer
                    votre essai gratuit de 14 jours.</p>
            </div>

            @if($sent)
                <div class="flex flex-col items-center gap-4 py-8 bg-green-50 rounded-xl border border-green-100">
                    <div class="rounded-full bg-green-100 p-3 text-green-600">
                        <flux:icon name="check" class="size-6" />
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-medium text-zinc-900">Demande envoyée !</h3>
                        <p class="text-zinc-500 text-sm mt-1">Nous vous contacterons sur <strong>{{ $email }}</strong> sous
                            peu.
                        </p>
                    </div>
                    <div class="w-full">
                        <flux:modal.close>
                            <flux:button variant="ghost" class="w-full mt-2" type="button">Fermer</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            @else
                <form wire:submit="registerTenant" class="space-y-4">
                    <flux:input wire:model="company" label="Nom de l'entreprise" placeholder="Acme Living" required />

                    <div>
                        <flux:input wire:model="subdomain" label="Sous-domaine souhaité" placeholder="acme" required
                            prefix="https://" suffix=".propella.ci" />
                        <p class="text-xs text-gray-500 mt-1">L'adresse de votre espace de travail.</p>
                    </div>

                    <flux:input wire:model="name" label="Personne à contacter" placeholder="Jane Doe" required />
                    <flux:input wire:model="email" label="Email professionnel" type="email" placeholder="jane@acme.com"
                        required />

                    <div class="relative" x-data="{ showPassword: false }">
                        <flux:input wire:model="password" label="Mot de passe" type="password"
                            x-bind:type="showPassword ? 'text' : 'password'" placeholder="********" required />
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-3 top-[2.2rem] text-gray-400 hover:text-gray-600 focus:outline-none"
                            tabindex="-1">
                            <flux:icon name="eye" x-show="!showPassword" class="size-5" />
                            <flux:icon name="eye-slash" x-show="showPassword" x-cloak class="size-5" />
                        </button>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <flux:modal.close>
                            <flux:button variant="ghost" type="button">Annuler</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Démarrer l'essai gratuit</flux:button>
                    </div>
                </form>
            @endif
        </div>
    </flux:modal>
</div>