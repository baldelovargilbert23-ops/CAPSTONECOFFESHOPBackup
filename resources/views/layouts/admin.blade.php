<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full overflow-x-hidden">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ $title ?? config('app.name') }}</title>

    @include('layouts.partials.theme-init')

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif

    @livewireStyles
    @include('layouts.partials.global-theme')
</head>

<body class="font-sans antialiased transition-colors duration-300">

    <div x-data="{ sidebarOpen: false }" class="min-h-dvh overflow-x-hidden">

        {{-- Mobile Backdrop --}}
        <div x-show="sidebarOpen"
             x-cloak
             @click="sidebarOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 backdrop-blur-sm lg:hidden"
             style="background-color: rgba(0,0,0,0.6);"></div>

        {{-- Sidebar --}}
        @include('layouts.admin.sidebar')

        {{-- Main content wrapper --}}
        <div class="min-w-0 lg:pl-64 flex flex-col min-h-dvh">
            @include('layouts.admin.header')

            <main class="min-w-0 flex-1">
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>
    </div>

    @livewireScripts
    @vite(['resources/js/app.js'])

    @stack('scripts')
</body>
</html>
