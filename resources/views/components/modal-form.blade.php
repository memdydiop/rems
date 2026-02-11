@props([
    'name' => '',
    'title' => '',
    'description' => '',
    'submitLabel' => 'Enregistrer',
    'cancelLabel' => 'Annuler',
    'size' => 'md', // sm, md, lg, xl
])

<flux:modal :name="$name" variant="flyout" class="max-w-{{ $size }}">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $title }}</flux:heading>
            @if($description)
                <flux:text class="mt-2">{{ $description }}</flux:text>
            @endif
        </div>

        <flux:separator />

        {{ $slot }}

        <div class="flex gap-2 pt-4">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">{{ $cancelLabel }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">{{ $submitLabel }}</flux:button>
        </div>
    </div>
</flux:modal>
