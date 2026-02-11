<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        @php
            $prefix = request()->routeIs('central.*') ? 'central.' : '';
        @endphp
        <flux:navlist aria-label="{{ __('Settings') }}">
            @if(Route::has($prefix . 'profile.edit'))
                <flux:navlist.item :href="route($prefix . 'profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            @endif
            @if(Route::has($prefix . 'user-password.edit'))
                <flux:navlist.item :href="route($prefix . 'user-password.edit')" wire:navigate>{{ __('Password') }}</flux:navlist.item>
            @endif
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication() && Route::has($prefix . 'two-factor.show'))
                <flux:navlist.item :href="route($prefix . 'two-factor.show')" wire:navigate>{{ __('Two-Factor Auth') }}</flux:navlist.item>
            @endif
            @if(Route::has($prefix . 'appearance.edit'))
                <flux:navlist.item :href="route($prefix . 'appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
            @endif
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
