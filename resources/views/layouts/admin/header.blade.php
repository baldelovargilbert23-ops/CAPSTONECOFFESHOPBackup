<header class="sticky top-0 z-30 backdrop-blur-md transition-colors duration-300"
        style="background-color: color-mix(in srgb, var(--bg-elevated) 80%, transparent);
               border-bottom: 1px solid var(--border-soft);">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-3">

            {{-- ✅ Hamburger (mobile/tablet only) --}}
            <button type="button"
                    @click="sidebarOpen = true"
                    class="lg:hidden p-2 -ml-2 rounded-lg transition-all"
                    style="color: var(--text-primary);"
                    onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                    onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                    aria-label="Open sidebar">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            {{-- Brand (visible on mobile when sidebar hidden) --}}
            <div class="flex items-center gap-2 lg:hidden">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                     style="background-color: var(--forest);">
                    <i class="fa fa-mug-hot text-sm" style="color: var(--bg-primary);"></i>
                </div>
                <span class="text-sm font-black" style="color: var(--accent);">
                    Victorias Hub
                </span>
            </div>

            {{-- Spacer to push right-side items over --}}
            <div class="flex-1 lg:flex lg:justify-end lg:items-center gap-3 hidden"></div>

            {{-- Right side actions --}}
            <div class="flex items-center gap-2 ml-auto">
                @include('layouts.partials.notification-link')
                {{-- Live indicator --}}
            </div>
        </div>
    </div>
</header>

@once
<style>
    .admin-header { font-family: 'Inter', sans-serif; }
</style>
@endonce
