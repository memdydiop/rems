@blaze

@props([
    'size' => 'base',
    'accent' => false,
    'level' => null,
])

@php
$classes = Flux::classes()
    ->add('font-medium text-heading')
    ->add(match ($accent) {
        true => 'text-[var(--color-accent-content)]',
        default => '[:where(&)]:text-zinc-800',
    })
    ->add(match ($size) {
        'xl' => 'text-xl [&:has(+[data-flux-subheading])]:mb-0 [[data-flux-subheading]+&]:mt-0',
        'lg' => 'text-lg [&:has(+[data-flux-subheading])]:mb-0 [[data-flux-subheading]+&]:mt-0',
        'md' => 'text-md [&:has(+[data-flux-subheading])]:mb-0 [[data-flux-subheading]+&]:mt-0',
        default => 'text-base [&:has(+[data-flux-subheading])]:mb-0 [[data-flux-subheading]+&]:mt-0',
        'sm' => 'text-sm [&:has(+[data-flux-subheading])]:mb-0 [[data-flux-subheading]+&]:mt-0',
        'xs' => 'text-xs [&:has(+[data-flux-subheading])]:mb-0 [[data-flux-subheading]+&]:mt-0',
    })
    ;
@endphp

<?php switch ((int) $level): case(1): ?>
        <h1 {{ $attributes->class($classes) }} data-flux-heading>{{ $slot }}</h1>

        @break
    <?php case(2): ?>
        <h2 {{ $attributes->class($classes) }} data-flux-heading>{{ $slot }}</h2>

        @break
    <?php case(3): ?>
        <h3 {{ $attributes->class($classes) }} data-flux-heading>{{ $slot }}</h3>

        @break
    <?php case(4): ?>
        <h4 {{ $attributes->class($classes) }} data-flux-heading>{{ $slot }}</h4>

        @break
    <?php default: ?>
        <div {{ $attributes->class($classes) }} data-flux-heading>{{ $slot }}</div>
<?php endswitch; ?>
