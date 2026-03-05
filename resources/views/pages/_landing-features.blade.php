{{-- ═══════════════════════════════════════════════════════════════
FONCTIONNALITÉS — Bento Grid orienté bénéfices
═══════════════════════════════════════════════════════════════ --}}
<section id="features" class="py-24 bg-zinc-50 relative z-10">
    <div class="container mx-auto px-6 max-w-7xl">
        <div class="text-center max-w-3xl mx-auto mb-20" x-data="{ shown: false }" x-intersect.once="shown = true">
            <div x-show="shown" x-transition:enter="transition ease-out duration-700"
                x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak>
                <span class="text-[rgb(1,98,232)] font-bold tracking-wider uppercase text-sm mb-3 block">Ce que vous
                    gagnez</span>
                <h2 class="text-4xl md:text-5xl font-extrabold text-zinc-900 mb-6 tracking-tight">
                    Moins de tâches manuelles,<br>plus de résultats
                </h2>
                <p class="text-lg text-zinc-500 font-light">
                    Chaque fonctionnalité est conçue pour vous faire gagner du temps et maximiser vos revenus locatifs.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" x-data="{ shown: false }" x-intersect.once="shown = true">

            {{-- Multi-Tenant (Span 2) --}}
            <div class="md:col-span-2 bg-white rounded-3xl p-8 lg:p-10 border border-zinc-200/60 shadow-xl shadow-zinc-200/20 hover:shadow-2xl hover:border-[rgb(1,98,232)]/30 transition-all duration-300 group relative overflow-hidden flex flex-col justify-between min-h-75"
                x-show="shown" x-transition:enter="transition ease-out duration-700 delay-100"
                x-transition:enter-start="opacity-0 translate-y-12" x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak>
                <div
                    class="absolute right-0 top-0 w-64 h-64 bg-blue-50/50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform duration-500">
                </div>
                <div>
                    <div
                        class="w-14 h-14 bg-blue-100/50 rounded-2xl flex items-center justify-center text-[rgb(1,98,232)] mb-6 group-hover:bg-[rgb(1,98,232)] group-hover:text-white transition-colors duration-300 shadow-sm">
                        <flux:icon name="building-office-2" class="size-7" />
                    </div>
                    <h3 class="text-2xl font-bold text-zinc-900 mb-3 tracking-tight">Centralisez tous vos portefeuilles
                    </h3>
                    <p class="text-zinc-500 leading-relaxed max-w-md">Gérez plusieurs agences ou portefeuilles dans des
                        espaces cloisonnés. Un seul compte, une vue d'ensemble complète sur tous vos biens.</p>
                </div>
            </div>

            {{-- Comptabilité (Span 1) --}}
            <div class="md:col-span-1 bg-white rounded-3xl p-8 lg:p-10 border border-zinc-200/60 shadow-xl shadow-zinc-200/20 hover:shadow-2xl hover:border-pink-500/30 transition-all duration-300 group relative overflow-hidden flex flex-col justify-between"
                x-show="shown" x-transition:enter="transition ease-out duration-700 delay-200"
                x-transition:enter-start="opacity-0 translate-y-12" x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak>
                <div
                    class="absolute right-0 top-0 w-40 h-40 bg-pink-50/50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform duration-500">
                </div>
                <div>
                    <div
                        class="w-14 h-14 bg-pink-100/50 rounded-2xl flex items-center justify-center text-pink-600 mb-6 group-hover:bg-pink-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                        <flux:icon name="banknotes" class="size-7" />
                    </div>
                    <h3 class="text-2xl font-bold text-zinc-900 mb-3 tracking-tight">Zéro quittance manuelle</h3>
                    <p class="text-zinc-500 leading-relaxed">Génération automatique des quittances et relances
                        intelligentes des retards de paiement.</p>
                </div>
            </div>

            {{-- Maintenance (Span 1) --}}
            <div class="md:col-span-1 bg-white rounded-3xl p-8 lg:p-10 border border-zinc-200/60 shadow-xl shadow-zinc-200/20 hover:shadow-2xl hover:border-purple-500/30 transition-all duration-300 group relative overflow-hidden flex flex-col justify-between"
                x-show="shown" x-transition:enter="transition ease-out duration-700 delay-300"
                x-transition:enter-start="opacity-0 translate-y-12" x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak>
                <div
                    class="absolute inset-0 bg-linear-to-b from-transparent to-purple-50/20 opacity-0 group-hover:opacity-100 transition-opacity">
                </div>
                <div>
                    <div
                        class="w-14 h-14 bg-purple-100/50 rounded-2xl flex items-center justify-center text-purple-600 mb-6 group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                        <flux:icon name="wrench-screwdriver" class="size-7" />
                    </div>
                    <h3 class="text-2xl font-bold text-zinc-900 mb-3 tracking-tight">Interventions sous contrôle</h3>
                    <p class="text-zinc-500 leading-relaxed">Vos locataires signalent un problème, vous suivez
                        l'intervention du prestataire. Tout est tracé.</p>
                </div>
            </div>

            {{-- Portails (Span 2) --}}
            <div class="md:col-span-2 bg-white rounded-3xl p-8 lg:p-10 border border-zinc-200/60 shadow-xl shadow-zinc-200/20 hover:shadow-2xl hover:border-teal-500/30 transition-all duration-300 group relative overflow-hidden flex flex-col lg:flex-row gap-6 items-center justify-between min-h-75"
                x-show="shown" x-transition:enter="transition ease-out duration-700 delay-400"
                x-transition:enter-start="opacity-0 translate-y-12" x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak>
                <div
                    class="absolute left-0 bottom-0 w-64 h-64 bg-teal-50/50 rounded-tr-full -z-10 group-hover:scale-110 transition-transform duration-500">
                </div>
                <div class="flex-1">
                    <div
                        class="w-14 h-14 bg-teal-100/50 rounded-2xl flex items-center justify-center text-teal-600 mb-6 group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                        <flux:icon name="users" class="size-7" />
                    </div>
                    <h3 class="text-2xl font-bold text-zinc-900 mb-3 tracking-tight">Vos locataires autonomes</h3>
                    <p class="text-zinc-500 leading-relaxed">Portail dédié : vos locataires consultent baux et factures,
                        vos propriétaires suivent la rentabilité. Moins de coups de fil, plus de transparence.</p>
                </div>
                <div class="hidden lg:block w-48 relative">
                    <div
                        class="bg-zinc-50 rounded-xl p-4 border border-zinc-100 shadow-inner rotate-3 group-hover:rotate-6 transition-transform">
                        <div class="flex gap-2 mb-3">
                            <div class="size-8 rounded-full bg-teal-100"></div>
                            <div class="space-y-1.5 flex-1 pt-1">
                                <div class="h-2 bg-zinc-200 rounded w-full"></div>
                                <div class="h-2 bg-zinc-200 rounded w-2/3"></div>
                            </div>
                        </div>
                        <div class="h-16 bg-white rounded border border-zinc-100 mt-2 flex items-center justify-center">
                            <flux:icon name="document-text" class="size-6 text-teal-400/50" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Analytique (Span 2) --}}
            <div class="md:col-span-2 bg-white rounded-3xl p-8 lg:p-10 border border-zinc-200/60 shadow-xl shadow-zinc-200/20 hover:shadow-2xl hover:border-orange-500/30 transition-all duration-300 group relative overflow-hidden flex flex-col justify-between"
                x-show="shown" x-transition:enter="transition ease-out duration-700 delay-500"
                x-transition:enter-start="opacity-0 translate-y-12" x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak>
                <div
                    class="absolute right-0 top-0 w-40 h-40 bg-orange-50/50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform duration-500">
                </div>
                <div>
                    <div
                        class="w-14 h-14 bg-orange-100/50 rounded-2xl flex items-center justify-center text-orange-600 mb-6 group-hover:bg-orange-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                        <flux:icon name="chart-bar" class="size-7" />
                    </div>
                    <h3 class="text-2xl font-bold text-zinc-900 mb-3 tracking-tight">Rentabilité visible en un coup
                        d'œil</h3>
                    <p class="text-zinc-500 leading-relaxed max-w-sm">Taux d'occupation, revenus, impayés : des tableaux
                        de bord dynamiques pour prendre les bonnes décisions.</p>
                </div>
            </div>

            {{-- Sécurité (Span 1) --}}
            <div class="md:col-span-1 bg-white rounded-3xl p-8 lg:p-10 border border-zinc-200/60 shadow-xl shadow-zinc-200/20 hover:shadow-2xl hover:border-cyan-500/30 transition-all duration-300 group relative overflow-hidden flex flex-col justify-between"
                x-show="shown" x-transition:enter="transition ease-out duration-700 delay-600"
                x-transition:enter-start="opacity-0 translate-y-12" x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak>
                <div
                    class="absolute right-0 bottom-0 w-32 h-32 bg-cyan-50/50 rounded-tl-full -z-10 group-hover:scale-110 transition-transform duration-500">
                </div>
                <div>
                    <div
                        class="w-14 h-14 bg-cyan-100/50 rounded-2xl flex items-center justify-center text-cyan-600 mb-6 group-hover:bg-cyan-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                        <flux:icon name="shield-check" class="size-7" />
                    </div>
                    <h3 class="text-2xl font-bold text-zinc-900 mb-3 tracking-tight">Vos données protégées</h3>
                    <p class="text-zinc-500 leading-relaxed">Chiffrement de bout en bout, sauvegardes automatiques et
                        accès sécurisé pour chaque utilisateur.</p>
                </div>
            </div>
        </div>
    </div>
</section>