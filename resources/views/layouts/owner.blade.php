<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    
    @include('partials.head')
</head>

<body class="h-full bg-zinc-50 antialiased font-sans">
    <flux:sidebar sticky collapsible="mobile" class="bg-white border-e border-zinc-200">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <flux:brand href="#" logo="https://fluxui.dev/img/demo/logo.png" name="{{ config('app.name') }}" class="px-2" />

        <flux:sidebar.nav>
            <flux:sidebar.group heading="Espace Propriétaire" class="grid">
                <flux:sidebar.item icon="home" href="{{ route('owner.dashboard') }}"
                    :current="request()->routeIs('owner.dashboard')" wire:navigate
                    class="data-[current]:bg-zinc-100 data-[current]:text-zinc-900">
                    Dashboard
                </flux:sidebar.item>

                {{-- Future Links --}}
                <flux:sidebar.item icon="document-text" href="#" disabled>
                    Rapports (Bientôt)
                </flux:sidebar.item>

                <flux:sidebar.item icon="cog-6-tooth" href="#" disabled>
                    Paramètres
                </flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        <flux:sidebar.nav>
            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:profile avatar="https://fluxui.dev/img/demo/user.png"
                    name="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}" />

                <flux:menu>
                    <flux:menu.item icon="arrow-right-start-on-rectangle" wire:click="logout" class="text-red-600">
                        <form method="POST" action="{{ route('logout') }}" id="logout-form">
                            @csrf
                            <button type="submit" class="w-full text-left">Se déconnecter</button>
                        </form>
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar.nav>
    </flux:sidebar>

    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" />

        <flux:spacer />

        <flux:profile avatar="https://fluxui.dev/img/demo/user.png" />
    </flux:header>

    <flux:main>
        {{ $slot }}
    </flux:main>

    @fluxScripts
    @persist('toast')
    {{-- <x-flux::toast /> --}}
    @endpersist
</body>

</html>