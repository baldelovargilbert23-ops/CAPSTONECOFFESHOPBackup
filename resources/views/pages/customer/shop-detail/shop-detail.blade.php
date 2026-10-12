<?php

use App\Models\Tenant;
use App\Models\Branch;
use App\Models\Review;
use App\Models\Product;
use App\Models\Order;
use App\Support\StockService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.customer')] class extends Component
{
    public $tenant;

    public function mount($tenant)
    {
        $this->tenant = Tenant::where('id', $tenant)
            ->where('status', 'active')
            ->firstOrFail();
    }

    public function render()
    {
        $tenantId = $this->tenant->id;

        /* ================================================================
         *  SUB-BRANCHES + MAIN STORE
         * ================================================================ */
        $subBranches = Branch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->orderBy('name')
            ->get();

        // Synthetic main store — the tenant owns this
        $mainStore = (object) [
            'id'          => null,
            'name'        => $this->tenant->name . ' (Main Store)',
            'address'     => $this->tenant->address ?? null,
            'logo_url'    => $this->tenant->logo_url ?? null,
            'is_main'     => true,
        ];

        // Main store stats (branch_id = NULL)
        $mainReview = Review::where('tenant_id', $tenantId)
            ->whereNull('branch_id')
            ->where('is_visible', true);

        $mainStore->avg_rating   = (float) ($mainReview->avg('rating') ?? 0);
        $mainStore->review_count = (int) $mainReview->count();
        $mainStore->orders_count = (int) Order::where('tenant_id', $tenantId)
            ->whereNull('branch_id')
            ->where('status', 'completed')
            ->count();
        // ✅ Both tenant + branch (branch = 0 means main store)
        $mainStore->menu_url = route('customer.shopmenu', [
            'tenant' => $tenantId,
            'branch' => 0,
        ]);

        // Sub-branch stats
        foreach ($subBranches as $branch) {
            $reviewQuery = Review::where('branch_id', $branch->id)->where('is_visible', true);

            $branch->avg_rating   = (float) ($reviewQuery->avg('rating') ?? 0);
            $branch->review_count = (int) $reviewQuery->count();
            $branch->orders_count = (int) $branch->orders()->where('status', 'completed')->count();
            $branch->is_main = false;
            // ✅ Both tenant + branch
            $branch->menu_url = route('customer.shopmenu', [
                'tenant' => $tenantId,
                'branch' => $branch->id,
            ]);
        }

        $locations = collect([$mainStore])->merge($subBranches);

        $menuProducts = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('type', 'finished')
            ->where('is_active', true)
            ->with('recipe.ingredient')
            ->withCount(['reviews as review_count' => fn ($q) => $q->where('is_visible', true)])
            ->withAvg(['reviews as avg_rating' => fn ($q) => $q->where('is_visible', true)], 'rating')
            ->orderBy('name')
            ->get();

        // Availability belongs to a location. Recipe drinks use that
        // location's ingredient stock; ready-made goods use their own stock.
        $sellingLocationByProduct = [];
        foreach ($locations as $location) {
            $locationBranchId = $location->is_main ? null : (int) $location->id;
            $available = StockService::availableQuantities($menuProducts, $locationBranchId, (int) $tenantId);
            $location->items_count = count(array_filter($available, fn ($quantity) => $quantity > 0));

            foreach ($available as $productId => $quantity) {
                if ($quantity > 0 && ! isset($sellingLocationByProduct[$productId])) {
                    $sellingLocationByProduct[$productId] = [
                        'name' => $location->name,
                        'url'  => $location->menu_url,
                    ];
                }
            }
        }

        /* ================================================================
         *  TOP PICKS
         * ================================================================ */
        $annotateLocation = function ($product) use ($sellingLocationByProduct) {
            $location = $sellingLocationByProduct[$product->id] ?? null;
            $product->branch_name = $location['name'] ?? 'Out of stock';
            $product->sell_url = $location['url'] ?? null;
            return $product;
        };

        $topProducts = $menuProducts
            ->filter(fn ($product) => (int) $product->review_count > 0 && isset($sellingLocationByProduct[$product->id]))
            ->sort(function ($a, $b) {
                $ratingOrder = (float) $b->avg_rating <=> (float) $a->avg_rating;
                return $ratingOrder !== 0 ? $ratingOrder : ((int) $b->review_count <=> (int) $a->review_count);
            })
            ->take(4)
            ->values()
            ->each($annotateLocation);

        if ($topProducts->count() < 4) {
            $existingIds = $topProducts->pluck('id')->all();
            $fallback = $menuProducts
                ->filter(fn ($product) => ! in_array($product->id, $existingIds, true)
                    && isset($sellingLocationByProduct[$product->id]))
                ->take(4 - $topProducts->count())
                ->each(function ($product) use ($annotateLocation) {
                    $product->setAttribute('review_count', 0);
                    $product->setAttribute('avg_rating', 0);
                    return $annotateLocation($product);
                });

            $topProducts = $topProducts->concat($fallback)->values();
        }

        /* ================================================================
         *  SHOP-WIDE RATING
         * ================================================================ */
        $allReviews      = Review::where('tenant_id', $tenantId)->where('is_visible', true);
        $shopRating      = (float) ($allReviews->avg('rating') ?? 0);
        $shopReviewCount = (int) $allReviews->count();

        return view('pages.customer.shop-detail.shop-detail', [
            'shop'            => $this->tenant,
            'locations'       => $locations,
            'firstBranch'     => $mainStore,
            'shopRating'      => $shopRating,
            'shopReviewCount' => $shopReviewCount,
            'topProducts'     => $topProducts,
        ]);
    }
};
?>

<div>
    <main class="relative min-h-screen bg-[#f5f0e8] dark:bg-[#0a1f1a] font-sans transition-colors duration-300">
        <div class="p-4 sm:p-6 lg:p-8 w-full max-w-7xl mx-auto">

            <a href="{{ route('customer.shoplist') }}"
               class="inline-flex items-center gap-2 text-sm font-bold text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 hover:text-[#c8965f] dark:hover:text-[#d4a574] transition-colors mb-5 group">
                <i class="fa fa-arrow-left text-xs transition-transform group-hover:-translate-x-1"></i>
                Back to Shops
            </a>

            {{-- HERO --}}
            <div class="relative overflow-hidden rounded-2xl mb-4">
                @if($shop->cover_url)
                    <img src="{{ $shop->cover_url }}" alt="{{ $shop->name }}"
                         class="absolute inset-0 w-full h-full object-cover">
                @else
                    <div class="absolute inset-0 bg-[#0f2922]"></div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-br from-[#0a1f1a]/90 via-[#0f2922]/80 to-[#1a3a3a]/70"></div>

                <div class="relative p-6 sm:p-8 lg:p-10 grid lg:grid-cols-2 gap-8 items-center">
                    <div>
                        <span class="inline-block text-[10px] font-bold uppercase tracking-[0.2em] text-[#d4a574] mb-3">
                            Welcome to
                        </span>
                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-[#f5f0e8] leading-tight mb-4">
                            {{ $shop->name }}
                        </h1>
                        @if($shop->tagline)
                            <p class="text-[#f0ebe0]/80 text-sm sm:text-base leading-relaxed mb-6 max-w-lg">
                                {{ $shop->tagline }}
                            </p>
                        @endif

                        <div class="flex flex-wrap items-center gap-3 mb-6">
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20">
                                <i class="fa fa-star text-[#d4a574] text-xs"></i>
                                <span class="text-[#f5f0e8] font-bold text-sm">{{ number_format($shopRating, 1) }}</span>
                                <span class="text-[#f0ebe0]/70 text-[10px]">({{ $shopReviewCount }})</span>
                            </div>
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20">
                                <i class="fa fa-store text-[#d4a574] text-xs"></i>
                                <span class="text-[#f5f0e8] font-bold text-sm">{{ $locations->count() }}</span>
                                <span class="text-[#f0ebe0]/70 text-[10px]">{{ Str::plural('location', $locations->count()) }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            @if($firstBranch)
                                <a href="{{ $firstBranch->menu_url }}"
                                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#c8965f] hover:bg-[#d4a574] text-[#0a1f1a] font-bold text-sm transition-all">
                                    <i class="fa fa-mug-hot text-xs"></i>
                                    Order from Main Store
                                </a>
                            @endif
                            <a href="#branches"
                               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white/10 border border-white/20 hover:bg-white/20 text-[#f5f0e8] font-bold text-sm transition-all">
                                Explore Branches
                                <i class="fa fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>

                    <div class="flex justify-center lg:justify-end">
                        @if($shop->logo_url)
                            <img src="{{ $shop->logo_url }}" alt="{{ $shop->name }}"
                                 class="w-48 h-48 sm:w-56 sm:h-56 rounded-3xl object-cover ring-4 ring-[#f5f0e8]/20 shadow-2xl">
                        @else
                            <div class="w-48 h-48 sm:w-56 sm:h-56 rounded-3xl bg-[#c8965f]/20 flex items-center justify-center ring-4 ring-[#f5f0e8]/20 shadow-2xl">
                                <i class="fa fa-mug-hot text-6xl text-[#d4a574]"></i>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- TOP PICKS --}}
            @if($topProducts->isNotEmpty())
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl sm:text-2xl font-black text-[#1a3a3a] dark:text-[#f0ebe0]">Top Picks</h2>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach($topProducts as $index => $product)
                            @php
                                $rank        = $index + 1;
                                $rating      = round($product->avg_rating ?? 0, 1);
                                $reviewCount = $product->review_count ?? 0;
                                $tag = null;
                                if ($rank === 1)     $tag = ['label' => 'BESTSELLER', 'class' => 'bg-emerald-600 text-white'];
                                elseif ($rank === 2) $tag = ['label' => 'TOP RATED',  'class' => 'bg-blue-600 text-white'];
                                elseif ($rank === 3) $tag = ['label' => 'POPULAR',    'class' => 'bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a]'];
                            @endphp

                            <div class="group relative bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 hover:border-[#c8965f]/40 transition-all duration-300 overflow-hidden">
                                @if($tag)
                                    <span class="absolute top-3 left-3 z-10 text-[9px] font-black uppercase tracking-wider px-2 py-1 rounded {{ $tag['class'] }}">
                                        {{ $tag['label'] }}
                                    </span>
                                @endif

                                <div class="relative w-full aspect-square bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 overflow-hidden">
                                    @if($product->image_url)
                                        <img src="{{ $product->image_url }}"
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                             loading="lazy">
                                    @else
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <i class="fa fa-mug-hot text-4xl text-[#1a3a3a]/30 dark:text-[#f0ebe0]/30"></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="p-3">
                                    <p class="text-xs font-bold text-[#1a3a3a] dark:text-[#f0ebe0] truncate mb-0.5">
                                        {{ $product->name }}
                                    </p>
                                    <p class="text-[10px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 truncate mb-2">
                                        {{ $product->branch_name }}
                                    </p>

                                    <div class="flex items-center gap-1 mb-3">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fa fa-star text-[10px] {{ $i <= round($rating) ? 'text-[#c8965f] dark:text-[#d4a574]' : 'text-[#1a3a3a]/20 dark:text-[#f0ebe0]/20' }}"></i>
                                        @endfor
                                        <span class="text-[10px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 ml-1">({{ $reviewCount }})</span>
                                    </div>

                                    <div class="mb-3">
                                        <span class="text-base font-black text-[#c8965f] dark:text-[#d4a574]">
                                            ₱{{ number_format($product->price, 2) }}
                                        </span>
                                    </div>

                                    @if($product->sell_url)
                                        <a href="{{ $product->sell_url }}"
                                           class="w-full inline-flex items-center justify-center gap-1.5 py-2 rounded-lg bg-[#1a3a3a] dark:bg-[#c8965f] hover:bg-[#0f2922] dark:hover:bg-[#d4a574] text-[#f5f0e8] dark:text-[#0a1f1a] font-bold text-[11px] transition-colors">
                                            <i class="fa fa-eye text-[10px]"></i>
                                            View Menu
                                        </a>
                                    @else
                                        <button disabled
                                                class="w-full inline-flex items-center justify-center gap-1.5 py-2 rounded-lg bg-[#1a3a3a]/20 dark:bg-[#c8965f]/20 text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 font-bold text-[11px] cursor-not-allowed">
                                            <i class="fa fa-ban text-[10px]"></i>
                                            Out of stock
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- SELLING LOCATIONS --}}
            <div id="branches" class="mb-4 scroll-mt-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl sm:text-2xl font-black text-[#1a3a3a] dark:text-[#f0ebe0]">
                        Our Selling Locations
                    </h2>
                    <span class="text-xs text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 font-medium">
                        {{ $locations->count() }} {{ Str::plural('location', $locations->count()) }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($locations as $loc)
                    <div wire:key="loc-{{ $loc->id ?? 'main' }}"
                         class="group relative bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border {{ $loc->is_main ? 'border-[#c8965f]/40' : 'border-[#1a3a3a]/10 dark:border-[#c8965f]/15' }} hover:border-[#c8965f]/60 transition-all duration-300 overflow-hidden">

                        @if($loc->is_main)
                            <span class="absolute top-3 right-3 z-10 text-[9px] font-black uppercase tracking-wider px-2 py-1 rounded bg-[#c8965f] text-[#0a1f1a]">
                                <i class="fa fa-crown text-[8px]"></i> Main Store
                            </span>
                        @endif

                        <div class="p-4 flex items-start gap-4">
                            <div class="w-16 h-16 rounded-xl overflow-hidden bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 flex-shrink-0 flex items-center justify-center">
                                @if($loc->logo_url)
                                    <img src="{{ $loc->logo_url }}" alt="{{ $loc->name }}" class="w-full h-full object-cover">
                                @else
                                    <i class="fa fa-store text-xl text-[#c8965f] dark:text-[#d4a574]"></i>
                                @endif
                            </div>
                            <div class="flex-grow min-w-0">
                                <h3 class="font-black text-[#1a3a3a] dark:text-[#f0ebe0] text-base mb-1 truncate">
                                    {{ $loc->name }}
                                </h3>
                                @if($loc->address)
                                    <p class="text-[11px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 truncate mb-2">
                                        <i class="fa fa-map-pin text-[#c8965f] dark:text-[#d4a574] mr-1"></i>
                                        {{ $loc->address }}
                                    </p>
                                @endif
                                <div class="flex items-center gap-3 text-[10px] flex-wrap">
                                    <span class="flex items-center gap-1">
                                        <i class="fa fa-star text-[#c8965f] dark:text-[#d4a574]"></i>
                                        <span class="font-bold text-[#1a3a3a] dark:text-[#f0ebe0]">{{ number_format($loc->avg_rating, 1) }}</span>
                                        <span class="text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40">({{ $loc->review_count }})</span>
                                    </span>
                                    <span class="flex items-center gap-1 text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60">
                                        <i class="fa fa-shopping-bag text-[#c8965f] dark:text-[#d4a574]"></i>
                                        {{ $loc->orders_count }} orders
                                    </span>
                                    <span class="flex items-center gap-1 text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60">
                                        <i class="fa fa-mug-hot text-[#c8965f] dark:text-[#d4a574]"></i>
                                        {{ $loc->items_count }} items
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="px-4 pb-4">
                            <a href="{{ $loc->menu_url }}"
                               class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-xl bg-[#1a3a3a] dark:bg-[#c8965f] hover:bg-[#0f2922] dark:hover:bg-[#d4a574] text-[#f5f0e8] dark:text-[#0a1f1a] font-bold text-xs transition-colors">
                                <i class="fa fa-mug-saucer text-[11px]"></i>
                                Order from {{ $loc->is_main ? 'Main Store' : 'This Branch' }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </main>
</div>
