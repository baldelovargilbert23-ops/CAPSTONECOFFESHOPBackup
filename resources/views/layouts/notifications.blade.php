<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $title ?? 'Notifications · ' . config('app.name') }}</title>
    @include('layouts.partials.theme-init')
    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
    @include('layouts.partials.global-theme')
</head>
<body class="min-h-dvh bg-slate-50 font-sans antialiased dark:bg-slate-950">
    @php
        $user = auth()->user();
        $dashboardRoute = match($user?->role) {
            'admin' => 'admin.dashboard',
            'owner' => 'owner.dashboard',
            'branch_manager' => 'branch.dashboard',
            'cashier' => 'branch.cashier',
            'barista' => $user?->branch_id ? 'branch.barista' : 'tenant.barista',
            default => 'customer.dashboard',
        };
    @endphp
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
        <div class="mx-auto flex min-h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
            <a href="{{ route($dashboardRoute) }}" class="inline-flex min-h-10 items-center gap-2 rounded-xl px-3 text-sm font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-100 dark:hover:bg-slate-800">
                <i class="fa fa-arrow-left" aria-hidden="true"></i>
                <span>Back to dashboard</span>
            </a>
            <span class="hidden text-sm font-black text-slate-900 dark:text-white sm:block">Coffee Shop</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="min-h-10 rounded-xl px-3 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Sign out</button>
            </form>
        </div>
    </header>
    <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 sm:py-8">
        {{ $slot }}
    </main>
    @livewireScripts
    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/app.js'])
    @endif
</body>
</html>
