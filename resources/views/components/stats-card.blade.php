@props([
    'icon',
    'title',
    'value',
    'color' => 'zinc' // default color
])
@php
    $iconBg = match ($color) {
        'blue' => 'bg-blue-50 text-blue-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'green' => 'bg-green-50 text-green-600',
        'orange' => 'bg-orange-50 text-orange-600',
        'red' => 'bg-red-50 text-red-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'cyan' => 'bg-cyan-50 text-cyan-600',
        'indigo' => 'bg-indigo-50 text-indigo-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'purple' => 'bg-purple-50 text-purple-600',
        'fuchsia' => 'bg-fuchsia-50 text-fuchsia-600',
        'pink' => 'bg-pink-50 text-pink-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'yellow' => 'bg-yellow-50 text-yellow-600',
        'lime' => 'bg-lime-50 text-lime-600',
        'teal' => 'bg-teal-50 text-teal-600',
        'sky' => 'bg-sky-50 text-sky-600',
        'gray', 'zinc', 'slate', 'neutral', 'stone' => 'bg-zinc-50 text-zinc-600',
        default => 'bg-zinc-50 text-zinc-600',
    };
@endphp

<x-flux::card class="flex items-center gap-4 p-6 border-zinc-200">
    <div class="flex items-center justify-center size-12 rounded-full {{ $iconBg }}">
        <flux:icon :name="$icon" class="size-6" />
    </div>
    <div class="flex flex-col">
        <span class="text-sm font-medium text-zinc-500">{{ $title }}</span>
        <span class="text-2xl font-bold text-zinc-900">{{ $value }}</span>
    </div>
</x-flux::card>
