{{-- ═══════════════════════════════════════════════════════════════
TÉMOIGNAGES & PREUVE SOCIALE
═══════════════════════════════════════════════════════════════ --}}
<section class="py-20 lg:py-28 bg-white" x-data="{ shown: false }" x-intersect.once="shown = true">
    <div class="container mx-auto px-6 max-w-6xl">

        <div class="text-center mb-16" x-show="shown" x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0"
            x-cloak>
            <span class="text-[rgb(1,98,232)] font-bold tracking-wider uppercase text-sm mb-3 block">Ils nous font
                confiance</span>
            <h2 class="text-3xl md:text-4xl font-extrabold text-zinc-900 mb-4 tracking-tight">
                Des gestionnaires comme vous l'utilisent au quotidien
            </h2>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-16" x-show="shown"
            x-transition:enter="transition ease-out duration-700 delay-100"
            x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0"
            x-cloak>
            <div class="text-center p-6 rounded-2xl bg-zinc-50 border border-zinc-100">
                <div class="text-3xl font-extrabold text-[rgb(1,98,232)] mb-1">2 500+</div>
                <div class="text-sm text-zinc-500 font-medium">Biens gérés</div>
            </div>
            <div class="text-center p-6 rounded-2xl bg-zinc-50 border border-zinc-100">
                <div class="text-3xl font-extrabold text-[rgb(1,98,232)] mb-1">98%</div>
                <div class="text-sm text-zinc-500 font-medium">Satisfaction client</div>
            </div>
            <div class="text-center p-6 rounded-2xl bg-zinc-50 border border-zinc-100">
                <div class="text-3xl font-extrabold text-[rgb(1,98,232)] mb-1">4h</div>
                <div class="text-sm text-zinc-500 font-medium">Gagnées par semaine</div>
            </div>
            <div class="text-center p-6 rounded-2xl bg-zinc-50 border border-zinc-100">
                <div class="text-3xl font-extrabold text-[rgb(1,98,232)] mb-1">120+</div>
                <div class="text-sm text-zinc-500 font-medium">Agences actives</div>
            </div>
        </div>

        {{-- Testimonials --}}
        <div class="grid md:grid-cols-3 gap-8" x-show="shown"
            x-transition:enter="transition ease-out duration-700 delay-300"
            x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0"
            x-cloak>

            <div class="bg-zinc-50 rounded-2xl p-8 border border-zinc-100 hover:shadow-lg transition-all relative">
                <div class="text-[rgb(1,98,232)]/20 text-6xl font-serif absolute top-4 right-6 leading-none">"</div>
                <p class="text-zinc-600 leading-relaxed mb-6 relative z-10">
                    « Gain de temps énorme sur mes <strong>15 lots</strong>. Les quittances se génèrent toutes seules,
                    je n'ai plus qu'à valider. Mon comptable est ravi. »
                </p>
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-[rgb(1,98,232)] font-bold text-sm">
                        AK</div>
                    <div>
                        <div class="font-semibold text-zinc-900 text-sm">Aminata K.</div>
                        <div class="text-zinc-400 text-xs">Gérante · 15 lots à Abidjan</div>
                    </div>
                </div>
            </div>

            <div class="bg-zinc-50 rounded-2xl p-8 border border-zinc-100 hover:shadow-lg transition-all relative">
                <div class="text-[rgb(1,98,232)]/20 text-6xl font-serif absolute top-4 right-6 leading-none">"</div>
                <p class="text-zinc-600 leading-relaxed mb-6 relative z-10">
                    « Mes locataires adorent le portail. Ils consultent leurs factures et signalent les pannes
                    directement. Fini les appels incessants ! »
                </p>
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-600 font-bold text-sm">
                        MD</div>
                    <div>
                        <div class="font-semibold text-zinc-900 text-sm">Moussa D.</div>
                        <div class="text-zinc-400 text-xs">Directeur · Agence Skyline Props</div>
                    </div>
                </div>
            </div>

            <div class="bg-zinc-50 rounded-2xl p-8 border border-zinc-100 hover:shadow-lg transition-all relative">
                <div class="text-[rgb(1,98,232)]/20 text-6xl font-serif absolute top-4 right-6 leading-none">"</div>
                <p class="text-zinc-600 leading-relaxed mb-6 relative z-10">
                    « En 3 mois, j'ai réduit mes impayés de <strong>40%</strong> grâce aux relances automatiques.
                    L'investissement est rentabilisé dès le premier mois. »
                </p>
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold text-sm">
                        FT</div>
                    <div>
                        <div class="font-semibold text-zinc-900 text-sm">Fatou T.</div>
                        <div class="text-zinc-400 text-xs">Propriétaire · 42 lots à Dakar</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>