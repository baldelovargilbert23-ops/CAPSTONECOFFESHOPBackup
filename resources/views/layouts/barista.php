<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full overflow-x-hidden bg-gray-50 dark:bg-[#0A0C10]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>

    @include('layouts.partials.theme-init')
    
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"></noscript>
<link rel="preload" as="style" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"></noscript>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    <style>
        /* Custom Brown Neon Theme for Shop Owner */
        :root {
            --neon-brown: #d2964b;
            --neon-brown-dark: #8b5a2b;
            --neon-glow: rgba(210, 150, 75, 0.3);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #1f2937;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #d2964b;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #b87c3a;
        }
        
        /* Smooth transitions */
        * {
            transition-property: background-color, border-color, color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
    </style>
    @include('layouts.partials.global-theme')
</head>
<body class="staff-workspace h-full overflow-hidden bg-gray-50 dark:bg-[#0A0C10] font-sans antialiased">
    
    <!-- The Header -->
    @include('layouts.shop_owner.header')

    <div class="flex min-h-dvh lg:h-dvh min-w-0 overflow-hidden bg-gray-50 dark:bg-[#0A0C10]">
        
        <!-- Sidebar -->
        @include('layouts.shop_owner.sidebar')

        <!-- Main Content Area -->
        <main class="min-w-0 flex-1 w-full lg:ps-72 transition-all duration-300 overflow-y-auto pt-16">
            <div class="min-h-full">
                {{ $slot }}
            </div>
        </main>

    </div>

    @livewireScripts
    
    <script>
        // Preline initialization if used
        if (typeof HSStaticMethods !== 'undefined') {
            HSStaticMethods.autoInit();
        }
        
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
