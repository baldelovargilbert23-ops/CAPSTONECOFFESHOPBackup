{{-- Sidebar — Alpine-controlled on mobile, always visible on desktop --}}
<aside x-cloak
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 start-0 z-50
              w-72 h-full
              transform transition-transform duration-300 ease-in-out
              lg:translate-x-0
              border-e shadow-lg"
       style="background-color: var(--bg-elevated); border-color: var(--border-soft);"
       role="dialog" tabindex="-1" aria-label="Sidebar">

    <div class="relative flex flex-col h-full max-h-full">

        {{-- Mobile Close --}}
        <button type="button"
                @click="sidebarOpen = false"
                class="lg:hidden absolute top-4 right-4 z-10 transition-colors"
                style="color: var(--text-muted);"
                onmouseover="this.style.color='var(--accent)'"
                onmouseout="this.style.color='var(--text-muted)'"
                aria-label="Close sidebar">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        {{-- Brand Header --}}
        <div class="px-6 pt-8 pb-4 flex flex-col gap-y-2">
            <div class="flex items-center gap-x-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center"
                     style="background-color: var(--forest);">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         style="color: var(--bg-primary);">
                        <path d="M17 8h1a4 4 0 1 1 0 8h-1"/>
                        <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
                        <line x1="6" y1="2" x2="6" y2="4"/>
                        <line x1="10" y1="2" x2="10" y2="4"/>
                        <line x1="14" y1="2" x2="14" y2="4"/>
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold uppercase tracking-widest truncate max-w-[140px]" style="color: var(--accent);">
                        {{ auth()->user()->tenant->name ?? 'Shop Management' }}
                    </span>
                    <h2 class="text-lg font-black tracking-tight" style="color: var(--text-primary);">Owner Portal</h2>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <div class="h-full overflow-y-auto p-4">
            <ul class="flex flex-col space-y-1.5">

                {{-- ═══════════════════════════════════════════════ --}}
                {{-- MY STORE (main branch — the tenant's own shop) --}}
                {{-- ═══════════════════════════════════════════════ --}}
                <li class="pt-1 pb-2 px-3">
                    <span class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color: var(--text-muted);">My Store</span>
                </li>

                {{-- Dashboard --}}
                <li>
                    <a href="{{ route('owner.dashboard') }}"
                       @click="sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('owner.dashboard')
                          ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                          : 'color: var(--text-primary);' }}"
                       @if(!request()->routeIs('owner.dashboard'))
                         onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                         onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                       @endif>
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="color: {{ request()->routeIs('owner.dashboard') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <rect width="7" height="9" x="3" y="3" rx="1"/>
                            <rect width="7" height="5" x="14" y="3" rx="1"/>
                            <rect width="7" height="9" x="14" y="12" rx="1"/>
                            <rect width="7" height="5" x="3" y="15" rx="1"/>
                        </svg>
                        Dashboard
                    </a>
                </li>

                {{-- Products --}}
                <li>
                    <a href="{{ route('owner.products') }}"
                       @click="sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('owner.products*')
                          ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                          : 'color: var(--text-primary);' }}"
                       @if(!request()->routeIs('owner.products*'))
                         onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                         onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                       @endif>
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="color: {{ request()->routeIs('owner.products*') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                            <line x1="12" y1="22.08" x2="12" y2="12"/>
                        </svg>
                        Products
                    </a>
                </li>

                {{-- Inventory --}}
                <li>
                    <a href="{{ route('owner.inventories') }}"
                       @click="sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('owner.inventories*')
                          ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                          : 'color: var(--text-primary);' }}"
                       @if(!request()->routeIs('owner.inventories*'))
                         onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                         onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                       @endif>
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="color: {{ request()->routeIs('owner.inventories*') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"/>
                            <path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"/>
                            <circle cx="12" cy="15" r="2"/>
                        </svg>
                        Inventory
                    </a>
                </li>

                {{-- Staff --}}
                <li>
                    <a href="{{ route('owner.staff') }}"
                       @click="sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('owner.staff*')
                          ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                          : 'color: var(--text-primary);' }}"
                       @if(!request()->routeIs('owner.staff*'))
                         onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                         onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                       @endif>
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="color: {{ request()->routeIs('owner.staff*') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        Staff
                    </a>
                </li>
                {{-- Analytics --}}
                <li>
                    <a href="{{ route('owner.analytics') }}"
                       @click="sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('owner.analytics')
                          ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                          : 'color: var(--text-primary);' }}"
                       @if(!request()->routeIs('owner.analytics'))
                         onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                         onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                       @endif>
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="color: {{ request()->routeIs('owner.analytics') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path d="M3 3v18h18"/>
                            <path d="m19 9-5 5-4-4-3 3"/>
                        </svg>
                        Analytics
                    </a>
                </li>

                {{-- Payments --}}
                <li>
                    <a href="{{ route('owner.payments') }}"
                       @click="sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('owner.payments')
                          ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                          : 'color: var(--text-primary);' }}"
                       @if(!request()->routeIs('owner.payments'))
                         onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                         onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                       @endif>
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="color: {{ request()->routeIs('owner.payments') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <rect x="2" y="5" width="20" height="14" rx="2"/>
                            <path d="M2 10h20"/>
                        </svg>
                        Payments
                    </a>
                </li>

                {{-- ═══════════════════════════════════════════════ --}}
                {{-- BRANCHES — compare & manage extra locations   --}}
                {{-- ═══════════════════════════════════════════════ --}}
                <li class="pt-4 pb-2 px-3">
                    <span class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color: var(--text-muted);">Branches</span>
                </li>

                {{-- Manage Branches --}}
                <li>
                    <a href="{{ route('owner.branches') }}"
                       @click="sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('owner.branches')
                          ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                          : 'color: var(--text-primary);' }}"
                       @if(!request()->routeIs('owner.branches'))
                         onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                         onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                       @endif>
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="color: {{ request()->routeIs('owner.branches') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path d="M3 21h18"/>
                            <path d="M5 21V7l8-4v18"/>
                            <path d="M19 21V11l-6-4"/>
                            <path d="M9 9h.01"/>
                            <path d="M9 12h.01"/>
                            <path d="M9 15h.01"/>
                            <path d="M9 18h.01"/>
                        </svg>
                        Manage Branches
                    </a>
                </li>

                {{-- ═══════════════════════════════════════════════ --}}
                {{-- TENANT — business-wide settings & programs    --}}
                {{-- ═══════════════════════════════════════════════ --}}
                <li class="pt-4 pb-2 px-3">
                    <span class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color: var(--text-muted);">Tenant</span>
                </li>

                {{-- Loyalty & Rewards --}}
                <li>
                    <a href="{{ route('owner.loyalty') }}"
                       @click="sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('owner.loyalty')
                          ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                          : 'color: var(--text-primary);' }}"
                       @if(!request()->routeIs('owner.loyalty'))
                         onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                         onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                       @endif>
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="color: {{ request()->routeIs('owner.loyalty') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <rect x="3" y="8" width="18" height="4" rx="1"/>
                            <path d="M12 8v13"/>
                            <path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/>
                            <path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/>
                        </svg>
                        Loyalty & Rewards
                    </a>
                </li>

                {{-- Reviews / Ratings --}}
                <li>
                    <a href="{{ route('owner.ratings') }}"
                       @click="sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('owner.ratings*')
                          ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                          : 'color: var(--text-primary);' }}"
                       @if(!request()->routeIs('owner.ratings*'))
                         onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                         onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                       @endif>
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="color: {{ request()->routeIs('owner.ratings*') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        Reviews
                    </a>
                </li>

                {{-- Settings --}}
                <li>
                    <a href="{{ route('owner.settings') }}"
                       @click="sidebarOpen = false"
                       class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
                       style="{{ request()->routeIs('owner.settings')
                          ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                          : 'color: var(--text-primary);' }}"
                       @if(!request()->routeIs('owner.settings'))
                         onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                         onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                       @endif>
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             style="color: {{ request()->routeIs('owner.settings') ? 'var(--accent)' : 'var(--text-muted)' }};">
                            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        Settings
                    </a>
                </li>

            </ul>
        </div>

        {{-- Footer: theme toggle + user + logout --}}
        <div class="mt-auto p-4" style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">

            {{-- Theme Toggle --}}
            <div class="flex items-center justify-between mb-3 px-2">
                <span class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Theme</span>
                <button type="button"
                        onclick="toggleTheme()"
                        class="flex items-center justify-center w-9 h-9 rounded-full transition-colors"
                        style="color: var(--text-primary);"
                        title="Toggle theme"
                        aria-label="Toggle theme">
                    <svg class="w-4 h-4 dark:hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                    </svg>
                    <svg class="w-4 h-4 hidden dark:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

            {{-- User + Logout --}}
            <div class="flex items-center justify-between gap-x-3 p-2">
                <div class="flex items-center gap-x-3 overflow-hidden">
                    <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-black"
                         style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex flex-col truncate">
                        <span class="text-xs font-semibold truncate" style="color: var(--text-primary);">{{ auth()->user()->name }}</span>
                        <span class="text-[10px] truncate leading-none mt-1" style="color: var(--text-muted);">{{ auth()->user()->email }}</span>
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
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
