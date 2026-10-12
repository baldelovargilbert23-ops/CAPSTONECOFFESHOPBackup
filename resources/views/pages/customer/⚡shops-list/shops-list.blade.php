<div>
    <main class="relative min-h-screen bg-[#f5f0e8] dark:bg-[#0a1f1a] font-sans transition-colors duration-300">
        <div class="p-4 sm:p-6 lg:p-8 w-full max-w-6xl mx-auto">
            {{-- ═══════════════ SEARCH ═══════════════ --}}
            <div class="mb-3 relative">
                <i class="fa fa-search absolute left-4 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 text-sm"></i>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search shops or drinks..."
                       class="w-full pl-11 pr-4 py-3.5 bg-[#faf6ef] dark:bg-[#0f2922] border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 rounded-2xl text-sm text-[#1a3a3a] dark:text-[#f0ebe0] placeholder-[#1a3a3a]/40 dark:placeholder-[#f0ebe0]/40 focus:outline-none focus:border-[#c8965f] dark:focus:border-[#d4a574] focus:ring-4 focus:ring-[#c8965f]/15 dark:focus:ring-[#d4a574]/15 transition-all">
            </div>

            {{-- ═══════════════ SORT TABS ═══════════════ --}}
            <div class="mb-5 flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide">
                @foreach([
                    'rating'   => ['label' => 'Top Rated',     'icon' => 'fa-star'],
                    'branches' => ['label' => 'Most Branches', 'icon' => 'fa-code-branch'],
                    'price'    => ['label' => 'Affordable',    'icon' => 'fa-tag'],
                ] as $key => $meta)
                    <button wire:click="setSort('{{ $key }}')"
                            class="flex-shrink-0 inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap
                                {{ $sortBy === $key
                                    ? 'bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] shadow-md scale-[1.02]'
                                    : 'bg-[#faf6ef] dark:bg-[#0f2922] text-[#1a3a3a]/70 dark:text-[#f0ebe0]/70 border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 hover:border-[#c8965f]/50 dark:hover:border-[#d4a574]/50 hover:text-[#c8965f] dark:hover:text-[#d4a574]' }}">
                        <i class="fa {{ $meta['icon'] }} text-[10px]"></i>
                        {{ $meta['label'] }}
                    </button>
                @endforeach
            </div>

            {{-- ═══════════════ SHOP CARDS ═══════════════ --}}
            @if($shops->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                    @foreach($shops as $shop)
                        @php
                            $shopLogoUrl = \App\Support\ImageUrlResolver::resolve(
                                $shop->logo_path ?? null,
                                'https://ui-avatars.com/api/?name=' . urlencode($shop->name)
                                    . '&size=256&background=c8965f&color=f5f0e8&bold=true&font-size=0.35'
                            );
                            $isOpen = $shop->is_open ?? true;
                        @endphp

                        <a href="{{ route('customer.shopdetail', $shop->id) }}"
                           wire:key="shop-{{ $shop->id }}"
                           class="group relative block bg-[#faf6ef] dark:bg-[#0f2922] rounded-[2rem] border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 hover:border-[#c8965f]/40 dark:hover:border-[#d4a574]/40 hover:shadow-lg transition-all duration-500 overflow-hidden">

                            <div class="relative p-4 sm:p-5">

                                {{-- ═══ TOP: Logo + Name + Rating ═══ --}}
                                <div class="flex items-start gap-4 mb-4">

                                    {{-- Circular shop logo --}}
                                    <div class="relative flex-shrink-0">
                                        <div class="relative w-[68px] h-[68px] rounded-2xl overflow-hidden bg-[#f5f0e8] dark:bg-[#0a1f1a] ring-[3px] ring-[#faf6ef] dark:ring-[#0f2922] shadow-md flex items-center justify-center">
                                            <img src="{{ $shopLogoUrl }}"
                                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                                 alt="{{ $shop->name }} logo"
                                                 loading="lazy"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                                            <div class="absolute inset-0 items-center justify-center hidden">
                                                <i class="fa fa-mug-hot text-2xl text-[#c8965f]/60 dark:text-[#d4a574]/60"></i>
                                            </div>
                                        </div>

                                        {{-- Rating badge --}}
                                        @if($shop->review_count > 0)
                                            <div class="absolute -bottom-1.5 -right-1.5 bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] rounded-full px-2 py-0.5 flex items-center gap-0.5 shadow-md ring-2 ring-[#faf6ef] dark:ring-[#0f2922] z-10">
                                                <i class="fa fa-star text-[8px]"></i>
                                                <span class="text-[10px] font-black">{{ number_format($shop->avg_rating, 1) }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Name + location --}}
                                    <div class="flex-grow min-w-0 pt-0.5">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <h3 class="text-base sm:text-lg font-black text-[#1a3a3a] dark:text-[#f0ebe0] group-hover:text-[#c8965f] dark:group-hover:text-[#d4a574] transition truncate">
                                                {{ $shop->name }}
                                            </h3>

                                            <span class="inline-flex items-center gap-1 flex-shrink-0">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $isOpen ? 'bg-emerald-500 animate-pulse' : 'bg-[#1a3a3a]/40 dark:bg-[#f0ebe0]/40' }}"></span>
                                                <span class="text-[9px] font-bold uppercase tracking-wider {{ $isOpen ? 'text-emerald-600 dark:text-emerald-400' : 'text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40' }}">
                                                    {{ $isOpen ? 'Open' : 'Closed' }}
                                                </span>
                                            </span>
                                        </div>

                                        @if($shop->primary_city)
                                            <p class="text-[11px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 flex items-center gap-1 truncate">
                                                <i class="fa fa-location-dot text-[#c8965f] dark:text-[#d4a574] text-[10px] flex-shrink-0"></i>
                                                <span class="truncate">{{ $shop->primary_city }}</span>
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                {{-- ═══ MIDDLE: Stat pills ═══ --}}
                                <div class="grid grid-cols-2 gap-2 mb-4">
                                    <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 border border-[#1a3a3a]/5 dark:border-[#c8965f]/10">
                                        <div class="w-7 h-7 rounded-lg bg-[#c8965f]/10 dark:bg-[#d4a574]/10 flex items-center justify-center flex-shrink-0">
                                            <i class="fa fa-code-branch text-[#c8965f] dark:text-[#d4a574] text-[10px]"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[9px] text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 uppercase tracking-wider font-bold">Branches</p>
                                            <p class="text-xs font-black text-[#1a3a3a] dark:text-[#f0ebe0] leading-tight">{{ $shop->branches_count }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 border border-[#1a3a3a]/5 dark:border-[#c8965f]/10">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center flex-shrink-0">
                                            <i class="fa fa-tag text-emerald-600 dark:text-emerald-400 text-[10px]"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[9px] text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 uppercase tracking-wider font-bold">Price</p>
                                            <p class="text-xs font-black text-[#1a3a3a] dark:text-[#f0ebe0] leading-tight truncate">
                                                @if($shop->min_price > 0)
                                                    ₱{{ number_format($shop->min_price, 0) }}–{{ number_format($shop->max_price, 0) }}
                                                @else
                                                    —
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- ═══ BOTTOM: Popular products + arrow ═══ --}}
                                <div class="flex items-end justify-between gap-3 pt-3 border-t border-[#1a3a3a]/5 dark:border-[#c8965f]/10">
                                    <div class="flex-grow min-w-0">
                                        @if(!empty($shop->top_products))
                                            <p class="text-[9px] text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 uppercase tracking-wider font-bold mb-1.5">Popular</p>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach(array_slice($shop->top_products, 0, 2) as $productName)
                                                    <span class="px-2 py-0.5 rounded-full bg-[#c8965f]/10 dark:bg-[#d4a574]/10 text-[#c8965f] dark:text-[#d4a574] text-[10px] font-semibold border border-[#c8965f]/20 dark:border-[#d4a574]/20 truncate max-w-[110px]">
                                                        {{ $productName }}
                                                    </span>
                                                @endforeach
                                                @if(count($shop->top_products) > 2)
                                                    <span class="px-2 py-0.5 rounded-full bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 text-[10px] font-bold">
                                                        +{{ count($shop->top_products) - 2 }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <p class="text-[11px] text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 italic">No products listed yet</p>
                                        @endif
                                    </div>

                                    {{-- Circular arrow button --}}
                                    <div class="flex-shrink-0 w-9 h-9 rounded-full bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 group-hover:bg-[#1a3a3a] dark:group-hover:bg-[#c8965f] flex items-center justify-center transition-all duration-300 group-hover:scale-110">
                                        <i class="fa fa-arrow-right text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 group-hover:text-[#f5f0e8] dark:group-hover:text-[#0a1f1a] group-hover:translate-x-0.5 transition-all text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                {{-- ═══════════════ EMPTY STATE ═══════════════ --}}
                <div class="text-center py-16 bg-[#faf6ef] dark:bg-[#0f2922] rounded-[2rem] border-2 border-dashed border-[#1a3a3a]/15 dark:border-[#c8965f]/20">
                    <div class="relative w-20 h-20 mx-auto mb-4">
                        <div class="relative w-full h-full rounded-3xl bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 flex items-center justify-center">
                            <i class="fa fa-mug-hot text-3xl text-[#c8965f] dark:text-[#d4a574]"></i>
                        </div>
                    </div>
                    <h3 class="text-base sm:text-lg font-black text-[#1a3a3a] dark:text-[#f0ebe0] mb-1">
                        @if($search)
                            No shops match "{{ $search }}"
                        @else
                            No shops available yet
                        @endif
                    </h3>
                    <p class="text-xs sm:text-sm text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 max-w-xs mx-auto mb-5">
                        @if($search)
                            Try a different search term or browse all shops.
                        @else
                            Check back soon — we're onboarding partners.
                        @endif
                    </p>
                    @if($search)
                        <button wire:click="$set('search', '')"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] rounded-xl font-bold text-xs hover:opacity-90 transition-all">
                            <i class="fa fa-times-circle"></i> Clear Search
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </main>

    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

        .group:hover { transform: translateY(-2px); }
        .group { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    </style>
</div>