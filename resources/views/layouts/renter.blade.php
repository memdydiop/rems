<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="font-sans antialiased bg-zinc-50">
    @persist('toast')
    {{-- <x-flux::toast /> --}}
    @endpersist

    @if(isset($slot))
        {{ $slot }}
    @else
        @yield('content')
    @endif

    @fluxScripts
</body>

</html>