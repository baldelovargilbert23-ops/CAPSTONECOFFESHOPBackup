<!-- @php
    // ✅ Null-safe: works for both guests and logged-in customers
    $user = auth()->user();
    $tenantId = $user?->tenant_id;
    $customerPoints = 0;

    if ($user && $tenantId) {
        $points = App\Models\CustomerPoint::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->first();
        $customerPoints = $points ? $points->points : 0;
    }
@endphp

{{-- ✅ Shared Alpine scope: keeps state + persists to localStorage across pages --}}
<div x-data="{
        sidebarOpen: (localStorage.getItem('sidebarOpen') === null)
                     ? true
                     : localStorage.getItem('sidebarOpen') === 'true',
        init() {
            this.$watch('sidebarOpen', value => {
                localStorage.setItem('sidebarOpen', value);
            });
        }
    }">

    {{-- ✅ FLOATING OPEN BUTTON — appears when sidebar is closed --}}
    <button x-show="!sidebarOpen"
            x-cloak
            @click="sidebarOpen = true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-x-2"
            x-transition:enter-end="opacity-100 translate-x-0"
            class="fixed top-4 left-4 z-[60] flex items-center justify-center w-10 h-10 rounded-xl
                   bg-stone-50 dark:bg-stone-950
                   text-stone-600 dark:text-stone-300
                   border border-stone-200 dark:border-stone-800
                   shadow-lg hover:text-amber-500 hover:bg-amber-500/10 hover:border-amber-500/40
                   transition-all duration-200"
            aria-label="Open sidebar">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="3"></rect>
            <path d="M9 3v18"></path>
        </svg>
    </button>

    <aside x-show="sidebarOpen"
           x-cloak
           @keydown.escape.window="sidebarOpen = false"
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           class="fixed md:relative inset-y-0 left-0 z-50 md:z-auto w-72 h-dvh max-h-dvh flex-shrink-0
                  bg-stone-50 dark:bg-stone-950 text-stone-900 dark:text-stone-200
                  flex flex-col
                  border-r border-stone-200 dark:border-stone-800/60
                  shadow-2xl transition-colors duration-300
                  overflow-hidden">

        {{-- Brand Section --}}
        <div class="h-24 sm:h-28 flex items-center justify-between px-6 sm:px-8 relative overflow-hidden border-b border-stone-200 dark:border-stone-800/60 md:border-0 flex-shrink-0">
            <div class="absolute -top-12 -left-12 w-40 h-40 bg-amber-500/10 rounded-full blur-[60px]"></div>

            <div class="flex items-center space-x-3 sm:space-x-4 group cursor-pointer relative z-10 min-w-0">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-amber-500 to-amber-700 rounded-2xl flex items-center justify-center shadow-[0_0_20px_rgba(245,158,11,0.25)] group-hover:scale-110 group-hover:rotate-3 transition-all duration-500 border border-amber-500/20 flex-shrink-0">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-stone-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 17c0 3.314-2.686 6-6 6s-6-2.686-6-6 2.686-6 6-6 6 2.686 6 6zM22 8c0 1.657-1.343 3-3 3h-2v-6h2c1.657 0 3 1.343 3 3zM17 11V5" />
                    </svg>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-xl sm:text-2xl font-black tracking-tighter leading-none text-stone-900 dark:text-white uppercase italic truncate">
                        Multi<span class="text-amber-500">Tenant</span>
                    </span>
                    <span class="text-[9px] font-bold tracking-[0.4em] text-amber-600/70 dark:text-amber-500/60 uppercase mt-1 truncate">Victorias Coffee Shops</span>
                </div>
            </div>
        </div>

        {{-- Close button --}}
        <div class="px-4 pt-4 flex-shrink-0">
            <div class="relative group">
                <button @click="sidebarOpen = false"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-2xl
                               text-stone-500 dark:text-stone-400
                               bg-stone-100 dark:bg-stone-900/60
                               hover:bg-amber-500/10 hover:text-amber-600 dark:hover:text-amber-400
                               border border-stone-200 dark:border-stone-800
                               hover:border-amber-500/40
                               transition-all duration-300"
                        aria-label="Close sidebar">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="3"></rect>
                            <path d="M9 3v18"></path>
                        </svg>
                        <span class="font-bold text-[11px] tracking-wider uppercase">Hide Sidebar</span>
                    </span>
                    <svg class="w-4 h-4 flex-shrink-0 opacity-60 group-hover:opacity-100 group-hover:-translate-x-0.5 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 min-h-0 px-4 py-4 space-y-2 overflow-y-auto">
            <p class="px-5 text-[10px] font-black text-stone-400 dark:text-stone-600 uppercase tracking-[0.3em] mb-4">Main Menu</p>

            {{-- Discover --}}
            <a href="{{ route('public.landing') }}"
               @click="window.innerWidth < 768 && (sidebarOpen = false)"
               class="group flex items-center px-4 sm:px-5 py-3 sm:py-4 rounded-2xl transition-all duration-300
                      {{ request()->routeIs('public.landing')
                         ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30'
                         : 'text-stone-600 dark:text-stone-500 hover:bg-stone-100 dark:hover:bg-stone-900 hover:text-stone-900 dark:hover:text-stone-100' }}">
                <svg class="w-5 h-5 mr-3 sm:mr-4 flex-shrink-0 {{ request()->routeIs('public.landing') ? 'text-amber-500' : 'text-stone-400 dark:text-stone-600 group-hover:text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>
                </svg>
                <div class="flex flex-col min-w-0">
                    <span class="font-bold text-xs sm:text-[13px] tracking-wide uppercase">Discover</span>
                    <span class="text-[9px] {{ request()->routeIs('public.landing') ? 'text-amber-700 dark:text-amber-200' : 'text-stone-500 dark:text-stone-600' }} font-bold truncate">Explore Victorias Coffee</span>
                </div>
            </a>

            @auth
                {{-- Dashboard --}}
                <a href="{{ route('customer.dashboard') }}"
                   @click="window.innerWidth < 768 && (sidebarOpen = false)"
                   class="group flex items-center px-4 sm:px-5 py-3 sm:py-4 rounded-2xl transition-all duration-300
                          {{ request()->routeIs('customer.dashboard')
                             ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30'
                             : 'text-stone-600 dark:text-stone-500 hover:bg-stone-100 dark:hover:bg-stone-900 hover:text-stone-900 dark:hover:text-stone-100' }}">
                    <svg class="w-5 h-5 mr-3 sm:mr-4 flex-shrink-0 {{ request()->routeIs('customer.dashboard') ? 'text-amber-500' : 'text-stone-400 dark:text-stone-600 group-hover:text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="font-bold text-xs sm:text-[13px] tracking-wide uppercase">Dashboard</span>
                </a>
            @endauth

            {{-- Browse Shops --}}
            <a href="{{ route('customer.shoplist') }}"
               @click="window.innerWidth < 768 && (sidebarOpen = false)"
               class="group flex items-center px-4 sm:px-5 py-3 sm:py-4 rounded-2xl transition-all duration-300
                      {{ request()->routeIs('customer.shoplist') || request()->routeIs('customer.shopmenu')
                         ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30'
                         : 'text-stone-600 dark:text-stone-500 hover:bg-stone-100 dark:hover:bg-stone-900 hover:text-stone-900 dark:hover:text-stone-100' }}">
                <svg class="w-5 h-5 mr-3 sm:mr-4 flex-shrink-0 {{ request()->routeIs('customer.shoplist') || request()->routeIs('customer.shopmenu') ? 'text-amber-500' : 'text-stone-400 dark:text-stone-600 group-hover:text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <span class="font-bold text-xs sm:text-[13px] tracking-wide uppercase">Browse Shops</span>
            </a>

            @auth
                {{-- My Orders --}}
                <a href="{{ route('customer.orders') }}"
                   @click="window.innerWidth < 768 && (sidebarOpen = false)"
                   class="group flex items-center px-4 sm:px-5 py-3 sm:py-4 rounded-2xl transition-all duration-300
                          {{ request()->routeIs('customer.orders')
                             ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30'
                             : 'text-stone-600 dark:text-stone-500 hover:bg-stone-100 dark:hover:bg-stone-900 hover:text-stone-900 dark:hover:text-stone-100' }}">
                    <svg class="w-5 h-5 mr-3 sm:mr-4 flex-shrink-0 {{ request()->routeIs('customer.orders') ? 'text-amber-500' : 'text-stone-400 dark:text-stone-600 group-hover:text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <span class="font-bold text-xs sm:text-[13px] tracking-wide uppercase">Orders</span>
                </a>

                {{-- Feedback --}}
                <p class="px-5 text-[10px] font-black text-stone-400 dark:text-stone-600 uppercase tracking-[0.3em] mt-8 mb-3">Feedback Loop</p>

                <a href="{{ route('customer.reviews') }}"
                   @click="window.innerWidth < 768 && (sidebarOpen = false)"
                   class="group flex items-center px-4 sm:px-5 py-3 sm:py-4 rounded-2xl transition-all duration-300
                          {{ request()->routeIs('customer.reviews')
                             ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950 shadow-[0_10px_20px_rgba(245,158,11,0.25)]'
                             : 'text-stone-600 dark:text-stone-500 hover:bg-stone-100 dark:hover:bg-stone-900 hover:text-stone-900 dark:hover:text-stone-100' }}">
                    <svg class="w-5 h-5 mr-3 sm:mr-4 flex-shrink-0 {{ request()->routeIs('customer.reviews') ? 'text-stone-950' : 'text-stone-400 dark:text-stone-600 group-hover:text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                    <div class="flex flex-col min-w-0">
                        <span class="font-bold text-xs sm:text-[13px] tracking-wide uppercase">FeedBacks</span>
                        <span class="text-[9px] {{ request()->routeIs('customer.reviews') ? 'text-stone-800' : 'text-stone-500 dark:text-stone-600' }} font-bold truncate">Shop Owners Feedback</span>
                    </div>
                </a>

                {{-- Rewards --}}
                <p class="px-5 text-[10px] font-black text-stone-400 dark:text-stone-600 uppercase tracking-[0.3em] mt-8 mb-3">Rewards</p>

                <a href="{{ route('customer.rewards') }}"
                   @click="window.innerWidth < 768 && (sidebarOpen = false)"
                   class="group flex items-center px-4 sm:px-5 py-3 sm:py-4 rounded-2xl transition-all duration-300
                          {{ request()->routeIs('customer.rewards')
                             ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30'
                             : 'text-stone-600 dark:text-stone-500 hover:bg-stone-100 dark:hover:bg-stone-900 hover:text-stone-900 dark:hover:text-stone-100' }}">
                    <svg class="w-5 h-5 mr-3 sm:mr-4 flex-shrink-0 {{ request()->routeIs('customer.rewards') ? 'text-amber-500' : 'text-stone-400 dark:text-stone-600 group-hover:text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                    </svg>
                    <div class="flex flex-col min-w-0 flex-1">
                        <span class="font-bold text-xs sm:text-[13px] tracking-wide uppercase">Rewards</span>
                        <span class="text-[9px] {{ request()->routeIs('customer.rewards') ? 'text-amber-700 dark:text-amber-200' : 'text-stone-500 dark:text-stone-600' }} font-bold truncate">Redeem Points</span>
                    </div>
                    @if($customerPoints > 0)
                        <span class="ml-2 bg-amber-500/20 text-amber-600 dark:text-amber-400 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-500/30 flex-shrink-0">
                            {{ number_format($customerPoints) }}
                        </span>
                    @endif
                </a>
            @endauth
        </nav>

        {{-- Footer --}}
        <div class="flex-shrink-0 p-4 sm:p-6 border-t border-stone-200 dark:border-stone-800/60 bg-stone-100 dark:bg-black/30">

            <div class="flex items-center justify-between mb-4 px-2">
                <span class="text-[10px] font-bold uppercase tracking-widest text-stone-500 dark:text-stone-500">
                    Theme
                </span>
                <div class="relative group">
                    <button type="button"
                            onclick="toggleDarkMode()"
                            class="flex items-center justify-center w-9 h-9 rounded-full
                                   text-stone-700 hover:bg-stone-200
                                   dark:text-stone-200 dark:hover:bg-stone-700
                                   transition-colors"
                            aria-label="Toggle theme">
                        <svg class="w-4 h-4 dark:hidden" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                        </svg>
                        <svg class="w-4 h-4 hidden dark:block" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="4"/>
                            <path d="M12 2v2"/>
                            <path d="M12 20v2"/>
                            <path d="m4.93 4.93 1.41 1.41"/>
                            <path d="m17.66 17.66 1.41 1.41"/>
                            <path d="M2 12h2"/>
                            <path d="M20 12h2"/>
                            <path d="m6.34 17.66-1.41 1.41"/>
                            <path d="m19.07 4.93-1.41 1.41"/>
                        </svg>
                    </button>

                    <span class="pointer-events-none absolute top-full right-0 mt-2
                                 whitespace-nowrap rounded-md
                                 bg-stone-900 dark:bg-stone-800 text-white
                                 text-[11px] font-semibold tracking-wide
                                 px-2.5 py-1.5
                                 opacity-0 translate-y-1
                                 group-hover:opacity-100 group-hover:translate-y-0
                                 transition-all duration-200
                                 shadow-lg z-50">
                        Toggle theme
                        <span class="absolute -top-1 right-3 w-2 h-2 rotate-45
                                     bg-stone-900 dark:bg-stone-800"></span>
                    </span>
                </div>
            </div>

            @auth
                <div class="flex items-center mb-4 px-1">
                    <div class="relative group cursor-pointer flex-shrink-0">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-stone-200 dark:bg-stone-900 flex-shrink-0 border border-amber-500/30 overflow-hidden group-hover:border-amber-500/60 transition-colors duration-300">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'User') }}&background=f59e0b&color=0c0a09" class="w-full h-full object-cover" alt="Avatar">
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-green-500 border-[3px] border-stone-100 dark:border-stone-950 rounded-full"></div>
                    </div>
                    <div class="ml-3 sm:ml-4 overflow-hidden flex-1 min-w-0">
                        <p class="text-sm font-black text-stone-900 dark:text-white truncate uppercase tracking-tight">{{ $user->name ?? 'Customer' }}</p>
                        <div class="flex items-center">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-2"></span>
                            <p class="text-[10px] text-amber-600/80 dark:text-amber-500/80 truncate font-bold uppercase tracking-widest">Customer</p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full flex items-center justify-center gap-2 sm:gap-3 px-3 sm:px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-[0.15em] text-stone-500 border border-stone-200 dark:border-stone-900 hover:bg-red-500/5 hover:text-red-500 hover:border-red-500/30 transition-all duration-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        End Session
                    </button>
                </form>
            @else
                <div class="mb-4 px-1">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-stone-200 dark:bg-stone-900 flex-shrink-0 border border-stone-300 dark:border-stone-800 flex items-center justify-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-stone-500 dark:text-stone-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-3 sm:ml-4 overflow-hidden">
                            <p class="text-sm font-black text-stone-900 dark:text-white truncate uppercase tracking-tight">Guest</p>
                            <div class="flex items-center">
                                <span class="w-1.5 h-1.5 rounded-full bg-stone-400 dark:bg-stone-600 mr-2"></span>
                                <p class="text-[10px] text-stone-500 dark:text-stone-600 truncate font-bold uppercase tracking-widest">Not signed in</p>
                            </div>
                        </div>
                    </div>
                    <p class="text-[10px] text-stone-500 dark:text-stone-600 leading-relaxed mb-4">
                        Sign in to place orders, earn rewards, and leave reviews.
                    </p>
                </div>

                <div class="space-y-2">
                    <a href="{{ route('login') }}"
                       class="w-full flex items-center justify-center gap-2 sm:gap-3 px-3 sm:px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-[0.15em] text-amber-600 dark:text-amber-400 border border-amber-500/30 hover:bg-amber-500/10 hover:border-amber-500/50 transition-all duration-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        Sign In
                    </a>
                    <a href="{{ route('register.customer.form') }}"
                       class="w-full flex items-center justify-center gap-2 sm:gap-3 px-3 sm:px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-[0.15em] text-stone-500 border border-stone-200 dark:border-stone-900 hover:bg-stone-200 dark:hover:bg-stone-900 hover:text-stone-900 dark:hover:text-stone-100 transition-all duration-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        Create Account
                    </a>
                </div>
            @endauth
        </div>
    </aside>
</div> -->
