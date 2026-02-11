@props([
    'label' => '',
    'value' => '',
    'change' => null,
    'changeType' => 'increase', // 'increase' or 'decrease'
    'icon' => 'chart-bar',
    'color' => '#2563EB',
    'sparklineData' => null,
])

@php
    $changeClass = $changeType === 'increase' 
        ? 'text-emerald-600 bg-emerald-50' 
        : 'text-rose-600 bg-rose-50';
    $changePrefix = $changeType === 'increase' ? '+' : '';
@endphp

<x-flux::card class="relative overflow-hidden">
    <div class="p-4 flex flex-col justify-between h-full">
        <div class="flex items-start justify-between">
            <div>
                <span class="text-zinc-500 text-xs font-medium uppercase tracking-wider">{{ $label }}</span>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-bold text-zinc-900">{{ $value }}</span>
                    @if($change !== null)
                        <span class="text-xs font-medium {{ $changeClass }} px-1.5 py-0.5 rounded-full">
                            {{ $changePrefix }}{{ $change }}%
                        </span>
                    @endif
                </div>
            </div>
            <flux:icon :name="$icon" variant="solid" class="text-zinc-200 w-6 h-6" />
        </div>
        
        @if($sparklineData)
            <div x-data="{
                init() {
                    const options = {
                        series: [{ data: {{ json_encode($sparklineData) }} }],
                        chart: { type: 'area', height: 40, sparkline: { enabled: true } },
                        stroke: { curve: 'smooth', width: 2 },
                        fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0 } },
                        colors: ['{{ $color }}'],
                        tooltip: { fixed: { enabled: false }, x: { show: false }, y: { title: { formatter: () => '' } }, marker: { show: false } }
                    };
                    const chart = new ApexCharts(this.$el, options);
                    chart.render();
                }
            }" class="mt-4 -mb-4 -mx-4 h-10"></div>
        @endif
    </div>
</x-flux::card>
