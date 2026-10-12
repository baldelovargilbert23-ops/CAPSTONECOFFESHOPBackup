<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    @include('layouts.partials.theme-init')

    {{-- This layout defaults to DARK when no theme is saved --}}
    <script>
        (function () {
            try {
                var s = localStorage.getItem('theme');
                document.documentElement.classList.toggle('dark', s !== 'light');
            } catch (e) { document.documentElement.classList.add('dark'); }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        :root {
            --neon-brown: #d2964b;
            --neon-brown-light: #e8b86d;
            --neon-brown-dark: #8b5a2b;
            --neon-brown-glow: rgba(210, 150, 75, 0.25);
            --neon-brown-glow-strong: rgba(210, 150, 75, 0.4);
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background: var(--bg-primary) !important;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        .bg-gray-50 { background-color: var(--bg-primary) !important; }
        .dark { background-color: var(--bg-primary) !important; }

        /* Scrollbar (size/radius/hover shape come from app.css) */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        ::-webkit-scrollbar-thumb { background: var(--neon-brown); }
        ::-webkit-scrollbar-thumb:hover { background: var(--neon-brown-dark); }

        /* Utility classes */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        .text-neon-brown { color: var(--neon-brown); }
        .bg-neon-brown { background-color: var(--neon-brown); }
        .border-neon-brown { border-color: var(--neon-brown); }

        .glow-brown { box-shadow: 0 0 20px var(--neon-brown-glow); }
        .glow-brown-strong { box-shadow: 0 0 30px var(--neon-brown-glow-strong); }

        /* Content area - full width */
        #content {
            margin-left: 0 !important;
            min-height: 100vh;
            width: 100% !important;
            max-width: 100% !important;
            background: var(--bg-primary) !important;
            padding-top: 0 !important;
        }

        .page-content {
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            background: transparent !important;
        }

        /* FIX: scoped to #content only.
           Before, this applied to the whole page and overrode the logout modal's
           max-w-md, which made the modal stretch across the entire screen. */
        #content .container,
        #content .max-w-7xl, #content .max-w-6xl, #content .max-w-5xl, #content .max-w-4xl,
        #content .max-w-3xl, #content .max-w-2xl, #content .max-w-xl, #content .max-w-lg,
        #content .max-w-md, #content .max-w-sm {
            max-width: 100% !important;
        }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.05) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .dark .glass {
            background: rgba(17, 19, 26, 0.6) !important;
            border-color: rgba(255, 255, 255, 0.05);
        }

        /* Notification */
        .notification-toast { animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Loading shimmer */
        .shimmer {
            background: linear-gradient(90deg, transparent 0%, rgba(210, 150, 75, 0.05) 50%, transparent 100%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            0%   { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        *:focus-visible {
            outline: 2px solid var(--neon-brown);
            outline-offset: 2px;
        }

        /* Side logout button (compact) */
        .side-logout {
            position: fixed;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 50;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .logout-btn-side {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.15);
            color: #f87171;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .logout-btn-side .icon { font-size: 13px; transition: all 0.3s ease; }

        /* Label hidden - the tooltip already says "Sign Out" */
        .logout-btn-side .label { display: none; }

        .logout-btn-side:hover {
            background: rgba(239, 68, 68, 0.18);
            border-color: rgba(239, 68, 68, 0.4);
            transform: scale(1.05) translateX(-3px);
            box-shadow: 0 0 24px rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }

        .logout-btn-side:hover .icon { transform: rotate(-15deg); }
        .logout-btn-side:active { transform: scale(0.92) translateX(-2px); }

        .logout-btn-side .tooltip {
            position: absolute;
            right: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%) scale(0.9);
            background: rgba(17, 19, 26, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.08);
            color: #e5e7eb;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }

        .logout-btn-side .tooltip::after {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-left-color: rgba(17, 19, 26, 0.95);
        }

        .logout-btn-side:hover .tooltip {
            opacity: 1;
            transform: translateY(-50%) scale(1);
        }

        @media (max-width: 768px) {
            .side-logout {
                right: 12px;
                top: auto;
                bottom: 100px;
                transform: none;
                flex-direction: row;
                gap: 8px;
            }
            .logout-btn-side { width: 34px; height: 34px; border-radius: 10px; }
            .logout-btn-side .tooltip { display: none; }
        }

        @media (max-width: 480px) {
            .side-logout { right: 10px; bottom: 80px; }
            .logout-btn-side { width: 32px; height: 32px; border-radius: 9px; }
            .logout-btn-side .icon { font-size: 12px; }
        }

        /* Logout confirmation modal */
        .modal-backdrop { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .modal-content { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
        }

        [x-cloak] { display: none !important; }
    </style>
    @include('layouts.partials.global-theme')
</head>
<body class="staff-workspace min-h-dvh overflow-x-hidden font-sans antialiased" style="background: var(--bg-primary) !important; color: var(--text-primary) !important;">

    <div class="flex min-h-dvh min-w-0 relative" style="background: var(--bg-primary) !important;">

        {{-- Main Content - FULL WIDTH --}}
        <main id="content" class="min-w-0 flex-1 min-h-dvh w-full" style="background: var(--bg-primary) !important;">
            <div class="page-content w-full max-w-full" style="background: var(--bg-primary) !important;">
                {{ $slot }}
            </div>
        </main>
    </div>

    {{-- SIDE LOGOUT BUTTON --}}
    <div class="side-logout">
        <button onclick="confirmLogout()" class="logout-btn-side" title="Logout" aria-label="Logout">
            <i class="fa-solid fa-right-from-bracket icon"></i>
            <span class="label">Logout</span>
            <span class="tooltip">Sign Out</span>
        </button>
    </div>

    {{-- Logout Confirmation Modal (compact, centered) --}}
    <div x-data="{ show: false }"
         x-show="show"
         x-on:open-logout-modal.window="show = true"
         x-on:keydown.escape.window="show = false"
         class="fixed inset-0 z-[200] flex items-center justify-center p-4 modal-backdrop"
         style="background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(8px);"
         x-cloak>

        <div @click.away="show = false"
             class="glass rounded-2xl max-w-sm w-full p-5 shadow-2xl modal-content"
             style="background: rgba(17, 19, 26, 0.95) !important; border: 1px solid rgba(255,255,255,0.1);">

            <div class="flex justify-center mb-3">
                <div class="w-11 h-11 rounded-full bg-red-500/20 flex items-center justify-center">
                    <i class="fa-solid fa-right-from-bracket text-base text-red-400"></i>
                </div>
            </div>

            <h3 class="text-base font-bold text-white text-center mb-1">Log out?</h3>
            <p class="text-xs text-slate-400 text-center mb-4">
                You'll need to sign in again to use your dashboard.
            </p>

            <div class="flex items-center justify-center gap-2">
                <button type="button" @click="show = false"
                        class="px-4 py-1.5 rounded-lg text-xs font-medium text-slate-300 hover:text-white bg-white/5 hover:bg-white/10 transition-all duration-300">
                    Cancel
                </button>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="px-4 py-1.5 rounded-lg text-xs font-medium text-white bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 transition-all duration-300">
                        <i class="fa-solid fa-right-from-bracket mr-1.5 text-[10px]"></i>
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Notification System --}}
    <div x-data="notificationSystem()"
         x-init="init()"
         class="fixed bottom-6 right-6 z-[100] space-y-3 max-w-sm w-full">

        <template x-for="(notification, index) in notifications" :key="notification.id">
            <div x-show="notification.visible"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 transform translate-y-4 scale-95"
                 class="glass rounded-xl shadow-2xl border-l-4 p-3"
                 :class="{
                     'border-[#d2964b]': notification.type === 'success',
                     'border-red-500': notification.type === 'error',
                     'border-blue-500': notification.type === 'info',
                     'border-yellow-500': notification.type === 'warning'
                 }">

                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center"
                             :class="{
                                 'bg-[#d2964b]/20 text-[#d2964b]': notification.type === 'success',
                                 'bg-red-500/20 text-red-500': notification.type === 'error',
                                 'bg-blue-500/20 text-blue-500': notification.type === 'info',
                                 'bg-yellow-500/20 text-yellow-500': notification.type === 'warning'
                             }">
                            <i :class="{
                                'fas fa-check': notification.type === 'success',
                                'fas fa-times': notification.type === 'error',
                                'fas fa-info': notification.type === 'info',
                                'fas fa-exclamation-triangle': notification.type === 'warning'
                            }" class="text-xs"></i>
                        </div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white" x-text="notification.title || 'Notification'"></p>
                        <p class="text-xs text-slate-400 mt-0.5" x-text="notification.message"></p>
                    </div>

                    <button @click="remove(notification.id)"
                            class="flex-shrink-0 text-slate-400 hover:text-white transition">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <script>
        // Notification System
        document.addEventListener('alpine:init', () => {
            Alpine.data('notificationSystem', () => ({
                notifications: [],
                id: 0,

                init() {
                    window.addEventListener('notify', (e) => {
                        this.add(e.detail);
                    });
                },

                add({ message, title = null, type = 'success', duration = 4000 }) {
                    const notification = {
                        id: this.id++,
                        message,
                        title,
                        type,
                        visible: true,
                        timer: null
                    };

                    this.notifications.push(notification);

                    if (duration > 0) {
                        notification.timer = setTimeout(() => {
                            this.remove(notification.id);
                        }, duration);
                    }
                },

                remove(id) {
                    const index = this.notifications.findIndex(n => n.id === id);
                    if (index !== -1) {
                        clearTimeout(this.notifications[index].timer);
                        this.notifications.splice(index, 1);
                    }
                },

                clear() {
                    this.notifications.forEach(n => clearTimeout(n.timer));
                    this.notifications = [];
                }
            }));
        });

        // Notification helper
        window.notify = function (message, type = 'success', title = null) {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message, type, title }
            }));
        };

        window.showNotification = window.notify;

        // Logout
        window.confirmLogout = function () {
            window.dispatchEvent(new CustomEvent('open-logout-modal'));
        };

        // Ctrl + L - Logout
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'l') {
                e.preventDefault();
                window.confirmLogout();
            }
        });
    </script>

    @livewireScripts
</body>
</html>