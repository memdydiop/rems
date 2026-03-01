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
    public function plans()
    {
        $interval = $this->billingCycle === 'monthly' ? 'monthly' : 'annually';

        return Plan::where('interval', $interval)
            ->where('is_public', true)
            ->orderBy('amount', 'asc')
            ->get();
    }

    #[Livewire\Attributes\Computed]
    public function allFeatures()
    {
        $featureLabels = [
            'max_properties' => 'Propriétés max',
            'max_users' => 'Utilisateurs max',
            'rent_tracking' => 'Suivi des loyers',
            'renter_portal' => 'Portail locataire',
            'maintenance_requests' => 'Demandes de maintenance',
            'online_payments' => 'Paiements en ligne',
            'expense_management' => 'Gestion des dépenses',
            'tenant_screening' => 'Sélection des locataires',
            'owner_portals' => 'Portails propriétaires',
            'multi_user_roles' => 'Rôles multi-utilisateurs',
            'priority_support' => 'Support prioritaire',
            'api_access' => 'Accès API',
        ];

        $keys = collect();
        foreach ($this->plans as $plan) {
            $features = $plan->features ?? [];
            foreach (array_keys($features) as $key) {
                $keys->push($key);
            }
        }

        return $keys->unique()->map(function ($key) use ($featureLabels) {
            return [
                'key' => $key,
                'label' => $featureLabels[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key)),
            ];
        })->values()->toArray();
    }


}; ?>

<!-- Add Alpine Intersection Plugin -->
@push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <style>
        .glass-header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }

        .hero-gradient {
            background: linear-gradient(135deg, #031b4e 0%, #0152c2 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-glow {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(1, 98, 232, 0.4) 0%, rgba(0, 0, 0, 0) 70%);
            top: -200px;
            right: -100px;
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
        }
    </style>
@endpush

<div class="antialiased font-sans text-zinc-900 bg-zinc-50 scroll-smooth">

    <!-- Navbar -->
    <header class="fixed top-0 w-full z-[100] transition-all duration-300 py-4"
        x-data="{ scrolled: window.scrollY > 10 }"
        x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 10 })"
        :class="scrolled ? 'glass-header py-3! shadow-sm' : 'bg-transparent'">

        <div class="container mx-auto px-6 flex justify-between items-center transition-all duration-300">
            <!-- Logo -->
            <div class="flex items-center gap-2 relative z-10">
                <a href="/" class="flex items-center gap-2 group">
                    <img :src="scrolled ? '{{ asset('img/brand-logos/toggle-logo.png') }}' : '{{ asset('img/brand-logos/toggle-white.png') }}'"
                        alt="Logo" class="h-6 transition-transform duration-300 group-hover:scale-105">
                </a>
            </div>

            <!-- Main Menu -->
            <nav class="hidden lg:flex items-center gap-2 bg-white/10 backdrop-blur-md px-6 py-2 rounded-full border border-white/20"
                :class="scrolled ? 'bg-zinc-100/50! border-zinc-200/50!' : ''">
                <a href="#home"
                    class="px-3 py-1.5 text-[0.9rem] font-medium transition-colors hover:text-[rgb(0,185,255)]"
                    :class="scrolled ? 'text-zinc-600' : 'text-white/90'">
                    Accueil
                </a>
                <a href="#features"
                    class="px-3 py-1.5 text-[0.9rem] font-medium transition-colors hover:text-[rgb(0,185,255)]"
                    :class="scrolled ? 'text-zinc-600' : 'text-white/90'">
                    Fonctionnalités
                </a>
                <a href="#pricing"
                    class="px-3 py-1.5 text-[0.9rem] font-medium transition-colors hover:text-[rgb(0,185,255)]"
                    :class="scrolled ? 'text-zinc-600' : 'text-white/90'">
                    Tarifs
                </a>
                <a href="#contact"
                    class="px-3 py-1.5 text-[0.9rem] font-medium transition-colors hover:text-[rgb(0,185,255)]"
                    :class="scrolled ? 'text-zinc-600' : 'text-white/90'">
                    Contact
                </a>
            </nav>

            <!-- Auth Buttons -->
            <div class="flex items-center gap-3 relative z-10" x-data="{ shown: false }"
                x-init="setTimeout(() => shown = true, 500)" x-show="shown" x-transition.opacity.duration.1000ms
                x-cloak>
                @auth
                    <a href="{{ route('central.dashboard') }}"
                        class="px-5 py-2.5 rounded-full bg-[rgb(1,98,232)] text-white text-sm font-semibold hover:bg-[#0152c2] transition-colors shadow-[0_0_15px_rgba(1,98,232,0.4)] hover:shadow-[0_0_25px_rgba(1,98,232,0.6)]">
                        Mon Tableau de bord
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="hidden sm:inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-colors"
                        :class="scrolled ? 'text-zinc-700 hover:text-[rgb(1,98,232)]' : 'text-white hover:text-blue-200'">
                        Connexion
                    </a>

                    <flux:modal.trigger name="request-access">
                        <button wire:click="openGenericModal"
                            class="px-6 py-2.5 rounded-full bg-[rgb(0,185,255)] text-white text-sm font-bold hover:bg-[#009ac9] transition-all duration-300 shadow-[0_0_20px_rgba(0,185,255,0.4)] hover:shadow-[0_0_30px_rgba(0,185,255,0.6)] hover:-translate-y-0.5">
                            Démarrer gratuitement
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

    <!-- Modern SaaS Hero Section -->
    <div class="hero-gradient relative min-h-[90vh] flex items-center pt-24 pb-20 lg:pt-32 lg:pb-32" id="home"
        x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 100)">
        <div class="hero-glow"></div>
        <div
            class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik0wIDBoNDB2NDBIMHoiIGZpbGw9Im5vbmUiLz4KPHBhdGggZD0iTTAgMGgxdjEwTDB2MTB6bTEwIDB2MWgtMTB2LTFoMTB6IiBmaWxsPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMDMpIi8+Cjwvc3ZnPg==')] opacity-50 z-0">
        </div>

        <div class="container mx-auto px-6 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Text Content -->
                <div class="max-w-2xl text-center lg:text-left mx-auto lg:mx-0" x-show="shown"
                    x-transition:enter="transition ease-out duration-1000 delay-100"
                    x-transition:enter-start="opacity-0 translate-y-8"
                    x-transition:enter-end="opacity-100 translate-y-0" x-cloak>

                    <div
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-500/20 text-blue-200 border border-blue-400/30 text-sm font-medium mb-6">
                        <span class="flex h-2 w-2 rounded-full bg-blue-400 animate-pulse"></span>
                        Le standard de la nouvelle génération
                    </div>

                    <h1
                        class="text-5xl lg:text-6xl xl:text-7xl font-extrabold text-white tracking-tight leading-[1.1] mb-6 drop-shadow-sm">
                        Gérez vos biens.<br />
                        <span
                            class="text-transparent bg-clip-text bg-linear-to-r from-[rgb(0,185,255)] to-[rgb(1,98,232)]">Élevez
                            vos revenus.</span>
                    </h1>

                    <p
                        class="text-lg lg:text-xl text-blue-100/80 mb-10 leading-relaxed font-light max-w-xl mx-auto lg:mx-0">
                        Propella est la plateforme unifiée qui combine paiements locatifs, gestion de baux et portails
                        dédiés pour libérer le plein potentiel de votre agence.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                        <flux:modal.trigger name="request-access">
                            <button wire:click="openGenericModal"
                                class="w-full sm:w-auto px-8 py-4 rounded-full bg-white text-[rgb(3,27,78)] font-bold text-lg hover:bg-zinc-50 transition-all duration-300 shadow-[0_0_30px_rgba(255,255,255,0.2)] hover:shadow-[0_0_40px_rgba(255,255,255,0.4)] hover:-translate-y-1">
                                Essai gratuit 14 jours
                            </button>
                        </flux:modal.trigger>
                        <a href="#features"
                            class="w-full sm:w-auto px-8 py-4 rounded-full bg-blue-600/30 text-white font-medium border border-blue-400/30 hover:bg-blue-600/50 backdrop-blur-sm transition-all text-lg flex items-center justify-center gap-2 group">
                            Découvrir les visuels
                            <flux:icon name="arrow-down"
                                class="size-5 group-hover:translate-y-1 transition-transform" />
                        </a>
                    </div>

                    <div
                        class="mt-10 flex items-center justify-center lg:justify-start gap-4 text-sm text-blue-200/60 font-medium">
                        <div class="flex items-center gap-1">
                            <flux:icon name="check-circle" class="size-4 text-[rgb(0,185,255)]" /> Sans engagement
                        </div>
                        <div class="flex items-center gap-1">
                            <flux:icon name="check-circle" class="size-4 text-[rgb(0,185,255)]" /> Carte non requise
                        </div>
                    </div>
                </div>

                <!-- Mockup Illustration -->
                <div class="hidden lg:block relative" x-show="shown"
                    x-transition:enter="transition ease-out duration-1000 delay-300"
                    x-transition:enter-start="opacity-0 translate-x-12"
                    x-transition:enter-end="opacity-100 translate-x-0" x-cloak>
                    <!-- Abstract Glassmorphism Dashboard Mockup -->
                    <div
                        class="relative w-full aspect-4/3 rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 shadow-2xl p-4 overflow-hidden transform rotate-2 hover:rotate-0 transition-transform duration-700">
                        <!-- Mac OS Window dots -->
                        <div class="flex items-center gap-2 mb-6">
                            <div class="size-3 rounded-full bg-rose-400/80"></div>
                            <div class="size-3 rounded-full bg-amber-400/80"></div>
                            <div class="size-3 rounded-full bg-emerald-400/80"></div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 h-full">
                            <!-- Sidebar mock -->
                            <div class="col-span-1 rounded-xl bg-white/5 space-y-3 p-3">
                                <div class="h-8 rounded bg-white/10 w-full mb-6"></div>
                                <div class="h-4 rounded bg-white/10 w-3/4"></div>
                                <div class="h-4 rounded bg-[rgb(0,185,255)]/40 w-full"></div>
                                <div class="h-4 rounded bg-white/10 w-5/6"></div>
                                <div class="h-4 rounded bg-white/10 w-2/3"></div>
                            </div>

                            <!-- Main content mock -->
                            <div class="col-span-2 space-y-4">
                                <!-- Cards -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div
                                        class="h-24 rounded-xl bg-linear-to-br from-white/10 to-white/5 border border-white/10 p-3">
                                        <div class="size-8 rounded-full bg-blue-400/30 mb-2"></div>
                                        <div class="h-4 rounded bg-white/20 w-1/2"></div>
                                    </div>
                                    <div
                                        class="h-24 rounded-xl bg-linear-to-br from-white/10 to-white/5 border border-white/10 p-3">
                                        <div class="size-8 rounded-full bg-emerald-400/30 mb-2"></div>
                                        <div class="h-4 rounded bg-white/20 w-2/3"></div>
                                    </div>
                                </div>

                                <!-- Chart mock -->
                                <div
                                    class="h-32 rounded-xl bg-white/5 border border-white/10 p-4 relative overflow-hidden">
                                    <svg class="absolute bottom-0 w-full h-24 text-[rgb(0,185,255)]/30"
                                        viewBox="0 0 100 100" preserveAspectRatio="none">
                                        <path d="M0,100 L0,50 Q25,20 50,60 T100,30 L100,100 Z" fill="currentColor" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

    <!-- Features Bento Grid -->
    <section id="features" class="py-24 bg-zinc-50 relative z-10" style="font-family: 'Roboto', sans-serif;">
        <div class="container mx-auto px-6 max-w-7xl">
            <div class="text-center max-w-3xl mx-auto mb-20" x-data="{ shown: false }" x-intersect.once="shown = true">
                <div x-show="shown" x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 translate-y-8"
                    x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                    <span
                        class="text-[rgb(1,98,232)] font-bold tracking-wider uppercase text-sm mb-3 block">Fonctionnalités
                        Clés</span>
                    <h2 class="text-4xl md:text-5xl font-extrabold text-zinc-900 mb-6 tracking-tight">Un écosystème
                        conçu pour la performance</h2>
                    <p class="text-lg text-zinc-500 font-light">
                        De la collecte des loyers à la maintenance automatisée, Propella fluidifie chaque aspect de
                        votre métier.
                    </p>
                </div>
            </div>

            <!-- Bento Grid Wrapper -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" x-data="{ shown: false }"
                x-intersect.once="shown = true">

                <!-- BENTO 1: Multi-Tenant (Span 2) -->
                <div class="md:col-span-2 bg-white rounded-3xl p-8 lg:p-10 border border-zinc-200/60 shadow-xl shadow-zinc-200/20 hover:shadow-2xl hover:border-[rgb(1,98,232)]/30 transition-all duration-300 group relative overflow-hidden flex flex-col justify-between min-h-75"
                    x-show="shown" x-transition:enter="transition ease-out duration-700 delay-100"
                    x-transition:enter-start="opacity-0 translate-y-12"
                    x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                    <div
                        class="absolute right-0 top-0 w-64 h-64 bg-blue-50/50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform duration-500">
                    </div>
                    <div>
                        <div
                            class="w-14 h-14 bg-blue-100/50 rounded-2xl flex items-center justify-center text-[rgb(1,98,232)] mb-6 group-hover:bg-[rgb(1,98,232)] group-hover:text-white transition-colors duration-300 shadow-sm">
                            <flux:icon name="building-office-2" class="size-7" />
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 mb-3 tracking-tight">Architecture Multi-Tenant</h3>
                        <p class="text-zinc-500 leading-relaxed max-w-md">Gérez vos différentes sous-agences ou
                            multiples portefeuilles dans des espaces de travail cloisonnés et parfaitement sécurisés
                            depuis un seul compte.</p>
                    </div>
                </div>

                <!-- BENTO 2: Compta (Span 1) -->
                <div class="md:col-span-1 bg-white rounded-3xl p-8 lg:p-10 border border-zinc-200/60 shadow-xl shadow-zinc-200/20 hover:shadow-2xl hover:border-pink-500/30 transition-all duration-300 group relative overflow-hidden flex flex-col justify-between"
                    x-show="shown" x-transition:enter="transition ease-out duration-700 delay-200"
                    x-transition:enter-start="opacity-0 translate-y-12"
                    x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                    <div
                        class="absolute right-0 top-0 w-40 h-40 bg-pink-50/50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform duration-500">
                    </div>
                    <div>
                        <div
                            class="w-14 h-14 bg-pink-100/50 rounded-2xl flex items-center justify-center text-pink-600 mb-6 group-hover:bg-pink-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                            <flux:icon name="banknotes" class="size-7" />
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 mb-3 tracking-tight">Comptabilité & Quittances</h3>
                        <p class="text-zinc-500 leading-relaxed">Génération automatique des quittances et rappels de
                            paiements intelligents.</p>
                    </div>
                </div>

                <!-- BENTO 3: Maintenance (Span 1) -->
                <div class="md:col-span-1 bg-white rounded-3xl p-8 lg:p-10 border border-zinc-200/60 shadow-xl shadow-zinc-200/20 hover:shadow-2xl hover:border-purple-500/30 transition-all duration-300 group relative overflow-hidden flex flex-col justify-between"
                    x-show="shown" x-transition:enter="transition ease-out duration-700 delay-300"
                    x-transition:enter-start="opacity-0 translate-y-12"
                    x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                    <div
                        class="absolute inset-0 bg-linear-to-b from-transparent to-purple-50/20 opacity-0 group-hover:opacity-100 transition-opacity">
                    </div>
                    <div>
                        <div
                            class="w-14 h-14 bg-purple-100/50 rounded-2xl flex items-center justify-center text-purple-600 mb-6 group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                            <flux:icon name="wrench-screwdriver" class="size-7" />
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 mb-3 tracking-tight">Maintenance Auto</h3>
                        <p class="text-zinc-500 leading-relaxed">Les locataires créent des tickets, vous suivez
                            l'avancée avec vos prestataires.</p>
                    </div>
                </div>

                <!-- BENTO 4: Portails Clients (Span 2) -->
                <div class="md:col-span-2 bg-white rounded-3xl p-8 lg:p-10 border border-zinc-200/60 shadow-xl shadow-zinc-200/20 hover:shadow-2xl hover:border-teal-500/30 transition-all duration-300 group relative overflow-hidden flex flex-col lg:flex-row gap-6 items-center justify-between min-h-75"
                    x-show="shown" x-transition:enter="transition ease-out duration-700 delay-400"
                    x-transition:enter-start="opacity-0 translate-y-12"
                    x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                    <div
                        class="absolute left-0 bottom-0 w-64 h-64 bg-teal-50/50 rounded-tr-full -z-10 group-hover:scale-110 transition-transform duration-500">
                    </div>
                    <div class="flex-1">
                        <div
                            class="w-14 h-14 bg-teal-100/50 rounded-2xl flex items-center justify-center text-teal-600 mb-6 group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                            <flux:icon name="users" class="size-7" />
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 mb-3 tracking-tight">Portails Dédiés</h3>
                        <p class="text-zinc-500 leading-relaxed">Offrez une expérience premium. Vos locataires accèdent
                            à leurs baux et factures ; vos propriétaires suivent la rentabilité en temps réel.</p>
                    </div>
                    <div class="hidden lg:block w-48 relative">
                        <!-- Tiny mock graphic -->
                        <div
                            class="bg-zinc-50 rounded-xl p-4 border border-zinc-100 shadow-inner rotate-3 group-hover:rotate-6 transition-transform">
                            <div class="flex gap-2 mb-3">
                                <div class="size-8 rounded-full bg-teal-100"></div>
                                <div class="space-y-1.5 flex-1 pt-1">
                                    <div class="h-2 bg-zinc-200 rounded w-full"></div>
                                    <div class="h-2 bg-zinc-200 rounded w-2/3"></div>
                                </div>
                            </div>
                            <div
                                class="h-16 bg-white rounded border border-zinc-100 mt-2 flex items-center justify-center">
                                <flux:icon name="document-text" class="size-6 text-teal-400/50" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BENTO 5: Analytique (Span 2) -->
                <div class="md:col-span-2 bg-white rounded-3xl p-8 lg:p-10 border border-zinc-200/60 shadow-xl shadow-zinc-200/20 hover:shadow-2xl hover:border-orange-500/30 transition-all duration-300 group relative overflow-hidden flex flex-col justify-between"
                    x-show="shown" x-transition:enter="transition ease-out duration-700 delay-500"
                    x-transition:enter-start="opacity-0 translate-y-12"
                    x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                    <div
                        class="absolute right-0 top-0 w-40 h-40 bg-orange-50/50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform duration-500">
                    </div>
                    <div>
                        <div
                            class="w-14 h-14 bg-orange-100/50 rounded-2xl flex items-center justify-center text-orange-600 mb-6 group-hover:bg-orange-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                            <flux:icon name="chart-bar" class="size-7" />
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 mb-3 tracking-tight">Analytique & Performance</h3>
                        <p class="text-zinc-500 leading-relaxed max-w-sm">Décisions basées sur les données. Suivez
                            précisément vos taux d'occupation, revenus et impayés via des tableaux de bord dynamiques.
                        </p>
                    </div>
                </div>

                <!-- BENTO 6: Sécurité (Span 1) -->
                <div class="md:col-span-1 bg-white rounded-3xl p-8 lg:p-10 border border-zinc-200/60 shadow-xl shadow-zinc-200/20 hover:shadow-2xl hover:border-cyan-500/30 transition-all duration-300 group relative overflow-hidden flex flex-col justify-between"
                    x-show="shown" x-transition:enter="transition ease-out duration-700 delay-600"
                    x-transition:enter-start="opacity-0 translate-y-12"
                    x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                    <div
                        class="absolute right-0 bottom-0 w-32 h-32 bg-cyan-50/50 rounded-tl-full -z-10 group-hover:scale-110 transition-transform duration-500">
                    </div>
                    <div>
                        <div
                            class="w-14 h-14 bg-cyan-100/50 rounded-2xl flex items-center justify-center text-cyan-600 mb-6 group-hover:bg-cyan-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                            <flux:icon name="shield-check" class="size-7" />
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 mb-3 tracking-tight">Haute Sécurité</h3>
                        <p class="text-zinc-500 leading-relaxed">Chiffrement de bout en bout et sauvegardes garanties.
                        </p>
                    </div>
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

            <!-- Pricing Grid Container -->
            <div class="max-w-6xl mx-auto mt-12" x-data="{ shown: false }" x-intersect.once="shown = true">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center" x-show="shown"
                    x-transition:enter="transition ease-out duration-700 delay-200"
                    x-transition:enter-start="opacity-0 translate-y-12"
                    x-transition:enter-end="opacity-100 translate-y-0" x-cloak>

                    @foreach ($this->plans as $plan)
                        @php
                            $isPremium = $loop->last; // Assuming last plan is Premium
                            $baseClass = $isPremium ? 'relative bg-white border-2 border-[rgb(1,98,232)] shadow-[0_0_40px_rgba(1,98,232,0.15)] lg:scale-105 z-10' : 'bg-white border border-zinc-200 hover:border-[rgb(1,98,232)]/50 shadow-xl shadow-zinc-200/40 hover:-translate-y-1';
                            $wrapperClass = "p-8 rounded-3xl flex flex-col h-full transition-all duration-300 $baseClass";
                            $titleColor = $isPremium ? 'text-[rgb(1,98,232)]' : 'text-zinc-900';

                            // Map icons based on index or name
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
                                        Plan Populaire
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
                                @foreach ($this->allFeatures as $feature)
                                    @php
                                        $dbKey = $feature['key'];
                                        $value = $plan->features[$dbKey] ?? null;
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
                                            {{ $feature['label'] }}
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