<?php

use App\Models\Plan;
use Livewire\Attributes\{Layout, Computed};
use Livewire\Component;
use App\Jobs\CreateTenantJob;

new #[Layout('layouts.guest')] class extends Component {
    public $email = '';
    public $password = '';
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
            'password' => 'required|string|min:8',
        ], [
            'subdomain.unique' => 'Ce sous-domaine est déjà pris.',
        ]);

        try {
            CreateTenantJob::dispatchSync(
                $this->company,
                $this->subdomain,
                $this->name,
                $this->email,
                $this->password,
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

    #[Computed]
    public function plans()
    {
        $interval = $this->billingCycle === 'monthly' ? 'monthly' : 'annually';

        return Plan::where('interval', $interval)
            ->where('is_public', true)
            ->orderBy('amount', 'asc')
            ->get();
    }

    #[Computed]
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

}
; ?>

@push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <style>
        .glass-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
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

        .pain-card {
            background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%);
        }

        .solution-card {
            background: linear-gradient(135deg, #eff6ff 0%, #ecfdf5 100%);
        }
    </style>
@endpush

<div class="antialiased font-sans text-zinc-900 bg-zinc-50 scroll-smooth">

    {{-- ═══════════════════════════════════════════════════════════════
    HEADER — Minimal: Tarifs + Connexion seulement
    ═══════════════════════════════════════════════════════════════ --}}
    <header class="fixed top-0 w-full z-[100] transition-all duration-300 py-4 border-b border-white/5"
        x-data="{ scrolled: window.scrollY > 10 }"
        x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 10 })"
        :class="scrolled ? 'glass-header py-3! shadow-sm' : 'bg-transparent'">

        <div class="container mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center gap-2 relative z-10">
                <a href="/" class="flex items-center gap-2 group">
                    <img :src="scrolled ? '{{ asset('img/brand-logos/toggle-logo.png') }}' : '{{ asset('img/brand-logos/toggle-white.png') }}'"
                        alt="Logo" class="h-6 transition-transform duration-300 group-hover:scale-105">
                </a>
            </div>

            <div class="flex items-center gap-3 relative z-10" x-data="{ shown: false }"
                x-init="setTimeout(() => shown = true, 400)" x-show="shown" x-transition.opacity.duration.800ms x-cloak>
                <a href="#pricing" class="hidden sm:inline-flex px-4 py-2 text-sm font-medium transition-colors"
                    :class="scrolled ? 'text-zinc-600 hover:text-[rgb(1,98,232)]' : 'text-white/80 hover:text-white'">
                    Tarifs
                </a>
                @auth
                    <a href="{{ route('central.dashboard') }}"
                        class="px-5 py-2.5 rounded-full bg-[rgb(1,98,232)] text-white text-sm font-semibold hover:bg-[#0152c2] transition-colors shadow-[0_0_15px_rgba(1,98,232,0.4)]">
                        Mon Tableau de bord
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="hidden sm:inline-flex px-4 py-2 text-sm font-medium transition-colors"
                        :class="scrolled ? 'text-zinc-600 hover:text-[rgb(1,98,232)]' : 'text-white/80 hover:text-white'">
                        Connexion
                    </a>
                    <flux:modal.trigger name="request-access">
                        <button wire:click="openGenericModal"
                            class="px-6 py-2.5 rounded-full bg-[rgb(0,185,255)] text-white text-sm font-bold hover:bg-[#009ac9] transition-all duration-300 shadow-[0_0_20px_rgba(0,185,255,0.4)] hover:shadow-[0_0_30px_rgba(0,185,255,0.6)] hover:-translate-y-0.5">
                            Essai gratuit
                        </button>
                    </flux:modal.trigger>
                @endauth
            </div>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════════════════
    HERO — Titre orienté bénéfices + CTA clair
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="hero-gradient relative min-h-[92vh] flex items-center pt-24 pb-20 lg:pt-32 lg:pb-32" id="home"
        x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 100)">
        <div class="hero-glow"></div>
        <div
            class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik0wIDBoNDB2NDBIMHoiIGZpbGw9Im5vbmUiLz4KPHBhdGggZD0iTTAgMGgxdjEwTDB2MTB6bTEwIDB2MWgtMTB2LTFoMTB6IiBmaWxsPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMDMpIi8+Cjwvc3ZnPg==')] opacity-50 z-0">
        </div>

        <div class="container mx-auto px-6 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="max-w-2xl text-center lg:text-left mx-auto lg:mx-0" x-show="shown"
                    x-transition:enter="transition ease-out duration-1000 delay-100"
                    x-transition:enter-start="opacity-0 translate-y-8"
                    x-transition:enter-end="opacity-100 translate-y-0" x-cloak>

                    <div
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-500/20 text-blue-200 border border-blue-400/30 text-sm font-medium mb-6">
                        <span class="flex h-2 w-2 rounded-full bg-blue-400 animate-pulse"></span>
                        +2 500 biens gérés sur la plateforme
                    </div>

                    <h1
                        class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-extrabold text-white tracking-tight leading-[1.1] mb-6 drop-shadow-sm">
                        Tous vos biens.<br />
                        <span
                            class="text-transparent bg-clip-text bg-linear-to-r from-[rgb(0,185,255)] to-[rgb(1,98,232)]">
                            Une seule plateforme.</span>
                    </h1>

                    <p
                        class="text-lg lg:text-xl text-blue-100/80 mb-10 leading-relaxed font-light max-w-xl mx-auto lg:mx-0">
                        Automatisez vos quittances, gérez vos loyers et suivez vos locataires sans effort.
                        Essayez gratuitement pendant 14 jours.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                        <flux:modal.trigger name="request-access">
                            <button wire:click="openGenericModal"
                                class="w-full sm:w-auto px-8 py-4 rounded-full bg-white text-[rgb(3,27,78)] font-bold text-lg hover:bg-zinc-50 transition-all duration-300 shadow-[0_0_30px_rgba(255,255,255,0.2)] hover:shadow-[0_0_40px_rgba(255,255,255,0.4)] hover:-translate-y-1">
                                Créer mon compte gratuit
                            </button>
                        </flux:modal.trigger>
                        <a href="#features"
                            class="w-full sm:w-auto px-8 py-4 rounded-full bg-blue-600/30 text-white font-medium border border-blue-400/30 hover:bg-blue-600/50 backdrop-blur-sm transition-all text-lg flex items-center justify-center gap-2 group">
                            Découvrir
                            <flux:icon name="arrow-down"
                                class="size-5 group-hover:translate-y-1 transition-transform" />
                        </a>
                    </div>

                    <div
                        class="mt-10 flex items-center justify-center lg:justify-start gap-5 text-sm text-blue-200/60 font-medium">
                        <div class="flex items-center gap-1.5">
                            <flux:icon name="check-circle" class="size-4 text-[rgb(0,185,255)]" /> Sans engagement
                        </div>
                        <div class="flex items-center gap-1.5">
                            <flux:icon name="check-circle" class="size-4 text-[rgb(0,185,255)]" /> Carte non requise
                        </div>
                        <div class="flex items-center gap-1.5">
                            <flux:icon name="check-circle" class="size-4 text-[rgb(0,185,255)]" /> 14 jours gratuits
                        </div>
                    </div>
                </div>

                <!-- Dashboard Mockup -->
                <div class="hidden lg:block relative" x-show="shown"
                    x-transition:enter="transition ease-out duration-1000 delay-300"
                    x-transition:enter-start="opacity-0 translate-x-12"
                    x-transition:enter-end="opacity-100 translate-x-0" x-cloak>
                    <div
                        class="relative w-full aspect-4/3 rounded-2xl bg-white/10 backdrop-blur-xl border border-white/20 shadow-2xl p-4 overflow-hidden transform rotate-2 hover:rotate-0 transition-transform duration-700">
                        <div class="flex items-center gap-2 mb-6">
                            <div class="size-3 rounded-full bg-rose-400/80"></div>
                            <div class="size-3 rounded-full bg-amber-400/80"></div>
                            <div class="size-3 rounded-full bg-emerald-400/80"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 h-full">
                            <div class="col-span-1 rounded-xl bg-white/5 space-y-3 p-3">
                                <div class="h-8 rounded bg-white/10 w-full mb-6"></div>
                                <div class="h-4 rounded bg-white/10 w-3/4"></div>
                                <div class="h-4 rounded bg-[rgb(0,185,255)]/40 w-full"></div>
                                <div class="h-4 rounded bg-white/10 w-5/6"></div>
                                <div class="h-4 rounded bg-white/10 w-2/3"></div>
                            </div>
                            <div class="col-span-2 space-y-4">
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

    {{-- ═══════════════════════════════════════════════════════════════
    PROBLÈME / SOLUTION
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="py-20 lg:py-28 bg-white" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="container mx-auto px-6 max-w-5xl">
            <div class="text-center mb-16" x-show="shown" x-transition:enter="transition ease-out duration-700"
                x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak>
                <h2 class="text-3xl md:text-4xl font-extrabold text-zinc-900 mb-4 tracking-tight">
                    Marre des tableurs Excel et des quittances manuelles ?
                </h2>
                <p class="text-lg text-zinc-500 font-light max-w-2xl mx-auto">
                    Automatisez <strong class="text-zinc-700">90% de votre gestion administrative</strong> et
                    concentrez-vous sur ce qui compte : développer votre portefeuille.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-6" x-show="shown"
                x-transition:enter="transition ease-out duration-700 delay-200"
                x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak>

                <div
                    class="rounded-2xl p-6 border border-red-100 pain-card text-center group hover:shadow-lg transition-all">
                    <div
                        class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-500 mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <flux:icon name="document-text" class="size-6" />
                    </div>
                    <p class="text-sm font-semibold text-red-400 uppercase tracking-wider mb-2">Avant</p>
                    <p class="text-zinc-600 font-medium">Quittances créées à la main, une par une, dans Word ou Excel
                    </p>
                    <div class="my-4 flex justify-center">
                        <flux:icon name="arrow-down" class="size-5 text-zinc-300" />
                    </div>
                    <p class="text-sm font-semibold text-emerald-500 uppercase tracking-wider mb-2">Avec PMS</p>
                    <p class="text-zinc-700 font-medium">Génération <strong>automatique</strong> et envoi en un clic</p>
                </div>

                <div
                    class="rounded-2xl p-6 border border-red-100 pain-card text-center group hover:shadow-lg transition-all">
                    <div
                        class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-500 mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <flux:icon name="clock" class="size-6" />
                    </div>
                    <p class="text-sm font-semibold text-red-400 uppercase tracking-wider mb-2">Avant</p>
                    <p class="text-zinc-600 font-medium">Relances manuelles des locataires en retard de paiement</p>
                    <div class="my-4 flex justify-center">
                        <flux:icon name="arrow-down" class="size-5 text-zinc-300" />
                    </div>
                    <p class="text-sm font-semibold text-emerald-500 uppercase tracking-wider mb-2">Avec PMS</p>
                    <p class="text-zinc-700 font-medium">Relances <strong>automatiques</strong> et suivi en temps réel
                    </p>
                </div>

                <div
                    class="rounded-2xl p-6 border border-red-100 pain-card text-center group hover:shadow-lg transition-all">
                    <div
                        class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-500 mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <flux:icon name="chart-bar" class="size-6" />
                    </div>
                    <p class="text-sm font-semibold text-red-400 uppercase tracking-wider mb-2">Avant</p>
                    <p class="text-zinc-600 font-medium">Aucune visibilité sur la rentabilité de chaque bien</p>
                    <div class="my-4 flex justify-center">
                        <flux:icon name="arrow-down" class="size-5 text-zinc-300" />
                    </div>
                    <p class="text-sm font-semibold text-emerald-500 uppercase tracking-wider mb-2">Avec PMS</p>
                    <p class="text-zinc-700 font-medium">Tableaux de bord <strong>dynamiques</strong> par propriété</p>
                </div>
            </div>
        </div>
    </section>

    @include('pages._landing-features')
    @include('pages._landing-testimonials')
    @include('pages._landing-pricing')
    @include('pages._landing-cta')
    @include('pages._landing-footer')
    @include('pages._landing-modal')
</div>