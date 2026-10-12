<div>
    {{-- Auto-refresh --}}
    <div wire:poll.30s class="hidden"></div>

    <main class="relative min-h-screen bg-[#f5f0e8] dark:bg-[#0a1f1a] font-sans text-[#1a3a3a] dark:text-[#f0ebe0] transition-colors duration-300">
        <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6 lg:space-y-8">

            {{-- Flash Messages --}}
            @if(session()->has('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="p-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-400 rounded-2xl text-sm font-medium flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <i class="fa fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button @click="show = false" class="text-emerald-600 hover:opacity-70"><i class="fa fa-times text-xs"></i></button>
                </div>
            @endif

            @if(session()->has('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="p-4 bg-red-500/10 border border-red-500/30 text-red-700 dark:text-red-400 rounded-2xl text-sm font-medium flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <i class="fa fa-exclamation-circle"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button @click="show = false" class="text-red-600 hover:opacity-70"><i class="fa fa-times text-xs"></i></button>
                </div>
            @endif

            {{-- ============================================= --}}
            {{-- LIVE ORDER STATUS BANNER --}}
            {{-- ============================================= --}}
            @if(isset($activeOrder) && $activeOrder)
                @php
                    $statusMeta = match($activeOrder->status) {
                        'pending' => ['bg' => 'bg-[#c8965f]', 'icon' => 'fa-clock', 'label' => 'Pending', 'msg' => 'Waiting for the shop to accept your order'],
                        'preparing' => ['bg' => 'bg-blue-600', 'icon' => 'fa-fire', 'label' => 'Preparing', 'msg' => 'Your coffee is being brewed'],
                        'ready' => ['bg' => 'bg-emerald-600', 'icon' => 'fa-check-circle', 'label' => 'Ready for Pickup', 'msg' => 'Come grab your order!'],
                        default => ['bg' => 'bg-stone-700', 'icon' => 'fa-info-circle', 'label' => 'Active', 'msg' => 'Order in progress'],
                    };
                    $elapsed = $activeOrder->created_at->diffInMinutes(now());
                @endphp

                <a href="{{ route('customer.orders') }}"
                   class="group relative overflow-hidden rounded-3xl {{ $statusMeta['bg'] }} p-5 sm:p-6 shadow-lg border border-white/10 block hover:-translate-y-0.5 transition-all duration-300">
                    <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="relative flex-shrink-0">
                                <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center border border-white/20">
                                    <i class="fa {{ $statusMeta['icon'] }} text-white text-2xl"></i>
                                </div>
                                @if($activeOrder->status !== 'ready')
                                    <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-white flex items-center justify-center">
                                        <span class="w-2 h-2 rounded-full bg-[#c8965f] animate-ping absolute"></span>
                                        <span class="w-2 h-2 rounded-full bg-[#c8965f] relative"></span>
                                    </span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-0.5">
                                    <span class="text-[10px] font-black text-white/70 uppercase tracking-widest">Order #{{ $activeOrder->order_number }}</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/20 text-white font-bold uppercase tracking-wider">{{ $statusMeta['label'] }}</span>
                                </div>
                                <p class="text-white font-bold text-lg sm:text-xl truncate">{{ $statusMeta['msg'] }}</p>
                                <p class="text-white/70 text-xs mt-0.5 flex items-center gap-2 flex-wrap">
                                    <span><i class="fa fa-store mr-1"></i>{{ $activeOrder->branch?->name ?? 'Main Store' }}</span>
                                    <span class="text-white/40">•</span>
                                    <span><i class="fa fa-clock mr-1"></i>{{ $elapsed }} min ago</span>
                                    <span class="text-white/40">•</span>
                                    <span class="font-bold text-white">₱{{ number_format($activeOrder->total_amount, 2) }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 self-end sm:self-auto">
                            <span class="hidden sm:inline text-xs font-bold text-white/90 group-hover:text-white transition">Track Order</span>
                            <i class="fa fa-arrow-right text-white group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </a>
            @endif

            {{-- ============================================= --}}
            {{-- HERO SECTION --}}
            {{-- ============================================= --}}
            <section class="relative overflow-hidden bg-[#faf6ef] dark:bg-[#0f2922] rounded-3xl p-6 sm:p-8 lg:p-12 shadow-lg border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 transition-colors duration-300">
                <div class="relative z-10 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 rounded-full text-[10px] font-bold uppercase tracking-widest text-[#c8965f] dark:text-[#d4a574] mb-4">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#c8965f] dark:bg-[#d4a574] animate-pulse"></span>
                        Coffee Lounge
                    </div>

                    @php
                        $hour = (int) now()->format('H');
                        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
                        $greetingIcon = $hour < 12 ? 'fa-sun' : ($hour < 18 ? 'fa-cloud-sun' : 'fa-moon');
                        $userName = Auth::check() ? explode(' ', Auth::user()->name)[0] : 'Guest';
                    @endphp
                    <h1 class="text-3xl sm:text-4xl lg:text-6xl font-black tracking-tight leading-tight mb-3 text-[#1a3a3a] dark:text-[#f0ebe0]">
                        {{ $greeting }},<br class="sm:hidden">
                        <span class="text-[#c8965f] dark:text-[#d4a574]">
                            {{ $userName }}
                        </span>
                        <i class="fa {{ $greetingIcon }} text-2xl sm:text-3xl lg:text-4xl text-[#c8965f] dark:text-[#d4a574] ml-1 align-middle"></i>
                    </h1>
                    <p class="text-[#1a3a3a]/70 dark:text-[#f0ebe0]/60 text-sm sm:text-base max-w-md">
                        Discover the best coffee shops, compare ratings, and share your experience.
                    </p>
                </div>
            </section>

            {{-- ============================================= --}}
            {{-- RECENT ORDERS --}}
            {{-- ============================================= --}}
            <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-3xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 shadow-lg overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/10 flex items-center justify-between">
                    <h3 class="font-black text-[#1a3a3a] dark:text-[#f0ebe0] flex items-center gap-2 text-sm sm:text-base">
                        <i class="fa fa-receipt text-[#c8965f] dark:text-[#d4a574]"></i>
                        Recent Orders
                    </h3>
                    <a href="{{ route('customer.orders') }}"
                       class="text-[10px] sm:text-xs font-bold text-[#c8965f] dark:text-[#d4a574] hover:opacity-80 flex items-center gap-1">
                        View All <i class="fa fa-arrow-right text-[9px]"></i>
                    </a>
                </div>

                <div class="divide-y divide-[#1a3a3a]/5 dark:divide-[#c8965f]/10">
                    @forelse($recentOrders as $order)
                        @php
                            $oStatus = match($order->status) {
                                'pending' => ['color' => 'bg-[#c8965f]/10 text-[#c8965f] border-[#c8965f]/30', 'icon' => 'fa-clock'],
                                'preparing' => ['color' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30', 'icon' => 'fa-fire'],
                                'ready' => ['color' => 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/30', 'icon' => 'fa-check-circle'],
                                'completed' => ['color' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30', 'icon' => 'fa-check-double'],
                                'cancelled' => ['color' => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/30', 'icon' => 'fa-ban'],
                                default => ['color' => 'bg-stone-500/10 text-stone-600 dark:text-stone-400 border-stone-500/30', 'icon' => 'fa-circle'],
                            };
                            $orderLogo = \App\Support\ImageUrlResolver::resolve(
                                $order->tenant_logo_path ?? $order->branch_logo_path ?? null,
                                \App\Support\ImageUrlResolver::svgPlaceholder()
                            );
                        @endphp
                        <div wire:key="order-{{ $order->id }}"
                             class="p-3 sm:p-4 hover:bg-[#c8965f]/5 dark:hover:bg-[#d4a574]/5 transition group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl overflow-hidden bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 flex items-center justify-center flex-shrink-0">
                                    <img src="{{ $orderLogo }}"
                                         class="w-full h-full object-cover"
                                         alt="{{ $order->tenant_name ?? 'Shop' }}"
                                         loading="lazy"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-full h-full items-center justify-center hidden">
                                        <i class="fa fa-store text-[#c8965f] dark:text-[#d4a574] text-sm"></i>
                                    </div>
                                </div>

                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap mb-0.5">
                                        <span class="text-xs font-bold text-[#1a3a3a] dark:text-[#f0ebe0] truncate">
                                            {{ $order->tenant_name ?? 'Coffee Shop' }}
                                        </span>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded-full font-bold uppercase tracking-wider border {{ $oStatus['color'] }} flex items-center gap-1">
                                            <i class="fa {{ $oStatus['icon'] }} text-[8px]"></i>
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 flex items-center gap-1.5 flex-wrap">
                                        <span class="font-mono">#{{ $order->order_number }}</span>
                                        <span>•</span>
                                        <span>{{ $order->created_at->diffForHumans() }}</span>
                                        <span>•</span>
                                        <span class="font-bold text-[#c8965f] dark:text-[#d4a574]">₱{{ number_format($order->total_amount, 2) }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 flex-shrink-0">
                                    @if(in_array($order->status, ['completed', 'cancelled']))
                                        <button wire:click="reorder({{ $order->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="reorder({{ $order->id }})"
                                                class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-bold bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] hover:opacity-90 transition disabled:opacity-50">
                                            <span wire:loading.remove wire:target="reorder({{ $order->id }})">
                                                <i class="fa fa-redo text-[9px]"></i>
                                                Reorder
                                            </span>
                                            <span wire:loading wire:target="reorder({{ $order->id }})">
                                                <i class="fa fa-spinner fa-spin text-[9px]"></i>
                                            </span>
                                        </button>
                                    @endif
                                    <a href="{{ route('customer.orders') }}"
                                       class="p-1.5 rounded-lg text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 hover:text-[#c8965f] dark:hover:text-[#d4a574] hover:bg-[#c8965f]/10 dark:hover:bg-[#d4a574]/10 transition"
                                       title="View order">
                                        <i class="fa fa-arrow-right text-xs"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 border border-[#1a3a3a]/10 dark:border-[#c8965f]/20 flex items-center justify-center mb-3">
                                <i class="fa fa-receipt text-2xl text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40"></i>
                            </div>
                            <p class="text-sm font-bold text-[#1a3a3a] dark:text-[#f0ebe0] mb-1">No orders yet</p>
                            <p class="text-xs text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60">Start your coffee journey today</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- ============================================= --}}
            {{-- STATS GRID --}}
            {{-- ============================================= --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach([
                    ['icon' => 'fa-star',         'label' => 'Reviews Given', 'value' => data_get($stats, 'total_reviews', 0)],
                    ['icon' => 'fa-shopping-bag', 'label' => 'Orders Placed', 'value' => data_get($stats, 'total_orders', 0)],
                    ['icon' => 'fa-coffee',       'label' => 'Total Spent',   'value' => '₱' . number_format(data_get($stats, 'total_spent', 0), 2)],
                ] as $stat)
                    <div class="group bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl p-5 border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 hover:border-[#c8965f]/40 dark:hover:border-[#d4a574]/40 transition-all duration-300 hover:-translate-y-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fa {{ $stat['icon'] }} text-[#c8965f] dark:text-[#d4a574] text-lg"></i>
                            </div>
                            <p class="text-[10px] font-bold text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 uppercase tracking-widest">{{ $stat['label'] }}</p>
                        </div>
                        <p class="text-2xl sm:text-3xl font-black text-[#1a3a3a] dark:text-[#f0ebe0]">{{ $stat['value'] }}</p>
                    </div>
                @endforeach

                <div class="group bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl p-5 border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 hover:border-[#c8965f]/40 dark:hover:border-[#d4a574]/40 transition-all duration-300 hover:-translate-y-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fa fa-trophy text-[#c8965f] dark:text-[#d4a574] text-lg"></i>
                        </div>
                        <p class="text-[10px] font-bold text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 uppercase tracking-widest">Favorite Shop</p>
                    </div>
                    <p class="text-sm font-bold text-[#1a3a3a] dark:text-[#f0ebe0] truncate">
                        {{ data_get($stats, 'favorite_shop.name') ?? 'No orders yet' }}
                    </p>
                </div>
            </div>

            {{-- ============================================= --}}
            {{-- NEWEST BRANCHES --}}
            {{-- ============================================= --}}
            @if(isset($newestShops) && count($newestShops) > 0)
                <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-3xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/10">
                        <h3 class="font-black text-[#1a3a3a] dark:text-[#f0ebe0] flex items-center gap-2 text-lg">
                            <i class="fa fa-newspaper text-[#c8965f] dark:text-[#d4a574]"></i> Newest Coffee Shops
                            <span class="text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 font-normal">Freshly added</span>
                        </h3>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 p-5 sm:p-6">
                        @foreach($newestShops as $shop)
                            @php
                                $newestLogo = \App\Support\ImageUrlResolver::resolve(
                                    $shop->branch_logo_path ?? null,
                                    \App\Support\ImageUrlResolver::resolve(
                                        $shop->tenant_logo_path ?? null,
                                        \App\Support\ImageUrlResolver::svgPlaceholder()
                                    )
                                );
                            @endphp
                            {{-- ✅ FIXED: now passes both tenant AND branch --}}
                            <a href="{{ route('customer.shopmenu', ['tenant' => $shop->tenant_id, 'branch' => $shop->branch_id]) }}"
                               wire:key="branch-{{ $shop->branch_id }}"
                               class="group"
                               title="{{ $shop->branch_name }} — {{ $shop->tenant_name }}">
                                <div class="text-center p-4 rounded-2xl bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 group-hover:border-[#c8965f]/40 dark:group-hover:border-[#d4a574]/40 group-hover:-translate-y-1 transition-all duration-300">
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto rounded-full overflow-hidden bg-[#1a3a3a] dark:bg-[#c8965f] flex items-center justify-center text-[#f5f0e8] dark:text-[#0a1f1a] text-lg sm:text-xl font-black mb-3 group-hover:scale-110 transition-transform border-2 border-[#faf6ef] dark:border-[#0f2922]">
                                        <img src="{{ $newestLogo }}"
                                             class="w-full h-full object-cover"
                                             alt="{{ $shop->branch_name }}"
                                             loading="lazy"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="w-full h-full items-center justify-center hidden">
                                            {{ substr($shop->branch_name, 0, 1) }}
                                        </div>
                                    </div>
                                    <p class="font-semibold text-[#1a3a3a] dark:text-[#f0ebe0] text-xs sm:text-sm truncate">
                                        {{ $shop->branch_name }}
                                    </p>
                                    <p class="text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 truncate mt-0.5">
                                        {{ $shop->tenant_name }}
                                    </p>
                                    <p class="text-[10px] text-[#c8965f] dark:text-[#d4a574] mt-1 font-bold uppercase tracking-wider">New</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ============================================= --}}
            {{-- OWNER REPLY + RECENT REVIEWS --}}
            {{-- ============================================= --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-1 bg-[#c8965f]/10 dark:bg-[#d4a574]/10 rounded-3xl border border-[#c8965f]/30 dark:border-[#d4a574]/30 p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-[#c8965f]/20 dark:bg-[#d4a574]/20 border border-[#c8965f]/30 dark:border-[#d4a574]/30 flex items-center justify-center">
                            <i class="fa fa-store text-[#c8965f] dark:text-[#d4a574] text-sm"></i>
                        </div>
                        <p class="text-[10px] font-bold text-[#c8965f] dark:text-[#d4a574] uppercase tracking-widest">Shop Owner's Response</p>
                    </div>

                    @if(data_get($stats, 'latest_reply'))
                        @php $latestReply = data_get($stats, 'latest_reply'); @endphp
                        <div class="relative">
                            <i class="fa fa-quote-left text-[#c8965f]/20 dark:text-[#d4a574]/20 text-4xl absolute -top-2 -left-2"></i>
                            <blockquote class="text-sm italic text-[#1a3a3a]/80 dark:text-[#f0ebe0]/80 mb-4 relative z-10 pl-4 leading-relaxed">
                                "{{ Str::limit($latestReply->owner_reply, 120) }}"
                            </blockquote>
                        </div>
                        <div class="flex items-center gap-3 pt-4 border-t border-[#c8965f]/20 dark:border-[#d4a574]/20">
                            <div class="w-10 h-10 rounded-xl overflow-hidden bg-[#1a3a3a] dark:bg-[#c8965f] flex items-center justify-center text-[#f5f0e8] dark:text-[#0a1f1a] text-sm font-black">
                                @if($latestReply->tenant)
                                    <img src="{{ $latestReply->tenant->logo_url }}"
                                         class="w-full h-full object-cover"
                                         alt="{{ $latestReply->tenant->name }}"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-full h-full items-center justify-center hidden">
                                        {{ substr($latestReply->tenant->name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-[#1a3a3a] dark:text-[#f0ebe0] truncate">{{ $latestReply->tenant?->name ?? 'Shop Owner' }}</p>
                                <p class="text-[10px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60">Shop Owner • {{ $latestReply->updated_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col items-center py-6 text-center">
                            <div class="w-16 h-16 rounded-2xl bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 border border-[#1a3a3a]/10 dark:border-[#c8965f]/20 flex items-center justify-center mb-3">
                                <i class="fa fa-reply-all text-2xl text-[#1a3a3a]/30 dark:text-[#f0ebe0]/30"></i>
                            </div>
                            <p class="text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 text-sm italic">No shop owner responses yet</p>
                            <p class="text-[10px] text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 mt-1 max-w-xs">When shop owners reply to your reviews, they'll appear here</p>
                        </div>
                    @endif
                </div>

                <div class="lg:col-span-2 bg-[#faf6ef] dark:bg-[#0f2922] rounded-3xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 overflow-hidden">
                    <div class="p-5 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/10 bg-[#f5f0e8] dark:bg-[#0a1f1a]/60">
                        <h3 class="font-black text-[#1a3a3a] dark:text-[#f0ebe0] flex items-center gap-2">
                            <i class="fa fa-history text-[#c8965f] dark:text-[#d4a574]"></i> Your Recent Reviews
                        </h3>
                    </div>
                    <div class="divide-y divide-[#1a3a3a]/5 dark:divide-[#c8965f]/10">
                        @forelse($recent_reviews as $review)
                            <div wire:key="review-{{ $review->id }}"
                                 class="p-4 sm:p-5 hover:bg-[#c8965f]/5 dark:hover:bg-[#d4a574]/5 transition group">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <span class="font-semibold text-[#1a3a3a] dark:text-[#f0ebe0] text-sm truncate">{{ $review->product?->name ?? 'Product' }}</span>
                                            <div class="flex text-[#c8965f] dark:text-[#d4a574]">
                                                @for($i=1; $i<=5; $i++)
                                                    <i class="fa fa-star text-[10px] {{ $i <= $review->rating ? 'text-[#c8965f] dark:text-[#d4a574]' : 'text-[#1a3a3a]/20 dark:text-[#f0ebe0]/20' }}"></i>
                                                @endfor
                                            </div>
                                            <span class="text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50">{{ $review->created_at->format('M d, Y') }}</span>
                                        </div>
                                        <p class="text-sm text-[#1a3a3a]/70 dark:text-[#f0ebe0]/70 line-clamp-2 italic">"{{ $review->comment }}"</p>
                                        <div class="flex items-center gap-2 mt-2 flex-wrap">
                                            <i class="fa fa-store text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50"></i>
                                            <p class="text-[11px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60">{{ $review->tenant?->name ?? 'Shop' }}</p>
                                            @if($review->owner_reply)
                                                <span class="text-[9px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                                                    Replied
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <i class="fa fa-chevron-right text-[#1a3a3a]/30 dark:text-[#f0ebe0]/30 group-hover:text-[#c8965f] dark:group-hover:text-[#d4a574] transition text-xs mt-1"></i>
                                </div>
                            </div>
                        @empty
                            <div class="p-12 text-center">
                                <div class="w-20 h-20 mx-auto rounded-2xl bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 border border-[#1a3a3a]/10 dark:border-[#c8965f]/20 flex items-center justify-center mb-4">
                                    <i class="fa fa-pen-fancy text-3xl text-[#1a3a3a]/30 dark:text-[#f0ebe0]/30"></i>
                                </div>
                                <p class="text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 text-sm">No reviews yet. Start your coffee journey!</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Component-scoped styles --}}
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</div>