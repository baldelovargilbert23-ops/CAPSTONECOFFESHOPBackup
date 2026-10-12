<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full overflow-x-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

        @include('layouts.partials.theme-init')

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('layouts.partials.global-theme')

    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-track:dark {
            background: #1f2937;
        }
        ::-webkit-scrollbar-thumb {
            background: #d2964b;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #b87c3a;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#0A0C10] text-gray-800 dark:text-gray-200 font-sans antialiased">

    <div class="flex min-h-dvh lg:h-dvh overflow-hidden">
        {{-- Sidebar --}}
        @include('layouts.customer.sidebar')

        {{-- Main content area --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Header --}}
            @include('layouts.customer.header')

            {{-- Page content --}}
            <main class="min-w-0 flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-6">
                {{-- ✅ Support both Blade @yield and Livewire $slot --}}
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>
    </div>

    @livewireScripts
    <script>
        // Dark mode toggle function
        function toggleDarkMode() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
        
        // Check for saved theme preference
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.remove('dark');
        } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>
