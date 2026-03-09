<div class="inline-block relative">
    @if(!$hasSeen)
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-zinc-900/60 z-90 backdrop-blur-sm transition-opacity" wire:click="dismiss"></div>
    @endif

    <!-- Target Element Wrapper -->
    <div class="{{ !$hasSeen ? 'relative z-100' : '' }}">
        {{ $slot }}

        @if(!$hasSeen)
                @php
                    $alignClasses = match ($align) {
                        'bottom' => 'top-full left-0 mt-4',
                        'top' => 'bottom-full left-0 mb-4',
                        'right' => 'left-full top-0 ml-4',
                        'left' => 'right-full top-0 mr-4',
                        'bottom-right' => 'top-full right-0 mt-4',
                        default => 'top-full left-0 mt-4',
                    };
                @endphp

                <!-- Tooltip Popover -->
                <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 300)" x-show="show"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    class="absolute {{ $alignClasses }} w-80 bg-white rounded-2xl shadow-2xl border border-zinc-100 p-5 z-100"
                    style="display: none;">
                    <div class="flex items-start gap-4">
                        <div class="size-10 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                            <flux:icon name="sparkles" class="size-5 text-[rgb(1,98,232)]" />
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-zinc-900 mb-1">{{ $title }}</h4>
                            <p class="text-[0.85rem] text-zinc-500 leading-relaxed mb-4">{{ $description }}</p>

                            <div class="flex items-center justify-between mb-4">
                                <div class="flex gap-1">
                                    @for($i = 1; $i <= $totalSteps; $i++)
                                        <div
                                            class="h-1 w-6 rounded-full {{ $i <= $currentStepNumber ? 'bg-[rgb(1,98,232)]' : 'bg-zinc-100' }}">
                                        </div>
                                    @endfor
                                </div>
                                <span class="text-2xs font-bold text-zinc-400 uppercase tracking-wider">Étape
                                    {{ $currentStepNumber }}/{{ $totalSteps }}</span>
                            </div>

                            <div class="flex items-center gap-3">
                                <flux:button size="sm" variant="primary"
                                    class="w-full bg-[rgb(1,98,232)] hover:bg-[#0152c2] text-white shadow-lg shadow-blue-200"
                                    wire:click="dismiss">
                                    {{ $currentStepNumber === $totalSteps ? 'Terminer' : 'Suivant' }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pulse Effect around target -->
                class="absolute -inset-x-2 -inset-y-2 border-2 border-[rgb(1,98,232)] rounded-xl animate-ping opacity-30
                pointer-events-none">
            </div>
        @endif
</div>
</div>