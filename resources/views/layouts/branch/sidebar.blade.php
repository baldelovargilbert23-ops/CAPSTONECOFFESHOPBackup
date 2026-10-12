<div id="branch-sidebar"
     x-cloak
     :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
     class="w-80 max-w-[85vw] h-full fixed inset-y-0 start-0 z-[80]
            border-e shadow-lg transform transition-transform duration-300 ease-out
            lg:block lg:end-auto lg:bottom-0"
     role="dialog" tabindex="-1" aria-label="Sidebar"
     style="background-color: var(--bg-elevated); border-color: var(--border-soft);">

    <div class="relative flex flex-col h-full max-h-full">

        <!-- Mobile Close -->
        <button type="button"
                x-on:click="sidebarOpen = false"
                class="lg:hidden absolute top-4 right-4 z-10 p-1.5 rounded-lg transition"
                style="color: var(--text-muted);"
                onmouseover="this.style.color='var(--accent)'"
                onmouseout="this.style.color='var(--text-muted)'"
                aria-label="Close menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- HEADER — Branch Logo --}}
        <div class="px-6 pt-8 pb-4">
            @php
                $sidebarBranch = App\Models\Branch::find(auth()->user()->branch_id ?? 0);
            @endphp

            <div class="flex items-center gap-x-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl overflow-hidden flex items-center justify-center"
                     style="background-color: var(--forest);">
                    @if($sidebarBranch && $sidebarBranch->logo_path)
                        <img src="{{ Storage::url($sidebarBranch->logo_path) }}"
                             alt="{{ $sidebarBranch->name }}"
                             class="w-full h-full object-cover">
                    @elseif($sidebarBranch && $sidebarBranch->tenant && $sidebarBranch->tenant->logo_path)
                        <img src="{{ Storage::url($sidebarBranch->tenant->logo_path) }}"
                             alt="{{ $sidebarBranch->tenant->name }}"
                             class="w-full h-full object-cover">
                    @else
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="color: var(--bg-primary);">
                            <path d="M17 8h1a4 4 0 1 1 0 8h-1"/>
                            <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
                            <line x1="6" y1="2" x2="6" y2="4"/>
                            <line x1="10" y1="2" x2="10" y2="4"/>
                            <line x1="14" y1="2" x2="14" y2="4"/>
                        </svg>
                    @endif
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-bold uppercase tracking-widest truncate" style="color: var(--accent);">
                        {{ auth()->user()->tenant->name ?? 'Coffee Shop' }}
                    </span>
                    <h2 class="text-lg font-black tracking-tight truncate" style="color: var(--text-primary);">
                        Branch Portal
                    </h2>
                </div>
            </div>
        </div>

        {{-- LOYALTY STATUS BADGE --}}
        @php
            $isLoyaltyEnabled = $sidebarBranch ? $sidebarBranch->isLoyaltyEnabled() : false;
        @endphp

        <div class="px-4 pb-3">
            <div class="flex items-center justify-between px-3 py-2.5 rounded-xl"
                 style="background-color: {{ $isLoyaltyEnabled
                    ? 'color-mix(in srgb, var(--ok) 10%, transparent)'
                    : 'color-mix(in srgb, var(--warn) 10%, transparent)' }};
                        border: 1px solid {{ $isLoyaltyEnabled
                    ? 'color-mix(in srgb, var(--ok) 20%, transparent)'
                    : 'color-mix(in srgb, var(--warn) 20%, transparent)' }};">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                         style="background-color: {{ $isLoyaltyEnabled
                            ? 'color-mix(in srgb, var(--ok) 20%, transparent)'
                            : 'color-mix(in srgb, var(--warn) 20%, transparent)' }};
                                color: {{ $isLoyaltyEnabled ? 'var(--ok)' : 'var(--warn)' }};">
                        <i class="fa fa-gift text-xs"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="text-xs font-bold block truncate"
                              style="color: {{ $isLoyaltyEnabled ? 'var(--ok)' : 'var(--warn)' }};">
                            {{ $isLoyaltyEnabled ? 'Rewards Active' : 'Rewards Off' }}
                        </span>
                        <p class="text-[9px] leading-tight truncate" style="color: var(--text-muted);">
                            {{ $isLoyaltyEnabled ? 'Customers can earn & redeem' : 'Branch not accepting rewards' }}
                        </p>
                    </div>
                </div>
                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $isLoyaltyEnabled ? 'animate-pulse' : '' }}"
                      style="background-color: {{ $isLoyaltyEnabled ? 'var(--ok)' : 'var(--warn)' }};"></span>
            </div>
        </div>

        {{-- NAVIGATION --}}
        <div class="h-full overflow-y-auto px-4 pb-4">
            <ul class="flex flex-col space-y-1.5">

                <!-- Dashboard -->
                <li>
                    <a href="{{ route('branch.dashboard') }}"
                       x-on:click="if (window.innerWidth < 1024) sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('branch.dashboard')
                           ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                           : 'color: var(--text-primary);' }}"
                       onmouseover="if(!this.style.backgroundColor) this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'"
                       onmouseout="if(!this.dataset.active) this.style.backgroundColor=''"
                       data-active="{{ request()->routeIs('branch.dashboard') ? '1' : '' }}">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                             style="color: {{ request()->routeIs('branch.dashboard') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                        Dashboard
                    </a>
                </li>

                <li>
                    <a href="{{ route('notifications.index') }}"
                       x-on:click="if (window.innerWidth < 1024) sidebarOpen = false"
                       class="flex min-h-10 items-center gap-x-3.5 rounded-lg px-3 py-2.5 text-sm font-medium transition-all"
                       style="color: var(--text-primary);"
                       onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                       onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'">
                        <i class="fa fa-bell w-4 text-center" aria-hidden="true"></i>
                        Notifications
                        @php($branchUnreadNotificationCount = auth()->user()->unreadNotifications()->count())
                        @if($branchUnreadNotificationCount > 0)
                            <span class="ms-auto rounded-full bg-rose-600 px-2 py-0.5 text-[10px] font-bold text-white">
                                {{ $branchUnreadNotificationCount > 99 ? '99+' : $branchUnreadNotificationCount }}
                            </span>
                        @endif
                    </a>
                </li>

                @if(auth()->user()->role === 'branch_manager')
                <li>
                    <a href="{{ route('branch.operations') }}"
                       x-on:click="if (window.innerWidth < 1024) sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('branch.operations')
                           ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                           : 'color: var(--text-primary);' }}"
                       data-active="{{ request()->routeIs('branch.operations') ? '1' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                             style="color: {{ request()->routeIs('branch.operations') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18v4H3zm0 6h8v10H3zm10 0h8v10h-8z" />
                        </svg>
                        Branch operations
                    </a>
                </li>
                @endif

                <!-- CASHIER: POS -->
                @if(auth()->user()->role === 'cashier')
                <li>
                    <a href="{{ route('branch.cashier') }}"
                       x-on:click="if (window.innerWidth < 1024) sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('branch.cashier')
                           ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                           : 'color: var(--text-primary);' }}"
                       onmouseover="if(!this.style.backgroundColor) this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'"
                       onmouseout="if(!this.dataset.active) this.style.backgroundColor=''"
                       data-active="{{ request()->routeIs('branch.cashier') ? '1' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                             style="color: {{ request()->routeIs('branch.cashier') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a.75.75 0 001.5 0v-3.75a.75.75 0 00-1.5 0v3.75zm3.75-3.75a.75.75 0 00-1.5 0V18a.75.75 0 001.5 0v-3.75zm3.75-3.75a.75.75 0 00-1.5 0v9a.75.75 0 001.5 0v-9zm3.75-3.75a.75.75 0 00-1.5 0v12a.75.75 0 001.5 0v-12zm3.75-3.75a.75.75 0 00-1.5 0v15a.75.75 0 001.5 0v-15z" />
                        </svg>
                        POS / Cashier
                        <span class="ml-auto text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"
                              style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);">Active</span>
                    </a>
                </li>
                @endif

                <!-- BARISTA -->
                @if(auth()->user()->role === 'barista')
                <li>
                    <a href="{{ route('branch.barista') }}"
                       x-on:click="if (window.innerWidth < 1024) sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('branch.barista')
                           ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                           : 'color: var(--text-primary);' }}"
                       onmouseover="if(!this.style.backgroundColor) this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'"
                       onmouseout="if(!this.dataset.active) this.style.backgroundColor=''"
                       data-active="{{ request()->routeIs('branch.barista') ? '1' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                             style="color: {{ request()->routeIs('branch.barista') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        Barista Dashboard
                        <span class="ml-auto text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"
                              style="background-color: color-mix(in srgb, var(--ok) 20%, transparent); color: var(--ok);">Orders</span>
                    </a>
                </li>
                @endif

                <!-- SECTION: Operations -->
                <li class="pt-4 pb-2 px-3">
                    <span class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color: var(--text-muted);">Operations</span>
                </li>

                <!-- Products -->
                <li>
                    <a href="{{ route('branch.product') }}"
                       x-on:click="if (window.innerWidth < 1024) sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('branch.product')
                           ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                           : 'color: var(--text-primary);' }}"
                       onmouseover="if(!this.style.backgroundColor) this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'"
                       onmouseout="if(!this.dataset.active) this.style.backgroundColor=''"
                       data-active="{{ request()->routeIs('branch.product') ? '1' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                             style="color: {{ request()->routeIs('branch.product') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375 7.444 2.25 12 2.25s8.25 1.847 8.25 4.125zm0 0v5.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125v-5.25m0 10.5c0 2.278 3.694 4.125 8.25 4.125s8.25-1.847 8.25-4.125v-5.25" />
                        </svg>
                        Products
                    </a>
                </li>

                <!-- Inventory -->
                <li>
                    <a href="{{ route('branch.inventory') }}"
                       x-on:click="if (window.innerWidth < 1024) sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('branch.inventory')
                           ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                           : 'color: var(--text-primary);' }}"
                       onmouseover="if(!this.style.backgroundColor) this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'"
                       onmouseout="if(!this.dataset.active) this.style.backgroundColor=''"
                       data-active="{{ request()->routeIs('branch.inventory') ? '1' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                             style="color: {{ request()->routeIs('branch.inventory') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375 7.444 2.25 12 2.25s8.25 1.847 8.25 4.125zm0 0v5.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125v-5.25m0 10.5c0 2.278 3.694 4.125 8.25 4.125s8.25-1.847 8.25-4.125v-5.25" />
                        </svg>
                        Inventory
                    </a>
                </li>

                <!-- SECTION: Management -->
                @if(auth()->user()->role === 'branch_manager')
                <li class="pt-4 pb-2 px-3">
                    <span class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color: var(--text-muted);">Management</span>
                </li>

                <li>
                    <a href="{{ route('branch.staff') }}"
                       x-on:click="if (window.innerWidth < 1024) sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('branch.staff')
                           ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                           : 'color: var(--text-primary);' }}"
                       onmouseover="if(!this.style.backgroundColor) this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'"
                       onmouseout="if(!this.dataset.active) this.style.backgroundColor=''"
                       data-active="{{ request()->routeIs('branch.staff') ? '1' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                             style="color: {{ request()->routeIs('branch.staff') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                        Staff
                    </a>
                </li>
                @endif

                <!-- SECTION: Business Intelligence -->
                <li class="pt-4 pb-2 px-3">
                    <span class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color: var(--text-muted);">Business Intelligence</span>
                </li>

                <!-- Ratings -->
                <li>
                    <a href="{{ route('branch.rating') }}"
                       x-on:click="if (window.innerWidth < 1024) sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('branch.rating')
                           ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                           : 'color: var(--text-primary);' }}"
                       onmouseover="if(!this.style.backgroundColor) this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'"
                       onmouseout="if(!this.dataset.active) this.style.backgroundColor=''"
                       data-active="{{ request()->routeIs('branch.rating') ? '1' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                             style="color: {{ request()->routeIs('branch.rating') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                        Ratings
                    </a>
                </li>

                <!-- Analytics -->
                <li>
                    <a href="{{ route('branch.analytics') }}"
                       x-on:click="if (window.innerWidth < 1024) sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('branch.analytics')
                           ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                           : 'color: var(--text-primary);' }}"
                       onmouseover="if(!this.style.backgroundColor) this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'"
                       onmouseout="if(!this.dataset.active) this.style.backgroundColor=''"
                       data-active="{{ request()->routeIs('branch.analytics') ? '1' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                             style="color: {{ request()->routeIs('branch.analytics') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-1 3m4.5-3v4.5m-3 0h6" />
                        </svg>
                        Analytics
                    </a>
                </li>

                <!-- SECTION: Settings -->
                @if(auth()->user()->role === 'branch_manager')
                <li class="pt-4 pb-2 px-3">
                    <span class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color: var(--text-muted);">Settings</span>
                </li>

                <li>
                    <a href="{{ route('branch.settings') }}"
                       x-on:click="if (window.innerWidth < 1024) sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('branch.settings')
                           ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                           : 'color: var(--text-primary);' }}"
                       onmouseover="if(!this.style.backgroundColor) this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'"
                       onmouseout="if(!this.dataset.active) this.style.backgroundColor=''"
                       data-active="{{ request()->routeIs('branch.settings') ? '1' : '' }}">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                             style="color: {{ request()->routeIs('branch.settings') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.991l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Settings
                    </a>
                </li>
                @endif

            </ul>
        </div>

        {{-- FOOTER — Theme Toggle + User --}}
        <div class="mt-auto p-4"
             style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">

            <div class="flex items-center justify-between mb-3 px-2">
                <span class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">
                    Theme
                </span>
                <button type="button"
                        onclick="toggleDarkMode()"
                        class="group flex items-center justify-center w-9 h-9 rounded-full transition-colors"
                        style="color: var(--text-primary);"
                        title="Toggle theme"
                        aria-label="Toggle theme">
                    <svg class="w-4 h-4 dark:hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                    </svg>
                    <svg class="w-4 h-4 hidden dark:block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
            </div>

            <div class="flex items-center justify-between gap-x-3 p-2">
                <div class="flex items-center gap-x-3 min-w-0">
                    <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-black"
                         style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-xs font-semibold truncate" style="color: var(--text-primary);">{{ auth()->user()->name }}</span>
                        <span class="text-[10px] truncate" style="color: var(--text-muted);">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit"
                            class="p-2 rounded-lg transition-colors"
                            style="color: var(--text-muted);"
                            onmouseover="this.style.color='var(--bad)'; this.style.backgroundColor='color-mix(in srgb, var(--bad) 10%, transparent)'"
                            onmouseout="this.style.color='var(--text-muted)'; this.style.backgroundColor=''"
                            title="Logout"
                            aria-label="Logout">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
