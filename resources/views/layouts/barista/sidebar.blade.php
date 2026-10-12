@php
    // ✅ Get customer points for the sidebar
    $tenantId = auth()->user()->tenant_id ?? null;
    $customerPoints = 0;
    if ($tenantId) {
        $points = App\Models\CustomerPoint::where('tenant_id', $tenantId)
            ->where('user_id', auth()->id())
            ->first();
        $customerPoints = $points ? $points->points : 0;
    }
@endphp

<aside class="w-72 bg-[#0f0d0c] text-stone-200 flex-shrink-0 hidden md:flex flex-col border-r border-[#261f1a] shadow-2xl">
    {{-- Brand Section: Artisan Roast Theme --}}
    <div class="h-28 flex items-center px-8 relative overflow-hidden">
        {{-- Ember glow effect --}}
        <div class="absolute -top-12 -left-12 w-40 h-40 bg-orange-600/10 rounded-full blur-[60px]"></div>
        
        <div class="flex items-center space-x-4 group cursor-pointer relative z-10">
            <div class="w-12 h-12 bg-gradient-to-br from-orange-600 to-orange-950 rounded-2xl flex items-center justify-center shadow-[0_0_20px_rgba(234,88,12,0.2)] group-hover:scale-110 group-hover:rotate-3 transition-all duration-500 border border-orange-500/20">
                <svg class="w-7 h-7 text-orange-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 17c0 3.314-2.686 6-6 6s-6-2.686-6-6 2.686-6 6-6 6 2.686 6 6zM22 8c0 1.657-1.343 3-3 3h-2v-6h2c1.657 0 3 1.343 3 3zM17 11V5" />
                </svg>
            </div>
            <div class="flex flex-col">
                <span class="text-2xl font-black tracking-tighter leading-none text-white uppercase italic">
                    Multi<span class="text-orange-500">Tenant</span>
                </span>
                <span class="text-[9px] font-bold tracking-[0.4em] text-orange-500/50 uppercase mt-1">Victorias Coffee Shops</span>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-4 py-4 space-y-2">
        <p class="px-5 text-[10px] font-black text-stone-700 uppercase tracking-[0.3em] mb-4">Main Menu</p>
        
        {{-- Dashboard --}}
        <a href="{{ route('customer.dashboard') }}" 
           class="group flex items-center px-5 py-4 rounded-2xl transition-all duration-300 {{ request()->routeIs('customer.dashboard') ? 'bg-orange-500/10 text-orange-400 border border-orange-500/20 shadow-[inset_0_0_15px_rgba(234,88,12,0.05)]' : 'text-stone-500 hover:bg-stone-900 hover:text-stone-100' }}">
            <svg class="w-5 h-5 mr-4 {{ request()->routeIs('customer.dashboard') ? 'text-orange-500' : 'text-stone-600 group-hover:text-orange-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span class="font-bold text-[13px] tracking-wide uppercase">Dashboard</span>
        </a>

        {{-- Browse Shops --}}
        <a href="{{ route('customer.shoplist') }}" 
           class="group flex items-center px-5 py-4 rounded-2xl transition-all duration-300 {{ request()->routeIs('customer.shoplist') ? 'bg-orange-500/10 text-orange-400 border border-orange-500/20' : 'text-stone-500 hover:bg-stone-900 hover:text-stone-100' }}">
            <svg class="w-5 h-5 mr-4 {{ request()->routeIs('customer.shoplist') ? 'text-orange-500' : 'text-stone-600 group-hover:text-orange-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <span class="font-bold text-[13px] tracking-wide uppercase">Browse Coffee Shops</span>
        </a>

        {{-- My Orders --}}
        <a href="{{ route('customer.orders') }}" 
           class="group flex items-center px-5 py-4 rounded-2xl transition-all duration-300 {{ request()->routeIs('customer.orders') ? 'bg-orange-500/10 text-orange-400 border border-orange-500/20' : 'text-stone-500 hover:bg-stone-900 hover:text-stone-100' }}">
            <svg class="w-5 h-5 mr-4 {{ request()->routeIs('customer.orders') ? 'text-orange-500' : 'text-stone-600 group-hover:text-orange-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
            </svg>
            <span class="font-bold text-[13px] tracking-wide uppercase">Orders</span>
        </a>

        {{-- Community Section --}}
        <p class="px-5 text-[10px] font-black text-stone-700 uppercase tracking-[0.3em] mt-10 mb-4">Feedback Loop</p>
        
        <a href="{{ route('customer.reviews') }}" 
           class="group flex items-center px-5 py-4 rounded-2xl transition-all duration-300 {{ request()->routeIs('customer.reviews') ? 'bg-orange-600 text-white shadow-[0_10px_20px_rgba(234,88,12,0.2)]' : 'text-stone-500 hover:bg-stone-900 hover:text-stone-100' }}">
            <svg class="w-5 h-5 mr-4 {{ request()->routeIs('customer.reviews') ? 'text-white' : 'text-stone-600 group-hover:text-orange-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
            </svg>
            <div class="flex flex-col">
                <span class="font-bold text-[13px] tracking-wide uppercase">FeedBacks</span>
                <span class="text-[9px] {{ request()->routeIs('customer.reviews') ? 'text-orange-100' : 'text-stone-600 group-hover:text-stone-400' }} font-bold">Shop Owners Feedback</span>
            </div>
        </a>

        {{-- ✅ Rewards Section --}}
        <p class="px-5 text-[10px] font-black text-stone-700 uppercase tracking-[0.3em] mt-10 mb-4">Rewards</p>

        <a href="{{ route('customer.rewards') }}" 
           class="group flex items-center px-5 py-4 rounded-2xl transition-all duration-300 {{ request()->routeIs('customer.rewards') ? 'bg-orange-500/10 text-orange-400 border border-orange-500/20' : 'text-stone-500 hover:bg-stone-900 hover:text-stone-100' }}">
            <svg class="w-5 h-5 mr-4 {{ request()->routeIs('customer.rewards') ? 'text-orange-500' : 'text-stone-600 group-hover:text-orange-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
            </svg>
            <div class="flex flex-col">
                <span class="font-bold text-[13px] tracking-wide uppercase">Rewards</span>
                <span class="text-[9px] {{ request()->routeIs('customer.rewards') ? 'text-orange-100' : 'text-stone-600 group-hover:text-stone-400' }} font-bold">Redeem Points</span>
            </div>
            @if($customerPoints > 0)
                <span class="ml-auto bg-orange-500/20 text-orange-400 text-[10px] font-bold px-2.5 py-1 rounded-full border border-orange-500/30">
                    {{ number_format($customerPoints) }}
                </span>
            @endif
        </a>
    </nav>

    {{-- User Profile / Footer --}}
    <div class="p-6 border-t border-stone-900/50 bg-[#0a0908]">
        <div class="flex items-center mb-6 px-1">
            <div class="relative group cursor-pointer">
                <div class="w-12 h-12 rounded-2xl bg-stone-900 flex-shrink-0 border border-orange-900/30 overflow-hidden group-hover:border-orange-500/50 transition-colors duration-300">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=1c1917&color=f97316" class="w-full h-full object-cover" alt="Avatar">
                </div>
                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-[3px] border-[#0a0908] rounded-full shadow-lg"></div>
            </div>
            <div class="ml-4 overflow-hidden">
                <p class="text-sm font-black text-white truncate uppercase tracking-tight">{{ auth()->user()->name }}</p>
                <div class="flex items-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500 mr-2"></span>
                    <p class="text-[10px] text-orange-500/80 truncate font-bold uppercase tracking-widest">Customer</p>
                </div>
            </div>
        </div>
        
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full flex items-center justify-center gap-3 px-4 py-3.5 rounded-xl text-[10px] font-black uppercase tracking-[0.15em] text-stone-500 border border-stone-900 hover:bg-orange-500/5 hover:text-orange-500 hover:border-orange-500/20 transition-all duration-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                End Session
            </button>
        </form>
    </div>
</aside>