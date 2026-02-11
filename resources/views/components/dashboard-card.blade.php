@props([
    'title',
    'value',
    'icon',
    'color' => 'zinc',
    'trend' => null, // Optional: array ['value' => '+5.2%', 'label' => 'vs last month', 'positive' => true]
])

@php
    $colorData = match ($color) {
        'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-500', 'hover' => 'hover:shadow-[0_8px_30px_-4px_rgba(99,102,241,0.1)]', 'icon_bg' => 'bg-indigo-400/20'],
        'cyan' => ['bg' => 'bg-cyan-50', 'text' => 'text-cyan-500', 'hover' => 'hover:shadow-[0_8px_30px_-4px_rgba(6,182,212,0.1)]', 'icon_bg' => 'bg-cyan-400/20'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-500', 'hover' => 'hover:shadow-[0_8px_30px_-4px_rgba(16,185,129,0.1)]', 'icon_bg' => 'bg-emerald-400/20'],
        'orange' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-500', 'hover' => 'hover:shadow-[0_8px_30px_-4px_rgba(249,115,22,0.1)]', 'icon_bg' => 'bg-orange-400/20'],
        'red', 'rose' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-500', 'hover' => 'hover:shadow-[0_8px_30px_-4px_rgba(244,63,94,0.1)]', 'icon_bg' => 'bg-rose-400/20'],
        default => ['bg' => 'bg-zinc-50', 'text' => 'text-zinc-500', 'hover' => 'hover:shadow-lg', 'icon_bg' => 'bg-zinc-400/20'],
    };
@endphp

<x-flux::card :bg="$colorData['bg']" padding="p-6"
    class="border-0 relative overflow-hidden h-full group {{ $colorData['hover'] }} transition-all duration-300 rounded-[20px]">

    <div class="flex flex-col h-full relative z-10">
        <div class="flex justify-between items-center">
            <div class="flex flex-col">
                <span class="text-zinc-500 font-medium text-sm">{{ $title }}</span>
                <div class="text-3xl font-bold text-zinc-900 tracking-tight">
                    {{ $value }}
                </div>
            </div>
            <div class="{{ $colorData['icon_bg'] }} p-2.5 rounded backdrop-blur-sm group-hover:scale-105 transition-transform">
                <flux:icon :name="$icon" variant="solid" class="w-5 h-5 {{ $colorData['text'] }}" />
            </div>
        </div>

        @if(isset($slot) && $slot->isNotEmpty())
            <div class="mt-3">
                {{ $slot }}
            </div>
        @elseif($trend)
             <div class="mt-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-zinc-400">{{ $trend['label'] ?? 'vs le mois dernier' }}</span>
                     <span class="text-xs font-bold {{ ($trend['positive'] ?? true) ? 'text-emerald-600 bg-emerald-100/50' : 'text-rose-600 bg-rose-100/50' }} px-2 py-0.5 rounded text-center">
                        {{ $trend['value'] }}
                    </span>
                </div>
            </div>
        @endif
    </div>

    <!-- Pattern -->
    @if($color === 'indigo')
        <div class="absolute inset-0 opacity-[0.03] pattern-globe pointer-events-none mix-blend-multiply"></div>
    @endif
    <img src="{{ asset('img/widget-bg-abstract.png') }}"
        class="absolute inset-0 w-64 pointer-events-none group-hover:rotate-12 transition-transform duration-700"
        alt="" />
</x-flux::card>
