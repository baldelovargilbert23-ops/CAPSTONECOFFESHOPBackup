<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full overflow-x-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $title ?? config('app.name') }}</title>

    @include('layouts.partials.theme-init')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('layouts.partials.global-theme')

    <style>
        html, body {
            overflow-x: hidden;
            max-width: 100vw;
        }

        body {
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
        }

        [x-cloak] { display: none !important; }

        @media (max-width: 640px) {
            .text-\[10px\] { font-size: 11px !important; }
            .text-\[9px\]  { font-size: 10px !important; }
            .text-\[8px\]  { font-size: 9px  !important; }

            button, a[role="button"] { min-height: 36px; }

            .h-52 { height: 160px !important; }
            .h-44 { height: 140px !important; }
        }
    </style>
</head>

<body class="min-h-dvh overflow-x-hidden font-sans antialiased"
      x-data="{ sidebarOpen: false }"
      x-on:keydown.escape.window="sidebarOpen = false"
      x-on:livewire:navigated.window="sidebarOpen = false">

    {{-- ═══════════════════ MOBILE TOP HEADER ═══════════════════ --}}
    <header class="lg:hidden fixed top-0 left-0 right-0 z-[70] h-14 shadow-sm"
            style="background-color: var(--bg-elevated); border-bottom: 1px solid var(--border-soft);">
        <div class="flex items-center justify-between h-full px-3">

            <a href="{{ route('branch.dashboard') }}" class="flex items-center gap-2 min-w-0">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                     style="background-color: var(--forest); color: var(--bg-primary);">
                    <i class="fa fa-store text-xs"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[9px] font-bold uppercase tracking-widest leading-tight truncate"
                       style="color: var(--accent);">
                        {{ auth()->user()->tenant->name ?? 'Shop' }}
                    </p>
                    <p class="text-xs font-black leading-tight truncate"
                       style="color: var(--text-primary);">
                        Branch Portal
                    </p>
                </div>
            </a>

            <div class="flex shrink-0 items-center gap-2">
            @include('layouts.partials.notification-link')
            <button type="button"
                    x-on:click="sidebarOpen = true"
                    class="p-2 rounded-lg active:scale-95 transition-all flex-shrink-0"
                    style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);"
                    aria-label="Open menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                          d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            </div>
        </div>
    </header>

    {{-- ═══════════════════ MOBILE BACKDROP (Alpine) ═══════════════════ --}}
    <div x-show="sidebarOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-on:click="sidebarOpen = false"
         class="lg:hidden fixed inset-0 z-[75] backdrop-blur-sm"
         style="background-color: rgba(0,0,0,0.6);"
         aria-hidden="true">
    </div>

    {{-- ═══════════════════ MAIN LAYOUT ═══════════════════ --}}
    <div class="flex min-h-dvh lg:h-dvh" style="background-color: var(--bg-primary);">

        @include('layouts.branch.sidebar')

        <main class="min-w-0 flex-1 w-full lg:ps-80 transition-all duration-300 overflow-y-auto overflow-x-hidden">
            <div class="min-h-full">
                <div class="lg:hidden h-14"></div>
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
