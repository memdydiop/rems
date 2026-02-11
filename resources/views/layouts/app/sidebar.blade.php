<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-linear-to-br from-blue-50 to-indigo-100 ">
    @php
        $isCentral = request()->routeIs('central.*') || request()->routeIs('tenants.*');
        $dashboardRoute = $isCentral ? route('central.dashboard') : route('dashboard');
        $settingsRoute = $isCentral ? route('central.profile.edit') : route('profile.edit');
        $logoutRoute = $isCentral ? route('central.logout') : route('logout');
    @endphp

    <flux:sidebar sticky collapsible="mobile" class="bg-white border-e border-zinc-200">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ $dashboardRoute }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Plateforme')" class="grid">
                <flux:sidebar.item icon="squares-2x2" :href="$dashboardRoute"
                    :current="request()->routeIs($isCentral ? 'central.dashboard' : 'dashboard')" wire:navigate>
                    {{ __('Tableau de bord') }}
                </flux:sidebar.item>

            </flux:sidebar.group>

            @if($isCentral)
                <flux:sidebar.group :heading="__('Central')" class="grid">


                    <flux:sidebar.item icon="building-office" :href="route('central.tenants.index')"
                        :current="request()->routeIs('central.tenants.index')" wire:navigate>
                        {{ __('Organisations') }}
                    </flux:sidebar.item>

                    @can('manage plans')
                        <flux:sidebar.item icon="currency-dollar" :href="route('central.plans.index')"
                            :current="request()->routeIs('central.plans.index')" wire:navigate>
                            {{ __('Forfaits') }}
                        </flux:sidebar.item>
                    @endcan

                    <flux:sidebar.item icon="users" :href="route('central.users.index')"
                        :current="request()->routeIs('central.users.index')" wire:navigate>Utilisateurs</flux:sidebar.item>


                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Analytics')" class="grid">
                    <flux:sidebar.item icon="chart-bar-square" :href="route('central.reports.index')"
                        :current="request()->routeIs('central.reports.*')" wire:navigate>
                        {{ __('Rapports') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="clipboard-document-list" :href="route('central.activity.index')"
                        :current="request()->routeIs('central.activity.*')" wire:navigate>
                        {{ __('Activité') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Paramètres')" class="grid">
                    <flux:sidebar.item icon="shield-check" :href="route('central.settings.roles.index')"
                        :current="request()->routeIs('central.settings.roles.*')" wire:navigate>
                        {{ __('Rôles & Permissions') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            @else
                <flux:sidebar.group :heading="__('Gestion Immobilière')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('tenant.properties.index')"
                        :current="request()->routeIs('tenant.properties.*')" wire:navigate>
                        {{ __('Tous les biens') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office" :href="route('tenant.units.index')"
                        :current="request()->routeIs('tenant.units.*')" wire:navigate>
                        {{ __('Unités') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="wrench-screwdriver" :href="route('tenant.maintenance.index')"
                        :current="request()->routeIs('tenant.maintenance.*')" wire:navigate>
                        {{ __('Maintenance') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="briefcase" :href="route('tenant.projects.index')"
                        :current="request()->routeIs('tenant.projects.*')" wire:navigate>
                        {{ __('Travaux') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Gestion Locative')" class="grid">
                    <flux:sidebar.item icon="users" :href="route('tenant.renters.index')"
                        :current="request()->routeIs('tenant.renters.*')" wire:navigate>
                        {{ __('Locataires') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('tenant.leases.index')"
                        :current="request()->routeIs('tenant.leases.*')" wire:navigate>
                        {{ __('Baux') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="user-circle" :href="route('tenant.owners.index')"
                        :current="request()->routeIs('tenant.owners.*')" wire:navigate>
                        {{ __('Propriétaires') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Finance & Rapports')" class="grid">
                    <flux:sidebar.item icon="banknotes" :href="route('tenant.expenses.index')"
                        :current="request()->routeIs('tenant.expenses.*')" wire:navigate>
                        {{ __('Dépenses') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar-square" :href="route('tenant.reports.index')"
                        :current="request()->routeIs('tenant.reports.*')" wire:navigate>
                        {{ __('Rapports') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Utilisateurs" class="grid">
                    <flux:sidebar.item icon="users" :href="route('tenant.settings.members')"
                        :current="request()->routeIs('tenant.settings.members')" wire:navigate>
                        Membres de l'équipe
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Paramètres')" class="grid">
                    <flux:sidebar.item icon="credit-card" :href="route('tenant.settings.billing')"
                        :current="request()->routeIs('tenant.settings.billing')" wire:navigate>
                        {{ __('Facturation') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="clipboard-document-list" :href="route('tenant.settings.activity')"
                        :current="request()->routeIs('tenant.settings.activity')" wire:navigate>
                        {{ __('Activité') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="user-group" :href="route('tenant.settings.vendors.index')"
                        :current="request()->routeIs('tenant.settings.vendors.*')" wire:navigate>
                        Prestataires
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="shield-check" :href="route('tenant.settings.roles.index')"
                        :current="request()->routeIs('tenant.settings.roles.*')" wire:navigate>
                        Rôles & Permissions
                    </flux:sidebar.item>
                </flux:sidebar.group>
            @endif
        </flux:sidebar.nav>

        <flux:spacer />

        <flux:sidebar.nav>
            <!-- Documentation links kept -->
            <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit"
                target="_blank">
                {{ __('Code source') }}
            </flux:sidebar.item>

            <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire"
                target="_blank">
                {{ __('Documentation') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </flux:sidebar>

    <flux:header sticky class="bg-white h-14 px-4">
        <flux:sidebar.toggle icon="bars-3" class="lg:hidden" />

        <flux:spacer class="lg:hidden" />
        <x-app-logo href="{{ $dashboardRoute }}" class="lg:hidden scale-90" />

        <div class="hidden lg:flex flex-1 items-center pr-8">
            <livewire:global-search />
        </div>

        <div class="flex items-center gap-4">
            <flux:button variant="ghost" size="sm" icon="envelope" class="text-zinc-500" />
            <livewire:notifications-dropdown />

            <flux:separator vertical class="h-6" />

            <flux:dropdown position="bottom" align="end">
                <flux:profile name="{{ auth()->user()->name }}" :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down" class="hover:bg-zinc-50 p-1 rounded-lg transition-colors" />

                <flux:menu>
                    <div class="p-2 border-b border-zinc-100 mb-2">
                        <div class="flex items-center gap-2">
                            <flux:avatar class="size-8" :name="auth()->user()->name"
                                :initials="auth()->user()->initials()" />
                            <div class="flex flex-col">
                                <span
                                    class="text-sm font-medium text-zinc-900 truncate max-w-[150px]">{{ auth()->user()->name }}</span>
                                <span
                                    class="text-xs text-zinc-500 truncate max-w-[150px]">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>

                    <flux:menu.item :href="$settingsRoute" icon="cog" wire:navigate>{{ __('Paramètres') }}
                    </flux:menu.item>
                    <form method="POST" action="{{ $logoutRoute }}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                            variant="danger">
                            {{ __('Se déconnecter') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:header>

    <flux:main class="p-6">
        {{ $slot }}
    </flux:main>

    @if(!$isCentral && session()->has('impersonating_from_central'))
        <div
            class="fixed bottom-0 inset-x-0 bg-indigo-600 text-white text-sm font-medium py-2 px-4 text-center z-50 flex items-center justify-center gap-4 shadow-lg mb-0 lg:ml-64 transition-all duration-300">
            <span class="flex items-center gap-2">
                <flux:icon.user class="size-4" />
                🕵️ Mode Impersonation actif
            </span>
            <a href="{{ route('tenancy.impersonate.leave') }}"
                class="underline hover:text-indigo-200 font-semibold text-white">Quitter le mode</a>
        </div>
    @endif

    @fluxScripts
    @flasher_render
</body>

</html>