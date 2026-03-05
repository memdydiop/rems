{{-- ═══════════════════════════════════════════════════════════════
CTA FINAL — Rappel de l'offre avant le footer
═══════════════════════════════════════════════════════════════ --}}
<section class="py-20 lg:py-28 bg-linear-to-br from-[#031b4e] to-[#0152c2] relative overflow-hidden">
    <div
        class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik0wIDBoNDB2NDBIMHoiIGZpbGw9Im5vbmUiLz4KPHBhdGggZD0iTTAgMGgxdjEwTDB2MTB6bTEwIDB2MWgtMTB2LTFoMTB6IiBmaWxsPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMDMpIi8+Cjwvc3ZnPg==')] opacity-50">
    </div>

    <div class="container mx-auto px-6 text-center relative z-10" x-data="{ shown: false }"
        x-intersect.once="shown = true">
        <div x-show="shown" x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0"
            x-cloak>

            <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-white mb-6 tracking-tight">
                Prêt à simplifier votre gestion ?
            </h2>
            <p class="text-lg text-blue-100/80 font-light max-w-xl mx-auto mb-10">
                Rejoignez les agences qui gagnent du temps chaque jour. Essai gratuit de 14 jours, sans carte bancaire,
                sans engagement.
            </p>

            <flux:modal.trigger name="request-access">
                <button wire:click="openGenericModal"
                    class="px-10 py-4 rounded-full bg-white text-[rgb(3,27,78)] font-bold text-lg hover:bg-zinc-50 transition-all duration-300 shadow-[0_0_30px_rgba(255,255,255,0.2)] hover:shadow-[0_0_40px_rgba(255,255,255,0.4)] hover:-translate-y-1">
                    Créer mon compte gratuit
                </button>
            </flux:modal.trigger>

            <div class="mt-8 flex items-center justify-center gap-5 text-sm text-blue-200/60 font-medium">
                <div class="flex items-center gap-1.5">
                    <flux:icon name="check-circle" class="size-4 text-[rgb(0,185,255)]" /> Sans engagement
                </div>
                <div class="flex items-center gap-1.5">
                    <flux:icon name="check-circle" class="size-4 text-[rgb(0,185,255)]" /> Carte non requise
                </div>
            </div>
        </div>
    </div>
</section>