<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full overflow-x-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    @include('layouts.partials.theme-init')

    @vite(['resources/css/app.css'])
    @livewireStyles
    @include('layouts.partials.global-theme')

    <style>
        /* Remove ambient glow blurs */
        .blur-\[120px\],
        .blur-\[100px\],
        .blur-\[140px\],
        .blur-\[150px\],
        .blur-\[200px\] {
            display: none !important;
        }

        .rounded-full.animate-pulse {
            animation: none !important;
            opacity: 0 !important;
        }
    </style>
</head>
<body class="min-h-dvh overflow-x-hidden bg-[#f5f0e8] dark:bg-[#0a1f1a] text-[#1a3a3a] dark:text-[#f0ebe0] font-sans antialiased transition-colors duration-300">

    <div x-data="{ sidebarOpen: false }" class="flex min-h-dvh md:h-dvh overflow-hidden">

        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen"
             x-cloak
             @click="sidebarOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm md:hidden"></div>

        {{-- Sidebar --}}
        @include('layouts.customer.sidebar')

        {{-- Main content --}}
        <div class="flex-1 flex flex-col overflow-hidden min-w-0">
            @include('layouts.customer.header')

            <main class="min-w-0 flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-6 lg:p-8 xl:p-10">
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>
    </div>

    {{-- Order kept: app.js first, then Livewire --}}
    @vite(['resources/js/app.js'])
    @livewireScripts

    {{-- Cookie Consent Banner --}}
    <div x-data="{
            show: false,
            init() {
                if (!localStorage.getItem('cookie_consent')) {
                    setTimeout(() => this.show = true, 1200);
                }
            },
            accept() {
                localStorage.setItem('cookie_consent', 'accepted');
                this.show = false;
            }
        }"
        x-init="init()"
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 translate-y-full"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-full"
        class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-6 sm:bottom-6 sm:max-w-sm z-[200]">

        <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl shadow-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 p-4 sm:p-5">
            <div class="flex items-start gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 flex items-center justify-center flex-shrink-0">
                    <i class="fa fa-cookie-bite text-[#c8965f] dark:text-[#d4a574] text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-[#1a3a3a] dark:text-[#f0ebe0]">We use cookies</p>
                    <p class="text-[11px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 leading-relaxed mt-0.5">
                        We use cookies to keep you signed in and improve your experience. By continuing, you agree to our use of cookies.
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <a href="#" class="flex-1 py-2 text-[11px] font-bold text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 hover:text-[#1a3a3a] dark:hover:text-[#f0ebe0] rounded-xl text-center transition">
                    Learn more
                </a>
                <button type="button"
                        @click="accept()"
                        class="flex-1 py-2 bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] font-bold text-[11px] rounded-xl hover:opacity-90 transition">
                    <i class="fa fa-check mr-1"></i> Got it
                </button>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
