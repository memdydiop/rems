<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white text-zinc-900 antialiased font-sans">
    {{ $slot }}

    @fluxScripts
    @flasher_render
</body>

</html>