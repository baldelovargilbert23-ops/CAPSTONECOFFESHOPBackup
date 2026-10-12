<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $title ?? config('app.name') }}</title>

    @include('layouts.partials.theme-init')

    @vite(['resources/css/app.css'])
    @livewireStyles
    @include('layouts.partials.global-theme')
</head>

<body class="bg-[#f5f0e8] text-[#1a3a3a] font-sans antialiased">
    <main>
        @yield('content')
    </main>

    @livewireScripts
    @vite(['resources/js/app.js', 'resources/js/preline.js'])
</body>
</html>
