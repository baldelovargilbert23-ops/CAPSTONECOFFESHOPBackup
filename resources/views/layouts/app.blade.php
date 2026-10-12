<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name', 'Public') }}</title>

        @include('layouts.partials.theme-init')

        {{-- Public site falls back to dark if localStorage is unavailable --}}
        <script>
            (function () {
                try {
                    var s = localStorage.getItem('theme');
                    var dark = s ? s === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    document.documentElement.classList.toggle('dark', dark);
                } catch (e) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        @vite(['resources/css/app.css'])
        @livewireStyles
        @include('layouts.partials.global-theme')
    </head>
    <body class="bg-stone-950 dark:bg-stone-950 text-stone-100 transition-colors duration-300">
        @include('layouts.public.header')

        @hasSection('content')
            @yield('content')
        @else
            {{ $slot ?? '' }}
        @endif

        @include('layouts.public.footer')

        @vite(['resources/js/app.js'])
        @livewireScripts
    </body>
</html>
