<header class="sticky top-0 z-30 backdrop-blur-md transition-colors duration-300"
        style="background-color: color-mix(in srgb, var(--bg-elevated) 80%, transparent);
               border-bottom: 1px solid var(--border-soft);">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-3">

            {{-- ✅ Hamburger (mobile/tablet only) — opens sidebar via Alpine --}}
            <button type="button"
                    @click="sidebarOpen = true"
                    class="lg:hidden p-2 -ml-2 rounded-lg active:scale-95 transition-all"
                    style="color: var(--text-primary);"
                    onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                    onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                    aria-label="Open sidebar">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            {{-- Left side is intentionally empty on desktop (sidebar has the brand) --}}

            {{-- Right side actions --}}
            <div class="flex items-center gap-2 ml-auto">
                @include('layouts.partials.notification-link')

                {{-- Live indicator (hidden on small) --}}
                <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full"
                     style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); border: 1px solid color-mix(in srgb, var(--ok) 20%, transparent);">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                              style="background-color: var(--ok);"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2"
                              style="background-color: var(--ok);"></span>
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--ok);">Live</span>
                </div>

                {{-- Role badge --}}
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full"
                     style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                    <i class="fa fa-user-tie text-xs" style="color: var(--accent);"></i>
                    <span class="text-[10px] font-bold uppercase tracking-wider truncate max-w-[100px]" style="color: var(--accent);">
                        {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
                    </span>
                </div>

                {{-- Owner name + avatar (no dropdown, no email) --}}
                <div class="flex items-center gap-2 p-1">
                    <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-black text-xs"
                         style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="hidden md:block text-left max-w-[140px]">
                        <p class="text-xs font-bold leading-tight truncate" style="color: var(--text-primary);">
                            {{ auth()->user()->name }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
