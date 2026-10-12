@php
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

    $navItems = [
        ['route' => 'public.landing',     'label' => 'Discover',  'active' => request()->routeIs('public.landing'), 'icon' => 'compass', 'guest' => true],
        ['route' => 'customer.dashboard', 'label' => 'Dashboard', 'active' => request()->routeIs('customer.dashboard'), 'icon' => 'home', 'guest' => false],
        ['route' => 'customer.shoplist',  'label' => 'Shops',     'active' => request()->routeIs('customer.shoplist') || request()->routeIs('customer.shopmenu'), 'icon' => 'store', 'guest' => true],
        ['route' => 'customer.orders',    'label' => 'Orders',    'active' => request()->routeIs('customer.orders'), 'icon' => 'bag', 'guest' => false],
        ['route' => 'customer.rewards',   'label' => 'Rewards',   'active' => request()->routeIs('customer.rewards'), 'icon' => 'gift', 'guest' => false],
    ];

    $visibleNav = array_filter($navItems, fn ($i) => $i['guest'] || auth()->check());
@endphp

{{-- ✅ Header — palette: cream + forest + muted amber --}}
<header class="sticky top-0 z-40 bg-[#f5f0e8] dark:bg-[#0a1f1a] border-b border-[#1a3a3a]/10 dark:border-[#c8965f]/15 transition-colors duration-300">

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- TOP ROW: Brand + Desktop Nav + Actions                     --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="max-w-7xl mx-auto px-3 sm:px-4 md:px-6 h-14 sm:h-16 flex items-center justify-between gap-2 sm:gap-3">

        {{-- LEFT: Brand + Desktop Nav --}}
        <div class="flex items-center gap-3 lg:gap-6 min-w-0 flex-1">

            {{-- Brand --}}
            <a href="{{ route('public.landing') }}"
               class="flex items-center gap-2 sm:gap-3 cursor-pointer min-w-0 flex-shrink-0">
                <div class="w-9 h-9 sm:w-10 sm:h-10 bg-[#1a3a3a] dark:bg-[#c8965f] rounded-2xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#f5f0e8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 17c0 3.314-2.686 6-6 6s-6-2.686-6-6 2.686-6 6-6 6 2.686 6 6zM22 8c0 1.657-1.343 3-3 3h-2v-6h2c1.657 0 3 1.343 3 3zM17 11V5" />
                    </svg>
                </div>
                <div class="flex flex-col min-w-0 leading-none">
                    <span class="text-sm sm:text-base lg:text-lg font-black tracking-tighter text-[#1a3a3a] dark:text-[#f0ebe0] uppercase italic truncate">
                        Multi<span class="text-[#c8965f] dark:text-[#d4a574]">Tenant</span>
                    </span>
                    <span class="hidden sm:block text-[8px] font-bold tracking-[0.35em] text-[#c8965f]/80 dark:text-[#d4a574]/70 uppercase truncate mt-1">
                        Victorias Coffee
                    </span>
                </div>
            </a>

            {{-- ✅ DESKTOP NAV --}}
            <nav class="hidden lg:flex items-center gap-0.5">
                @foreach($visibleNav as $item)
                    <a href="{{ route($item['route']) }}"
                       class="relative flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold uppercase tracking-wide transition-colors duration-150
                              {{ $item['active']
                                 ? 'text-[#c8965f] dark:text-[#d4a574]'
                                 : 'text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 hover:text-[#1a3a3a] dark:hover:text-[#f0ebe0]' }}">

                        @if($item['active'])
                            <span class="absolute inset-0 rounded-xl bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/30 dark:border-[#d4a574]/30"></span>
                        @endif

                        <svg class="w-4 h-4 flex-shrink-0 relative z-10 {{ $item['active'] ? 'text-[#c8965f] dark:text-[#d4a574]' : 'text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            @switch($item['icon'])
                                @case('compass')<circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>@break
                                @case('home')<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>@break
                                @case('store')<path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>@break
                                @case('bag')<path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>@break
                                @case('chat')<path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>@break
                                @case('gift')<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>@break
                            @endswitch
                        </svg>
                        <span class="relative z-10">{{ $item['label'] }}</span>
                        @if($item['route'] === 'customer.rewards' && $customerPoints > 0)
                            <span class="relative z-10 bg-[#c8965f]/20 dark:bg-[#d4a574]/20 text-[#c8965f] dark:text-[#d4a574] text-[9px] font-black px-1.5 py-0.5 rounded-full border border-[#c8965f]/30 dark:border-[#d4a574]/30 tabular-nums">
                                {{ number_format($customerPoints) }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- RIGHT: Actions --}}
        <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">

            {{-- Notifications Bell --}}
            @auth
                <div x-data="{
                        open: false,
                        unread: {{ auth()->user()->unreadNotifications()->count() ?? 0 }},
                        async markAllRead() {
                            await $wire.markAllAsRead();
                            this.unread = 0;
                        },
                        async markRead(id, url) {
                            await $wire.markAsRead(id);
                            if (url) window.location.href = url;
                            this.open = false;
                        }
                    }"
                     @notifications-updated.window="unread = $event.detail.count"
                     @click.away="open = false"
                     class="relative">

                    <button @click="open = !open"
                            class="relative w-9 h-9 rounded-xl
                                   bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10
                                   border border-[#1a3a3a]/10 dark:border-[#c8965f]/20
                                   flex items-center justify-center
                                   text-[#1a3a3a] dark:text-[#f0ebe0]
                                   hover:text-[#c8965f] dark:hover:text-[#d4a574] hover:border-[#c8965f]/40 dark:hover:border-[#d4a574]/40
                                   transition-colors duration-150"
                            aria-label="Notifications">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span x-show="unread > 0"
                              x-text="unread > 99 ? '99+' : unread"
                              x-cloak
                              class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-black flex items-center justify-center border-2 border-[#f5f0e8] dark:border-[#0a1f1a] tabular-nums">
                        </span>
                    </button>

                    {{-- Notifications Dropdown --}}
                    <div x-show="open"
                         x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute right-0 mt-2 w-[calc(100vw-1.5rem)] max-w-sm sm:w-80 md:w-96 bg-[#faf6ef] dark:bg-[#0f2922] border border-[#1a3a3a]/10 dark:border-[#c8965f]/20 rounded-2xl shadow-lg overflow-hidden z-50">

                        <div class="px-4 py-3 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/10 flex items-center justify-between bg-[#f5f0e8] dark:bg-[#0a1f1a]/50">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-black text-[#1a3a3a] dark:text-[#f0ebe0] uppercase tracking-wider">Notifications</h3>
                                <span x-show="unread > 0" x-text="unread + ' new'" x-cloak
                                      class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/20 tabular-nums">
                                </span>
                            </div>
                            <button type="button" x-show="unread > 0" x-cloak @click="markAllRead()"
                                    class="text-[10px] font-bold text-[#c8965f] dark:text-[#d4a574] hover:opacity-80 transition-opacity">
                                Mark all read
                            </button>
                        </div>

                        <div class="max-h-[60vh] sm:max-h-96 overflow-y-auto custom-scrollbar">
                            @php $notifs = auth()->user()->notifications()->take(8)->get(); @endphp

                            @forelse($notifs as $notif)
                                @php
                                    $isUnread = is_null($notif->read_at);
                                    $data = $notif->data ?? [];
                                    $title = $data['title'] ?? 'Notification';
                                    $message = $data['message'] ?? '';
                                    $url = $data['url'] ?? null;
                                    $color = $data['color'] ?? 'amber';
                                @endphp

                                <button type="button"
                                        @click="markRead('{{ $notif->id }}', @js($url))"
                                        class="w-full text-left px-4 py-3 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/5 hover:bg-[#f5f0e8] dark:hover:bg-[#0a1f1a]/50 transition-colors flex items-start gap-3 {{ $isUnread ? '' : 'opacity-60' }}">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 bg-{{ $color }}-500/10 border border-{{ $color }}-500/30">
                                        <svg class="w-4 h-4 text-{{ $color }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2 mb-0.5">
                                            <p class="text-xs font-bold text-[#1a3a3a] dark:text-[#f0ebe0] leading-snug {{ $isUnread ? '' : 'font-medium' }}">
                                                {{ $title }}
                                            </p>
                                            @if($isUnread)
                                                <span class="w-2 h-2 rounded-full bg-[#c8965f] dark:bg-[#d4a574] flex-shrink-0 mt-1"></span>
                                            @endif
                                        </div>
                                        @if($message)
                                            <p class="text-[11px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 line-clamp-2">{{ $message }}</p>
                                        @endif
                                        <p class="text-[9px] text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                                    </div>
                                </button>
                            @empty
                                <div class="py-12 text-center">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-[#1a3a3a]/20 dark:text-[#f0ebe0]/20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <p class="text-xs font-bold text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60">No notifications yet</p>
                                    <p class="text-[10px] text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 mt-1">We'll let you know when something happens</p>
                                </div>
                            @endforelse
                        </div>

                        @if($notifs->count() > 0)
                            <a href="{{ route('customer.notifications') }}"
                               class="block px-4 py-3 text-center text-xs font-bold text-[#c8965f] dark:text-[#d4a574] hover:opacity-80 bg-[#f5f0e8] dark:bg-[#0a1f1a]/50 border-t border-[#1a3a3a]/5 dark:border-[#c8965f]/10 transition-opacity uppercase tracking-wider">
                                View all notifications
                            </a>
                        @endif
                    </div>
                </div>
            @endauth

            {{-- Theme Toggle --}}
            <button type="button"
                    onclick="toggleDarkMode()"
                    class="flex items-center justify-center w-9 h-9 rounded-xl
                           bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10
                           border border-[#1a3a3a]/10 dark:border-[#c8965f]/20
                           text-[#1a3a3a] dark:text-[#f0ebe0]
                           hover:text-[#c8965f] dark:hover:text-[#d4a574] hover:border-[#c8965f]/40 dark:hover:border-[#d4a574]/40
                           transition-colors duration-150"
                    aria-label="Toggle theme">
                <svg class="w-4 h-4 dark:hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                </svg>
                <svg class="w-4 h-4 hidden dark:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2"/><path d="M12 20v2"/>
                    <path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/>
                    <path d="M2 12h2"/><path d="M20 12h2"/>
                    <path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
                </svg>
            </button>

            @auth
                {{-- User Menu --}}
                <div x-data="{ userMenu: false }" @click.away="userMenu = false" class="relative">
                    <button @click="userMenu = !userMenu"
                            class="flex items-center gap-2 p-1 md:pl-1 md:pr-2.5 md:py-1 rounded-xl
                                   bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10
                                   border border-[#1a3a3a]/10 dark:border-[#c8965f]/20
                                   hover:border-[#c8965f]/40 dark:hover:border-[#d4a574]/40 transition-colors duration-150">
                        <div class="relative flex-shrink-0">
                            <div class="w-7 h-7 rounded-lg bg-[#1a3a3a]/10 dark:bg-[#c8965f]/20 border border-[#c8965f]/30 overflow-hidden">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'User') }}&background=c8965f&color=f5f0e8"
                                     class="w-full h-full object-cover" alt="Avatar" loading="lazy">
                            </div>
                            <div class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-500 border-2 border-[#f5f0e8] dark:border-[#0a1f1a] rounded-full"></div>
                        </div>
                        <div class="hidden md:flex flex-col items-start min-w-0 leading-none">
                            <p class="text-[11px] font-black text-[#1a3a3a] dark:text-[#f0ebe0] truncate uppercase tracking-tight max-w-[100px]">
                                {{ $user->name ?? 'Customer' }}
                            </p>
                            <p class="text-[8px] text-[#c8965f]/80 dark:text-[#d4a574]/80 font-bold uppercase tracking-widest mt-0.5">
                                Customer
                            </p>
                        </div>
                        <svg class="w-3.5 h-3.5 text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 hidden md:block transition-transform duration-150"
                             :class="userMenu ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    {{-- User Dropdown --}}
                    <div x-show="userMenu"
                         x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute right-0 mt-2 w-[calc(100vw-1.5rem)] max-w-xs sm:w-64 bg-[#faf6ef] dark:bg-[#0f2922] border border-[#1a3a3a]/10 dark:border-[#c8965f]/20 rounded-2xl shadow-lg overflow-hidden z-50">

                        <div class="px-4 py-4 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/10 bg-[#f5f0e8] dark:bg-[#0a1f1a]/50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-[#1a3a3a]/10 dark:bg-[#c8965f]/20 border border-[#c8965f]/30 overflow-hidden flex-shrink-0">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'User') }}&background=c8965f&color=f5f0e8"
                                         class="w-full h-full object-cover" alt="Avatar" loading="lazy">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-black text-[#1a3a3a] dark:text-[#f0ebe0] truncate uppercase tracking-tight">
                                        {{ $user->name ?? 'Customer' }}
                                    </p>
                                    <p class="text-[10px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 truncate">{{ $user->email ?? '' }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Logout --}}
                        <form method="POST" action="{{ route('logout') }}" class="p-2">
                            @csrf
                            <button class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-black uppercase tracking-[0.15em] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 hover:bg-red-500/5 hover:text-red-500 transition-colors duration-150">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                End Session
                            </button>
                        </form>
                    </div>
                </div>
            @else
                {{-- Guest CTAs --}}
                <a href="{{ route('login') }}"
                   class="flex items-center gap-1.5 px-2.5 sm:px-3.5 py-2 rounded-xl
                          text-[10px] sm:text-[11px] font-black uppercase tracking-[0.1em] sm:tracking-[0.15em]
                          text-[#c8965f] dark:text-[#d4a574]
                          border border-[#c8965f]/40 dark:border-[#d4a574]/40
                          hover:bg-[#c8965f]/10 dark:hover:bg-[#d4a574]/10 hover:border-[#c8965f]/60 dark:hover:border-[#d4a574]/60
                          transition-colors duration-150">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="hidden sm:inline">Sign In</span>
                </a>
                <a href="{{ route('register.customer.form') }}"
                   class="hidden md:flex items-center gap-2 px-3.5 py-2 rounded-xl
                          text-[11px] font-black uppercase tracking-[0.15em]
                          text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60
                          border border-[#1a3a3a]/10 dark:border-[#c8965f]/20
                          hover:bg-[#1a3a3a]/5 dark:hover:bg-[#c8965f]/10 hover:text-[#1a3a3a] dark:hover:text-[#f0ebe0]
                          transition-colors duration-150">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    Create Account
                </a>
            @endauth
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- BOTTOM ROW: Nav strip — mobile only                        --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="lg:hidden border-t border-[#1a3a3a]/10 dark:border-[#c8965f]/15">
        <nav class="max-w-7xl mx-auto px-2 sm:px-4 flex items-center gap-1 overflow-x-auto no-scrollbar py-1.5">
            @foreach($visibleNav as $item)
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-lg whitespace-nowrap
                          text-[11px] font-bold uppercase tracking-wide transition-colors duration-150 flex-shrink-0
                          {{ $item['active']
                             ? 'bg-[#c8965f]/10 dark:bg-[#d4a574]/10 text-[#c8965f] dark:text-[#d4a574] border border-[#c8965f]/30 dark:border-[#d4a574]/30'
                             : 'text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 hover:text-[#1a3a3a] dark:hover:text-[#f0ebe0] border border-transparent' }}">
                    <svg class="w-3.5 h-3.5 flex-shrink-0 {{ $item['active'] ? 'text-[#c8965f] dark:text-[#d4a574]' : 'text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50' }}"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        @switch($item['icon'])
                            @case('compass')<circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>@break
                            @case('home')<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>@break
                            @case('store')<path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>@break
                            @case('bag')<path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>@break
                            @case('chat')<path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>@break
                            @case('gift')<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>@break
                        @endswitch
                    </svg>
                    <span>{{ $item['label'] }}</span>
                    @if($item['route'] === 'customer.rewards' && $customerPoints > 0)
                        <span class="bg-[#c8965f]/20 dark:bg-[#d4a574]/20 text-[#c8965f] dark:text-[#d4a574] text-[9px] font-black px-1.5 py-0.5 rounded-full border border-[#c8965f]/30 dark:border-[#d4a574]/30 tabular-nums">
                            {{ number_format($customerPoints) }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>
</header>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #c8965f; border-radius: 10px; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>