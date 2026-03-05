{{-- ═══════════════════════════════════════════════════════════════
PRICING — Logique PHP inchangée, même toggle mensuel/annuel
═══════════════════════════════════════════════════════════════ --}}
<section id="pricing" class="py-24 bg-zinc-50 relative z-10" x-data="{ cycle: @entangle('billingCycle').live }">
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

        <!-- Pricing Grid -->
        <div class="max-w-6xl mx-auto mt-12" x-data="{ shown: false }" x-intersect.once="shown = true">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center" x-show="shown"
                x-transition:enter="transition ease-out duration-700 delay-200"
                x-transition:enter-start="opacity-0 translate-y-12" x-transition:enter-end="opacity-100 translate-y-0"
                x-cloak>

                @foreach ($this->plans as $plan)
                    @php
                        $isPremium = $loop->last;
                        $baseClass = $isPremium ? 'relative bg-white border-2 border-[rgb(1,98,232)] shadow-[0_0_40px_rgba(1,98,232,0.15)] lg:scale-105 z-10' : 'bg-white border border-zinc-200 hover:border-[rgb(1,98,232)]/50 shadow-xl shadow-zinc-200/40 hover:-translate-y-1';
                        $wrapperClass = "p-8 rounded-3xl flex flex-col h-full transition-all duration-300 $baseClass";
                        $titleColor = $isPremium ? 'text-[rgb(1,98,232)]' : 'text-zinc-900';
                        $iconName = match ($loop->index) {
                            0 => 'tag',
                            1 => 'hand-thumb-up',
                            2 => 'star',
                            default => 'tag'
                        };
                    @endphp

                    <div class="{{ $wrapperClass }}">
                        @if($isPremium)
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
                                    <span class="flex-1 truncate">{{ $feature['label'] }}</span>
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
                                Choisir ce plan
                            </button>
                        </flux:modal.trigger>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>