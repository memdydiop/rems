<div class="relative w-full" x-data="{ 
    open: @entangle('showResults'),
    init() {
        // Keyboard shortcut: Cmd+K / Ctrl+K
        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                this.$refs.searchInput.focus();
            }
            // Escape to close
            if (e.key === 'Escape') {
                this.open = false;
                $wire.closeResults();
            }
        });
    }
}" @click.away="open = false; $wire.closeResults()">

    <flux:input wire:model.live.debounce.300ms="query" x-ref="searchInput" icon="magnifying-glass"
        placeholder="{{ __('Rechercher...') }} (⌘K)"
        class="bg-zinc-50 border-none shadow-none focus:ring-1 focus:ring-accent/20" variant="filled"
        autocomplete="off" />

    <!-- Results Dropdown -->
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute top-full mt-2 w-full bg-white rounded-lg shadow-lg border border-zinc-200 max-h-128 overflow-y-auto z-50"
        style="display: none;">
        @if($query && $showResults)
            @php
                $hasResults = false;
                foreach ($results as $category => $items) {
                    if ($items->isNotEmpty()) {
                        $hasResults = true;
                        break;
                    }
                }
            @endphp

            @if($hasResults)
                @foreach($results as $category => $items)
                    @if($items->isNotEmpty())
                        <div class="p-2">
                            <div class="px-2 py-1 text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                                {{ ucfirst($category) }}
                            </div>
                            @foreach($items as $result)
                                <a href="{{ $result['url'] }}" wire:navigate
                                    class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-zinc-50 transition-colors group"
                                    @click="open = false; $wire.closeResults()">
                                    <div class="shrink-0">
                                        <div class="size-8 rounded-md bg-accent/10 flex items-center justify-center">
                                            <flux:icon :name="$result['icon']" class="size-4 text-accent" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-zinc-900 truncate">{{ $result['title'] }}</p>
                                        <p class="text-xs text-zinc-500 truncate">{{ $result['subtitle'] }}</p>
                                    </div>
                                    <div class="shrink-0">
                                        <flux:badge size="sm" color="zinc" inset="top bottom">{{ $result['type'] }}</flux:badge>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        @if(!$loop->last)
                            <div class="border-t border-zinc-100"></div>
                        @endif
                    @endif
                @endforeach
            @else
                <div class="p-8 text-center">
                    <flux:icon.magnifying-glass class="size-12 text-zinc-300 mx-auto mb-3" />
                    <p class="text-sm text-zinc-500">Aucun résultat pour "{{ $query }}"</p>
                    <p class="text-xs text-zinc-400 mt-1">Essayez une autre recherche</p>
                </div>
            @endif
        @endif
    </div>
</div>